<?php

class TLMS_Integration_Handler {
    
    private static $instance = null;
    private $active_integrations = array();
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_integrations'));
        add_filter('tlms_course_visibility', array($this, 'apply_integration_filters'), 10, 2);
    }
    
    public function load_integrations() {
        $options = get_option('tlms_academic_pro_settings');
        if (!isset($options['enable_integrations']) || !$options['enable_integrations']) {
            return;
        }
        
        $this->detect_active_plugins();
        $this->initialize_integrations();
    }
    
    private function detect_active_plugins() {
        $possible_integrations = array(
            'restrict-content-pro' => array(
                'plugin' => 'restrict-content-pro/restrict-content-pro.php',
                'class' => 'TLMS_Restrict_Content_Pro_Integration'
            ),
            'paid-memberships-pro' => array(
                'plugin' => 'paid-memberships-pro/paid-memberships-pro.php',
                'class' => 'TLMS_Paid_Memberships_Pro_Integration'
            ),
            'woocommerce-subscriptions' => array(
                'plugin' => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
                'class' => 'TLMS_WooCommerce_Subscriptions_Integration'
            ),
            'zoom-integration' => array(
                'plugin' => 'tutor-pro/tutor-pro.php', // Assuming Zoom is in Tutor Pro
                'class' => 'TLMS_Zoom_Integration'
            ),
            'buddypress' => array(
                'plugin' => 'buddypress/bp-loader.php',
                'class' => 'TLMS_BuddyPress_Integration'
            )
        );
        
        foreach ($possible_integrations as $integration => $data) {
            if ($this->is_plugin_active($data['plugin'])) {
                $this->active_integrations[$integration] = $data;
            }
        }
    }
    
    private function is_plugin_active($plugin_path) {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        return is_plugin_active($plugin_path);
    }
    
    private function initialize_integrations() {
        foreach ($this->active_integrations as $integration => $data) {
            $integration_file = TLMS_ACADEMIC_PRO_PATH . 'integrations/' . $data['class'] . '.php';
            
            if (file_exists($integration_file)) {
                require_once $integration_file;
                $class_name = $data['class'];
                
                if (class_exists($class_name)) {
                    new $class_name();
                }
            }
        }
        
        do_action('tlms_integrations_loaded', $this->active_integrations);
    }
    
    public function apply_integration_filters($visibility, $course_id) {
        foreach ($this->active_integrations as $integration => $data) {
            $visibility = apply_filters("tlms_{$integration}_course_visibility", $visibility, $course_id);
        }
        
        return $visibility;
    }
    
    public function get_active_integrations() {
        return $this->active_integrations;
    }
    
    public function is_integration_active($integration) {
        return isset($this->active_integrations[$integration]);
    }
}

?>