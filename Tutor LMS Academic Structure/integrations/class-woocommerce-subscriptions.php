<?php

class TLMS_WooCommerce_Subscriptions_Integration {
    
    private $is_active = false;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_integration'));
    }
    
    public function init_integration() {
        if (!class_exists('WC_Subscriptions')) {
            return;
        }
        
        $this->is_active = true;
        
        // Add integration hooks
        add_filter('tlms_course_visibility', array($this, 'apply_subscription_restrictions'), 20, 2);
        add_action('woocommerce_subscription_status_updated', array($this, 'handle_subscription_status_change'), 10, 3);
        add_filter('tlms_user_has_access', array($this, 'check_subscription_access'), 10, 3);
        
        // Product management hooks
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_fields'));
    }
    
    public function apply_subscription_restrictions($visibility, $course_id) {
        if (!$this->is_active) {
            return $visibility;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user has subscription access to this course
        if (!$this->user_has_subscription_access($user_id, $course_id)) {
            return false;
        }
        
        return $visibility;
    }
    
    public function user_has_subscription_access($user_id, $course_id) {
        // Get subscription restrictions for this course
        $restricted_products = get_post_meta($course_id, '_tlms_subscription_products', true);
        
        if (empty($restricted_products)) {
            return true; // No subscription restrictions
        }
        
        // Check if user has active subscription for any required product
        return $this->user_has_active_subscription($user_id, $restricted_products);
    }
    
    public function user_has_active_subscription($user_id, $product_ids) {
        if (!function_exists('wcs_user_has_subscription')) {
            return false;
        }
        
        foreach ($product_ids as $product_id) {
            if (wcs_user_has_subscription($user_id, $product_id, 'active')) {
                return true;
            }
        }
        
        return false;
    }
    
    public function handle_subscription_status_change($subscription, $new_status, $old_status) {
        $user_id = $subscription->get_user_id();
        $options = get_option('tlms_academic_pro_settings');
        
        if (!isset($options['wcs_sync_subscriptions']) || !$options['wcs_sync_subscriptions']) {
            return;
        }
        
        if ($new_status === 'active') {
            // Subscription activated - assign categories
            $this->assign_categories_from_subscription($user_id, $subscription);
        } elseif (in_array($new_status, array('cancelled', 'expired'))) {
            // Subscription ended - reset to default
            $default_category = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
            update_user_meta($user_id, 'tlms_education_type', $default_category);
            delete_user_meta($user_id, 'tlms_academic_categories');
        }
    }
    
    public function assign_categories_from_subscription($user_id, $subscription) {
        $items = $subscription->get_items();
        $all_categories = array();
        
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product_categories = get_post_meta($product_id, '_tlms_academic_categories', true);
            
            if (is_array($product_categories)) {
                $all_categories = array_merge($all_categories, $product_categories);
            }
        }
        
        if (!empty($all_categories)) {
            update_user_meta($user_id, 'tlms_academic_categories', array_unique($all_categories));
            
            // Set education type if not set
            $education_type = get_user_meta($user_id, 'tlms_education_type', true);
            if (!$education_type) {
                update_user_meta($user_id, 'tlms_education_type', 'university');
            }
        }
    }
    
    public function add_product_fields() {
        global $post;
        
        $selected_categories = get_post_meta($post->ID, '_tlms_academic_categories', true);
        if (!is_array($selected_categories)) {
            $selected_categories = array();
        }
        
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false
        ));
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="tlms_academic_categories"><?php _e('Academic Categories', 'tutor-lms-academic-pro'); ?></label>
                <select id="tlms_academic_categories" name="tlms_academic_categories[]" style="width: 100%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e('Select academic categories&hellip;', 'tutor-lms-academic-pro'); ?>">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category->term_id; ?>" <?php selected(in_array($category->term_id, $selected_categories)); ?>>
                            <?php echo $category->name; ?> (<?php echo get_term_meta($category->term_id, 'education_type', true) ?: 'general'; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo wc_help_tip(__('Users with active subscriptions to this product will be automatically assigned to these academic categories.', 'tutor-lms-academic-pro')); ?>
            </p>
        </div>
        <?php
    }
    
    public function save_product_fields($product_id) {
        if (isset($_POST['tlms_academic_categories'])) {
            $categories = array_map('intval', $_POST['tlms_academic_categories']);
            update_post_meta($product_id, '_tlms_academic_categories', $categories);
        } else {
            delete_post_meta($product_id, '_tlms_academic_categories');
        }
    }
    
    public function check_subscription_access($has_access, $user_id, $course_id) {
        if (!$this->is_active) {
            return $has_access;
        }
        
        return $has_access && $this->user_has_subscription_access($user_id, $course_id);
    }
}

?>