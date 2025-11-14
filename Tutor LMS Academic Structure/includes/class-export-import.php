<?php

class TLMS_Export_Import {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_init', array($this, 'handle_export_import'));
        add_action('wp_ajax_tlms_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_tlms_import_data', array($this, 'ajax_import_data'));
    }
    
    public function handle_export_import() {
        if (!isset($_POST['tlms_export_action']) && !isset($_POST['tlms_import_action'])) {
            return;
        }
        
        if (!current_user_can('manage_tutor')) {
            wp_die(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        if (isset($_POST['tlms_export_action'])) {
            $this->process_export();
        }
        
        if (isset($_POST['tlms_import_action'])) {
            $this->process_import();
        }
    }
    
    private function process_export() {
        check_admin_referer('tlms_export_nonce');
        
        $export_type = sanitize_text_field($_POST['export_type']);
        
        switch ($export_type) {
            case 'settings':
                $this->export_settings();
                break;
                
            case 'categories':
                $this->export_categories();
                break;
                
            case 'user_assignments':
                $this->export_user_assignments();
                break;
                
            case 'full_backup':
                $this->export_full_backup();
                break;
        }
    }
    
    private function export_settings() {
        $settings = get_option('tlms_academic_pro_settings');
        $network_settings = is_multisite() ? get_site_option('tlms_network_settings') : array();
        
        $export_data = array(
            'type' => 'settings',
            'settings' => $settings,
            'network_settings' => $network_settings,
            'export_date' => current_time('mysql'),
            'version' => TLMS_ACADEMIC_PRO_VERSION
        );
        
        $this->send_json_download($export_data, 'tlms-settings-export.json');
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
        
        $export_data = array(
            'type' => 'categories',
            'categories' => $export_categories,
            'export_date' => current_time('mysql'),
            'version' => TLMS_ACADEMIC_PRO_VERSION
        );
        
        $this->send_json_download($export_data, 'tlms-categories-export.json');
    }
    
    private function export_user_assignments() {
        global $wpdb;
        
        $user_assignments = $wpdb->get_results("
            SELECT user_id, meta_key, meta_value 
            FROM {$wpdb->usermeta} 
            WHERE meta_key IN ('tlms_education_type', 'tlms_academic_categories')
        ", ARRAY_A);
        
        $export_data = array(
            'type' => 'user_assignments',
            'assignments' => $user_assignments,
            'export_date' => current_time('mysql'),
            'version' => TLMS_ACADEMIC_PRO_VERSION
        );
        
        $this->send_json_download($export_data, 'tlms-user-assignments-export.json');
    }
    
    private function export_full_backup() {
        $settings = get_option('tlms_academic_pro_settings');
        $network_settings = is_multisite() ? get_site_option('tlms_network_settings') : array();
        
        // Export categories
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'get' => 'all'
        ));
        
        $export_categories = array();
        foreach ($categories as $category) {
            $export_categories[] = array(
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent' => $category->parent,
                'education_type' => get_term_meta($category->term_id, 'education_type', true)
            );
        }
        
        // Export course assignments
        $course_assignments = get_posts(array(
            'post_type' => 'courses',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $export_courses = array();
        foreach ($course_assignments as $course_id) {
            $education_type = get_post_meta($course_id, '_tlms_education_type', true);
            $course_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
            
            if ($education_type || !empty($course_categories)) {
                $export_courses[] = array(
                    'course_id' => $course_id,
                    'education_type' => $education_type,
                    'categories' => $course_categories
                );
            }
        }
        
        $export_data = array(
            'type' => 'full_backup',
            'settings' => $settings,
            'network_settings' => $network_settings,
            'categories' => $export_categories,
            'course_assignments' => $export_courses,
            'export_date' => current_time('mysql'),
            'version' => TLMS_ACADEMIC_PRO_VERSION
        );
        
        $this->send_json_download($export_data, 'tlms-full-backup.json');
    }
    
    private function send_json_download($data, $filename) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    private function process_import() {
        check_admin_referer('tlms_import_nonce');
        
        if (empty($_FILES['import_file'])) {
            wp_die(__('Please select a file to import.', 'tutor-lms-academic-pro'));
        }
        
        $file = $_FILES['import_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('Error uploading file.', 'tutor-lms-academic-pro'));
        }
        
        $content = file_get_contents($file['tmp_name']);
        $import_data = json_decode($content, true);
        
        if (!$import_data) {
            wp_die(__('Invalid JSON file.', 'tutor-lms-academic-pro'));
        }
        
        $results = $this->import_data($import_data);
        
        // Show results
        add_action('admin_notices', function() use ($results) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>' . __('Import Results', 'tutor-lms-academic-pro') . '</strong></p>';
            echo '<ul>';
            foreach ($results as $result) {
                echo '<li>' . $result . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        });
    }
    
    private function import_data($import_data) {
        $results = array();
        
        // Import settings
        if (isset($import_data['settings'])) {
            update_option('tlms_academic_pro_settings', $import_data['settings']);
            $results[] = __('Settings imported successfully.', 'tutor-lms-academic-pro');
        }
        
        // Import network settings
        if (isset($import_data['network_settings']) && is_multisite()) {
            update_site_option('tlms_network_settings', $import_data['network_settings']);
            $results[] = __('Network settings imported successfully.', 'tutor-lms-academic-pro');
        }
        
        // Import categories
        if (isset($import_data['categories']) && is_array($import_data['categories'])) {
            $imported = $this->import_categories_data($import_data['categories']);
            $results[] = sprintf(__('Imported %d categories.', 'tutor-lms-academic-pro'), $imported);
        }
        
        // Import course assignments
        if (isset($import_data['course_assignments']) && is_array($import_data['course_assignments'])) {
            $imported = $this->import_course_assignments($import_data['course_assignments']);
            $results[] = sprintf(__('Updated %d course assignments.', 'tutor-lms-academic-pro'), $imported);
        }
        
        // Import user assignments
        if (isset($import_data['assignments']) && is_array($import_data['assignments'])) {
            $imported = $this->import_user_assignments($import_data['assignments']);
            $results[] = sprintf(__('Updated %d user assignments.', 'tutor-lms-academic-pro'), $imported);
        }
        
        return $results;
    }
    
    private function import_categories_data($categories) {
        $imported = 0;
        
        foreach ($categories as $category_data) {
            // Check if category already exists
            $existing_term = get_term_by('slug', $category_data['slug'], 'tlms_academic_category');
            
            if ($existing_term) {
                // Update existing term
                wp_update_term($existing_term->term_id, 'tlms_academic_category', array(
                    'name' => $category_data['name'],
                    'description' => $category_data['description'],
                    'parent' => $category_data['parent']
                ));
                $term_id = $existing_term->term_id;
            } else {
                // Create new term
                $term = wp_insert_term(
                    $category_data['name'],
                    'tlms_academic_category',
                    array(
                        'description' => $category_data['description'],
                        'slug' => $category_data['slug'],
                        'parent' => $category_data['parent']
                    )
                );
                
                if (is_wp_error($term)) {
                    continue;
                }
                
                $term_id = $term['term_id'];
            }
            
            // Update education type
            if (isset($category_data['education_type'])) {
                update_term_meta($term_id, 'education_type', $category_data['education_type']);
            }
            
            $imported++;
        }
        
        return $imported;
    }
    
    private function import_course_assignments($course_assignments) {
        $imported = 0;
        
        foreach ($course_assignments as $course_data) {
            $course_id = $course_data['course_id'];
            
            if (get_post_type($course_id) !== 'courses') {
                continue;
            }
            
            // Update education type
            if (isset($course_data['education_type'])) {
                update_post_meta($course_id, '_tlms_education_type', $course_data['education_type']);
            }
            
            // Update categories
            if (isset($course_data['categories']) && is_array($course_data['categories'])) {
                wp_set_post_terms($course_id, $course_data['categories'], 'tlms_academic_category');
            }
            
            $imported++;
        }
        
        return $imported;
    }
    
    private function import_user_assignments($user_assignments) {
        $imported = 0;
        
        foreach ($user_assignments as $assignment) {
            $user_id = $assignment['user_id'];
            $meta_key = $assignment['meta_key'];
            $meta_value = maybe_unserialize($assignment['meta_value']);
            
            if (get_userdata($user_id)) {
                update_user_meta($user_id, $meta_key, $meta_value);
                $imported++;
            }
        }
        
        return $imported;
    }
    
    public function ajax_export_data() {
        check_ajax_referer('tlms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_tutor')) {
            wp_send_json_error(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        $export_type = sanitize_text_field($_POST['export_type']);
        
        switch ($export_type) {
            case 'settings':
                $this->export_settings();
                break;
                
            case 'categories':
                $this->export_categories();
                break;
                
            default:
                wp_send_json_error(__('Invalid export type.', 'tutor-lms-academic-pro'));
        }
    }
    
    public function ajax_import_data() {
        check_ajax_referer('tlms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_tutor')) {
            wp_send_json_error(__('Insufficient permissions.', 'tutor-lms-academic-pro'));
        }
        
        // This would handle AJAX file upload and import
        // Implementation depends on frontend requirements
        wp_send_json_success(array(
            'message' => __('Import functionality would be implemented here.', 'tutor-lms-academic-pro')
        ));
    }
}

?>