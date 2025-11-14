<?php

class TLMS_Paid_Memberships_Pro_Integration {
    
    private $is_active = false;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_integration'));
    }
    
    public function init_integration() {
        if (!function_exists('pmpro_getAllLevels')) {
            return;
        }
        
        $this->is_active = true;
        
        // Add integration hooks
        add_filter('tlms_course_visibility', array($this, 'apply_pmpro_restrictions'), 20, 2);
        add_action('pmpro_after_change_membership_level', array($this, 'sync_membership_categories'), 10, 3);
        add_filter('tlms_user_has_access', array($this, 'check_pmpro_access'), 10, 3);
        
        // Admin hooks
        add_action('pmpro_membership_level_after_other_settings', array($this, 'add_level_settings'));
        add_action('pmpro_save_membership_level', array($this, 'save_level_settings'));
    }
    
    public function apply_pmpro_restrictions($visibility, $course_id) {
        if (!$this->is_active) {
            return $visibility;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has PMPro access to this course
        if (!$this->user_has_pmpro_access($user_id, $course_id)) {
            return false;
        }
        
        return $visibility;
    }
    
    public function user_has_pmpro_access($user_id, $course_id) {
        // Get PMPro restrictions for this course
        $restricted_levels = get_post_meta($course_id, '_tlms_pmpro_restriction_levels', true);
        
        if (empty($restricted_levels)) {
            return true; // No PMPro restrictions
        }
        
        // Check if user has any of the required membership levels
        return $this->user_has_required_levels($user_id, $restricted_levels);
    }
    
    public function user_has_required_levels($user_id, $required_levels) {
        if (!function_exists('pmpro_hasMembershipLevel')) {
            return false;
        }
        
        foreach ($required_levels as $level_id) {
            if (pmpro_hasMembershipLevel($level_id, $user_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function sync_membership_categories($level_id, $user_id, $cancel_level) {
        $options = get_option('tlms_academic_pro_settings');
        if (!isset($options['pmpro_sync_memberships']) || !$options['pmpro_sync_memberships']) {
            return;
        }
        
        if ($level_id > 0) {
            // User added to a level
            $level_categories = $this->get_level_categories($level_id);
            if (!empty($level_categories)) {
                update_user_meta($user_id, 'tlms_academic_categories', $level_categories);
                
                // Set education type based on level if not set
                $education_type = get_user_meta($user_id, 'tlms_education_type', true);
                if (!$education_type) {
                    update_user_meta($user_id, 'tlms_education_type', 'university');
                }
            }
        } else {
            // User removed from level - reset to default
            $default_category = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
            update_user_meta($user_id, 'tlms_education_type', $default_category);
            delete_user_meta($user_id, 'tlms_academic_categories');
        }
    }
    
    public function get_level_categories($level_id) {
        return get_option('tlms_pmpro_level_' . $level_id . '_categories', array());
    }
    
    public function add_level_settings() {
        global $wpdb;
        
        $level_id = intval($_REQUEST['edit']);
        $selected_categories = $this->get_level_categories($level_id);
        
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false
        ));
        ?>
        <h3 class="topborder"><?php _e('Academic Categories Integration', 'tutor-lms-academic-pro'); ?></h3>
        <p><?php _e('Automatically assign academic categories to users with this membership level:', 'tutor-lms-academic-pro'); ?></p>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" valign="top">
                        <label for="tlms_academic_categories"><?php _e('Academic Categories', 'tutor-lms-academic-pro'); ?></label>
                    </th>
                    <td>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            <?php if ($categories && !is_wp_error($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="tlms_academic_categories[]" 
                                               value="<?php echo $category->term_id; ?>" 
                                               <?php checked(in_array($category->term_id, $selected_categories)); ?> />
                                        <?php echo $category->name; ?>
                                        <span class="description">(<?php echo get_term_meta($category->term_id, 'education_type', true) ?: 'general'; ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><?php _e('No academic categories found.', 'tutor-lms-academic-pro'); ?></p>
                            <?php endif; ?>
                        </div>
                        <p class="description">
                            <?php _e('Users with this membership level will be automatically assigned to the selected academic categories.', 'tutor-lms-academic-pro'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    public function save_level_settings($level_id) {
        if (isset($_POST['tlms_academic_categories'])) {
            $categories = array_map('intval', $_POST['tlms_academic_categories']);
            update_option('tlms_pmpro_level_' . $level_id . '_categories', $categories);
        } else {
            delete_option('tlms_pmpro_level_' . $level_id . '_categories');
        }
    }
    
    public function check_pmpro_access($has_access, $user_id, $course_id) {
        if (!$this->is_active) {
            return $has_access;
        }
        
        return $has_access && $this->user_has_pmpro_access($user_id, $course_id);
    }
}

?>