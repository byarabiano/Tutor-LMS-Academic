<?php

class TLMS_Restrict_Content_Pro_Integration {
    
    private $is_active = false;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_integration'));
    }
    
    public function init_integration() {
        if (!function_exists('rcp_get_capabilities')) {
            return;
        }
        
        $this->is_active = true;
        
        // Add integration hooks
        add_filter('tlms_course_visibility', array($this, 'apply_rcp_restrictions'), 20, 2);
        add_action('rcp_user_profile_updated', array($this, 'sync_membership_categories'), 10, 2);
        add_filter('tlms_user_has_access', array($this, 'check_rcp_access'), 10, 3);
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_rcp_meta_boxes'));
        add_action('save_post', array($this, 'save_rcp_meta'));
    }
    
    public function apply_rcp_restrictions($visibility, $course_id) {
        if (!$this->is_active) {
            return $visibility;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has RCP access to this course
        if (!$this->user_has_rcp_access($user_id, $course_id)) {
            return false;
        }
        
        return $visibility;
    }
    
    public function user_has_rcp_access($user_id, $course_id) {
        // Get RCP restrictions for this course
        $restricted_levels = get_post_meta($course_id, '_tlms_rcp_restriction_levels', true);
        
        if (empty($restricted_levels)) {
            return true; // No RCP restrictions
        }
        
        // Check if user has any of the required membership levels
        return $this->user_has_required_levels($user_id, $restricted_levels);
    }
    
    public function user_has_required_levels($user_id, $required_levels) {
        if (!function_exists('rcp_get_customer_by_user_id')) {
            return false;
        }
        
        $customer = rcp_get_customer_by_user_id($user_id);
        if (!$customer) {
            return false;
        }
        
        $memberships = $customer->get_memberships();
        
        foreach ($memberships as $membership) {
            if ($membership->is_active() && in_array($membership->get_object_id(), $required_levels)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function sync_membership_categories($user_id, $customer) {
        $options = get_option('tlms_academic_pro_settings');
        if (!isset($options['rcp_sync_memberships']) || !$options['rcp_sync_memberships']) {
            return;
        }
        
        $memberships = $customer->get_memberships();
        $academic_categories = array();
        
        foreach ($memberships as $membership) {
            if ($membership->is_active()) {
                $level_categories = $this->get_level_categories($membership->get_object_id());
                $academic_categories = array_merge($academic_categories, $level_categories);
            }
        }
        
        if (!empty($academic_categories)) {
            update_user_meta($user_id, 'tlms_academic_categories', array_unique($academic_categories));
        }
    }
    
    public function get_level_categories($level_id) {
        return get_post_meta($level_id, '_tlms_academic_categories', true) ?: array();
    }
    
    public function add_rcp_meta_boxes() {
        add_meta_box(
            'tlms-rcp-integration',
            __('Academic Categories Restriction', 'tutor-lms-academic-pro'),
            array($this, 'rcp_meta_box_callback'),
            'rcp_membership',
            'normal',
            'high'
        );
    }
    
    public function rcp_meta_box_callback($post) {
        wp_nonce_field('tlms_rcp_nonce', 'tlms_rcp_nonce');
        
        $selected_categories = get_post_meta($post->ID, '_tlms_academic_categories', true);
        if (!is_array($selected_categories)) {
            $selected_categories = array();
        }
        
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false
        ));
        ?>
        <div class="tlms-rcp-integration">
            <p><?php _e('Select academic categories that will be automatically assigned to users with this membership level:', 'tutor-lms-academic-pro'); ?></p>
            
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                <?php foreach ($categories as $category): ?>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="tlms_academic_categories[]" 
                               value="<?php echo $category->term_id; ?>" 
                               <?php checked(in_array($category->term_id, $selected_categories)); ?> />
                        <?php echo $category->name; ?>
                        <span class="description">(<?php echo get_term_meta($category->term_id, 'education_type', true) ?: 'general'; ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    public function save_rcp_meta($post_id) {
        if (!isset($_POST['tlms_rcp_nonce']) || 
            !wp_verify_nonce($_POST['tlms_rcp_nonce'], 'tlms_rcp_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if ('rcp_membership' !== get_post_type($post_id)) {
            return;
        }
        
        if (isset($_POST['tlms_academic_categories'])) {
            $categories = array_map('intval', $_POST['tlms_academic_categories']);
            update_post_meta($post_id, '_tlms_academic_categories', $categories);
        } else {
            delete_post_meta($post_id, '_tlms_academic_categories');
        }
    }
    
    public function check_rcp_access($has_access, $user_id, $course_id) {
        if (!$this->is_active) {
            return $has_access;
        }
        
        return $has_access && $this->user_has_rcp_access($user_id, $course_id);
    }
}

?>