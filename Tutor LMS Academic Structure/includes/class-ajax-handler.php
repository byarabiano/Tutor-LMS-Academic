<?php

class TLMS_Ajax_Handler {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('wp_ajax_tlms_admin_actions', array($this, 'handle_admin_actions'));
        add_action('wp_ajax_tlms_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_tlms_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_tlms_bulk_assign_categories', array($this, 'bulk_assign_categories'));
        add_action('wp_ajax_tlms_get_course_categories', array($this, 'get_course_categories'));
    }
    
    public function get_course_categories() {
        check_ajax_referer('tlms_course_nonce', 'nonce');
        
        $education_type = sanitize_text_field($_POST['education_type']);
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        $selected_categories = array();
        if ($course_id) {
            $selected_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
        }
        
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'education_type',
                    'value' => $education_type
                )
            )
        ));
        
        if (empty($categories) || is_wp_error($categories)) {
            wp_send_json_success('<p>' . __('No categories found for this education type.', 'tutor-lms-academic-pro') . '</p>');
            return;
        }
        
        $output = '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
        foreach ($categories as $category) {
            $checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
            $output .= '<label style="display: block; margin-bottom: 5px;">';
            $output .= '<input type="checkbox" name="tlms_academic_categories[]" value="' . $category->term_id . '" ' . $checked . '> ';
            $output .= $category->name;
            $output .= '</label>';
        }
        $output .= '</div>';
        
        wp_send_json_success($output);
    }
    
    // باقي الدوال تبقى كما هي...
    public function handle_admin_actions() {
        check_ajax_referer('tlms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_tutor')) {
            wp_send_json_error(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'migrate_existing_users':
                $this->migrate_existing_users();
                break;
                
            case 'clear_cache':
                $this->clear_cache();
                break;
                
            case 'validate_categories':
                $this->validate_categories();
                break;
                
            default:
                wp_send_json_error(__('Unknown action.', 'tutor-lms-academic-pro'));
        }
    }
    
    private function migrate_existing_users() {
        $options = get_option('tlms_academic_pro_settings');
        $default_category = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
        
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'tlms_education_type',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $migrated = 0;
        foreach ($users as $user) {
            update_user_meta($user->ID, 'tlms_education_type', $default_category);
            $migrated++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Migrated %d users to default category.', 'tutor-lms-academic-pro'), $migrated),
            'migrated' => $migrated
        ));
    }
    
    private function clear_cache() {
        $cache_cleared = array();
        
        wp_cache_delete('tlms_categories_tree', 'tlms');
        $cache_cleared[] = __('Category tree cache', 'tutor-lms-academic-pro');
        
        wp_cache_delete('tlms_user_counts', 'tlms');
        $cache_cleared[] = __('User counts cache', 'tutor-lms-academic-pro');
        
        wp_send_json_success(array(
            'message' => __('Cache cleared successfully.', 'tutor-lms-academic-pro'),
            'cleared_items' => $cache_cleared
        ));
    }
    
    private function validate_categories() {
        $issues = array();
        
        $all_categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'hierarchical' => false
        ));
        
        foreach ($all_categories as $category) {
            $education_type = get_term_meta($category->term_id, 'education_type', true);
            if (!$education_type) {
                $issues[] = array(
                    'type' => 'missing_meta',
                    'category' => $category->name,
                    'message' => __('Category missing education type.', 'tutor-lms-academic-pro')
                );
            }
        }
        
        wp_send_json_success(array(
            'issues' => $issues,
            'total_categories' => count($all_categories)
        ));
    }
    
    public function export_settings() {
        check_ajax_referer('tlms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_tutor')) {
            wp_die(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        $export_data = array(
            'settings' => get_option('tlms_academic_pro_settings'),
            'categories' => $this->export_categories(),
            'export_date' => current_time('mysql'),
            'version' => TLMS_ACADEMIC_PRO_VERSION
        );
        
        $filename = 'tlms-academic-pro-settings-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    private function export_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'get' => 'all'
        ));
        
        $export_categories = array();
        
        foreach ($categories as $category) {
            $export_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent' => $category->parent,
                'education_type' => get_term_meta($category->term_id, 'education_type', true),
                'term_order' => $category->term_order
            );
        }
        
        return $export_categories;
    }
    
    public function bulk_assign_categories() {
        check_ajax_referer('tlms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_tutor')) {
            wp_send_json_error(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        $user_ids = isset($_POST['user_ids']) ? array_map('intval', $_POST['user_ids']) : array();
        $education_type = sanitize_text_field($_POST['education_type']);
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : array();
        
        if (empty($user_ids)) {
            wp_send_json_error(__('No users selected.', 'tutor-lms-academic-pro'));
        }
        
        $processed = 0;
        foreach ($user_ids as $user_id) {
            update_user_meta($user_id, 'tlms_education_type', $education_type);
            update_user_meta($user_id, 'tlms_academic_categories', $categories);
            $processed++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Updated %d users.', 'tutor-lms-academic-pro'), $processed),
            'processed' => $processed
        ));
    }
}

?>