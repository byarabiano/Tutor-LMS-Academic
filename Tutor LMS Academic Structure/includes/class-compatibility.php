<?php

class TLMS_Compatibility {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'check_compatibility'));
        add_action('admin_notices', array($this, 'display_compatibility_notices'));
        add_filter('tlms_feature_support', array($this, 'check_feature_support'), 10, 2);
    }
    
    public function check_compatibility() {
        $compatibility = array(
            'php' => $this->check_php_version(),
            'wordpress' => $this->check_wordpress_version(),
            'tutor_lms' => $this->check_tutor_lms_version(),
            'multisite' => $this->check_multisite(),
            'memory_limit' => $this->check_memory_limit()
        );
        
        update_option('tlms_compatibility_report', $compatibility);
        
        return $compatibility;
    }
    
    public function check_php_version() {
        $required = '7.4';
        $current = PHP_VERSION;
        
        return array(
            'required' => $required,
            'current' => $current,
            'compatible' => version_compare($current, $required, '>=')
        );
    }
    
    public function check_wordpress_version() {
        $required = '5.6';
        $current = get_bloginfo('version');
        
        return array(
            'required' => $required,
            'current' => $current,
            'compatible' => version_compare($current, $required, '>=')
        );
    }
    
    public function check_tutor_lms_version() {
        if (!class_exists('TUTOR\Tutor')) {
            return array(
                'installed' => false,
                'compatible' => false
            );
        }
        
        $required = '2.0.0';
        
        if (defined('TUTOR_VERSION')) {
            $current = TUTOR_VERSION;
        } else {
            $current = '1.0.0'; // Fallback
        }
        
        return array(
            'installed' => true,
            'required' => $required,
            'current' => $current,
            'compatible' => version_compare($current, $required, '>=')
        );
    }
    
    public function check_multisite() {
        return array(
            'enabled' => is_multisite(),
            'compatible' => true // Multisite is fully supported
        );
    }
    
    public function check_memory_limit() {
        $memory_limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        $recommended = 134217728; // 128MB in bytes
        
        return array(
            'current' => size_format($memory_limit),
            'recommended' => '128MB',
            'sufficient' => $memory_limit >= $recommended
        );
    }
    
    public function display_compatibility_notices() {
        $compatibility = get_option('tlms_compatibility_report', array());
        
        if (empty($compatibility)) {
            return;
        }
        
        $messages = array();
        
        // Check PHP version
        if (isset($compatibility['php']) && !$compatibility['php']['compatible']) {
            $messages[] = sprintf(
                __('Tutor LMS Academic Pro requires PHP version %s or higher. Your current version is %s.', 'tutor-lms-academic-pro'),
                $compatibility['php']['required'],
                $compatibility['php']['current']
            );
        }
        
        // Check WordPress version
        if (isset($compatibility['wordpress']) && !$compatibility['wordpress']['compatible']) {
            $messages[] = sprintf(
                __('Tutor LMS Academic Pro requires WordPress version %s or higher. Your current version is %s.', 'tutor-lms-academic-pro'),
                $compatibility['wordpress']['required'],
                $compatibility['wordpress']['current']
            );
        }
        
        // Check Tutor LMS
        if (isset($compatibility['tutor_lms'])) {
            if (!$compatibility['tutor_lms']['installed']) {
                $messages[] = __('Tutor LMS Academic Pro requires Tutor LMS to be installed and activated.', 'tutor-lms-academic-pro');
            } elseif (!$compatibility['tutor_lms']['compatible']) {
                $messages[] = sprintf(
                    __('Tutor LMS Academic Pro requires Tutor LMS version %s or higher. Your current version is %s.', 'tutor-lms-academic-pro'),
                    $compatibility['tutor_lms']['required'],
                    $compatibility['tutor_lms']['current']
                );
            }
        }
        
        // Check memory limit
        if (isset($compatibility['memory_limit']) && !$compatibility['memory_limit']['sufficient']) {
            $messages[] = sprintf(
                __('For optimal performance, we recommend increasing your memory limit to %s. Your current limit is %s.', 'tutor-lms-academic-pro'),
                $compatibility['memory_limit']['recommended'],
                $compatibility['memory_limit']['current']
            );
        }
        
        if (!empty($messages)) {
            echo '<div class="notice notice-warning">';
            echo '<h3>' . __('Compatibility Issues Detected', 'tutor-lms-academic-pro') . '</h3>';
            echo '<ul>';
            foreach ($messages as $message) {
                echo '<li>' . $message . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
    
    public function check_feature_support($supported, $feature) {
        switch ($feature) {
            case 'advanced_categories':
                return $this->check_php_version()['compatible'] && $this->check_memory_limit()['sufficient'];
                
            case 'multisite_network':
                return $this->check_multisite()['enabled'];
                
            case 'import_export':
                return $this->check_php_version()['compatible'] && function_exists('json_encode');
                
            default:
                return $supported;
        }
    }
    
    public function get_system_info() {
        global $wpdb;
        
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'tutor_lms_version' => defined('TUTOR_VERSION') ? TUTOR_VERSION : 'Not Installed',
            'mysql_version' => $wpdb->db_version(),
            'memory_limit' => WP_MEMORY_LIMIT,
            'multisite' => is_multisite(),
            'active_plugins' => get_option('active_plugins', array()),
            'theme' => get_stylesheet()
        );
    }
}

?>