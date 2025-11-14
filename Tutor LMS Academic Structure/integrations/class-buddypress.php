<?php

class TLMS_BuddyPress_Integration {
    
    private $is_active = false;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_integration'));
    }
    
    public function init_integration() {
        if (!class_exists('BuddyPress')) {
            return;
        }
        
        $this->is_active = true;
        
        // Add integration hooks
        add_action('tlms_user_category_updated', array($this, 'sync_buddypress_groups'), 10, 2);
        add_action('groups_join_group', array($this, 'handle_group_join'), 10, 2);
        add_action('groups_leave_group', array($this, 'handle_group_leave'), 10, 2);
        add_filter('bp_user_can_view_course', array($this, 'check_course_visibility'), 10, 3);
        
        // Group extension
        add_action('bp_init', array($this, 'setup_group_extension'));
    }
    
    public function sync_buddypress_groups($user_id, $academic_categories) {
        $options = get_option('tlms_academic_pro_settings');
        if (!isset($options['bp_sync_groups']) || !$options['bp_sync_groups']) {
            return;
        }
        
        // Remove user from all academic groups first
        $this->remove_user_from_academic_groups($user_id);
        
        // Add user to groups based on academic categories
        foreach ($academic_categories as $category_id) {
            $group_id = $this->get_group_for_category($category_id);
            if ($group_id) {
                groups_join_group($group_id, $user_id);
            }
        }
    }
    
    public function remove_user_from_academic_groups($user_id) {
        $academic_groups = $this->get_all_academic_groups();
        
        foreach ($academic_groups as $group_id) {
            if (groups_is_user_member($user_id, $group_id)) {
                groups_leave_group($group_id, $user_id);
            }
        }
    }
    
    public function get_group_for_category($category_id) {
        $group_id = get_term_meta($category_id, 'buddypress_group_id', true);
        
        if (!$group_id) {
            // Auto-create group if enabled
            $options = get_option('tlms_academic_pro_settings');
            if (isset($options['bp_auto_create_groups']) && $options['bp_auto_create_groups']) {
                $group_id = $this->create_group_for_category($category_id);
            }
        }
        
        return $group_id;
    }
    
    public function create_group_for_category($category_id) {
        $category = get_term($category_id, 'tlms_academic_category');
        if (!$category || is_wp_error($category)) {
            return false;
        }
        
        $group_args = array(
            'creator_id' => 1, // Admin user
            'name' => $category->name,
            'description' => sprintf(__('Academic group for %s category', 'tutor-lms-academic-pro'), $category->name),
            'status' => 'private',
            'enable_forum' => false
        );
        
        $group_id = groups_create_group($group_args);
        
        if ($group_id) {
            update_term_meta($category_id, 'buddypress_group_id', $group_id);
            
            // Add category information as group meta
            groups_update_groupmeta($group_id, 'academic_category_id', $category_id);
            groups_update_groupmeta($group_id, 'education_type', get_term_meta($category_id, 'education_type', true));
            
            return $group_id;
        }
        
        return false;
    }
    
    public function get_all_academic_groups() {
        global $wpdb;
        
        return $wpdb->get_col("
            SELECT group_id 
            FROM {$wpdb->prefix}bp_groups_groupmeta 
            WHERE meta_key = 'academic_category_id'
        ");
    }
    
    public function handle_group_join($group_id, $user_id) {
        $category_id = groups_get_groupmeta($group_id, 'academic_category_id');
        
        if ($category_id) {
            // This is an academic group - update user's academic categories
            $current_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
            if (!is_array($current_categories)) {
                $current_categories = array();
            }
            
            if (!in_array($category_id, $current_categories)) {
                $current_categories[] = $category_id;
                update_user_meta($user_id, 'tlms_academic_categories', $current_categories);
            }
        }
    }
    
    public function handle_group_leave($group_id, $user_id) {
        $category_id = groups_get_groupmeta($group_id, 'academic_category_id');
        
        if ($category_id) {
            // Remove category from user's academic categories
            $current_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
            if (is_array($current_categories)) {
                $key = array_search($category_id, $current_categories);
                if ($key !== false) {
                    unset($current_categories[$key]);
                    update_user_meta($user_id, 'tlms_academic_categories', array_values($current_categories));
                }
            }
        }
    }
    
    public function check_course_visibility($can_view, $course_id, $user_id) {
        if (!function_exists('bp_is_active') || !bp_is_active('groups')) {
            return $can_view;
        }
        
        // Get course academic categories
        $course_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
        
        if (empty($course_categories)) {
            return $can_view; // Course has no academic restrictions
        }
        
        // Get user's academic categories from groups
        $user_academic_groups = $this->get_user_academic_groups($user_id);
        
        // Check if user has any matching categories
        foreach ($course_categories as $category_id) {
            $group_id = $this->get_group_for_category($category_id);
            if ($group_id && in_array($group_id, $user_academic_groups)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function get_user_academic_groups($user_id) {
        $groups = groups_get_user_groups($user_id);
        $academic_groups = array();
        
        foreach ($groups['groups'] as $group_id) {
            if (groups_get_groupmeta($group_id, 'academic_category_id')) {
                $academic_groups[] = $group_id;
            }
        }
        
        return $academic_groups;
    }
    
    public function setup_group_extension() {
        if (!bp_is_active('groups')) {
            return;
        }
        
        bp_register_group_extension('TLMS_BuddyPress_Group_Extension');
    }
}

// BuddyPress Group Extension
class TLMS_BuddyPress_Group_Extension extends BP_Group_Extension {
    
    function __construct() {
        $args = array(
            'slug' => 'academic-info',
            'name' => __('Academic Information', 'tutor-lms-academic-pro'),
            'enable_nav_item' => false,
            'screens' => array(
                'edit' => array(
                    'enabled' => true,
                    'name' => __('Academic Information', 'tutor-lms-academic-pro'),
                    'slug' => 'academic-info',
                ),
            ),
        );
        parent::init($args);
    }
    
    function settings_screen($group_id = null) {
        $category_id = groups_get_groupmeta($group_id, 'academic_category_id');
        $category = $category_id ? get_term($category_id, 'tlms_academic_category') : null;
        ?>
        
        <div class="tlms-buddypress-settings">
            <?php if ($category): ?>
                <p><strong><?php _e('Linked Academic Category:', 'tutor-lms-academic-pro'); ?></strong> 
                   <?php echo $category->name; ?>
                </p>
                <p><strong><?php _e('Education Type:', 'tutor-lms-academic-pro'); ?></strong> 
                   <?php echo get_term_meta($category_id, 'education_type', true) ?: 'general'; ?>
                </p>
            <?php else: ?>
                <p><?php _e('This group is not linked to any academic category.', 'tutor-lms-academic-pro'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php
    }
    
    function settings_screen_save($group_id = null) {
        // Settings are auto-managed, no need for save logic
    }
}

?>