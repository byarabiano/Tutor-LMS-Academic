<?php

class TLMS_Debug_Logger {
    
    private static $instance = null;
    private $log_file;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/tutor-lms-academic-pro-debug.log';
        
        add_action('init', array($this, 'check_debug_mode'));
    }
    
    public function check_debug_mode() {
        if (isset($_GET['tlms_debug']) && $_GET['tlms_debug'] == '1') {
            $this->log('Debug mode activated via URL parameter');
        }
    }
    
    public function log($message, $type = 'INFO') {
        if (!defined('TLMS_DEBUG') || !TLMS_DEBUG) {
            return;
        }
        
        $timestamp = current_time('mysql');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
        
        $log_message = "[$timestamp] [$type] [$caller] $message" . PHP_EOL;
        
        file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
    
    public function log_hook($hook_name, $extra = '') {
        $this->log("Hook fired: $hook_name" . ($extra ? " - $extra" : ''));
    }
    
    public function log_function_call($function_name, $args = array()) {
        $args_str = !empty($args) ? json_encode($args) : 'no args';
        $this->log("Function called: $function_name with $args_str");
    }
    
    public function get_log_content() {
        if (file_exists($this->log_file)) {
            return file_get_contents($this->log_file);
        }
        return 'No log file found.';
    }
    
    public function clear_log() {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }
}

// دالة مساعدة للاستخدام السريع
function tlms_log($message, $type = 'INFO') {
    $logger = TLMS_Debug_Logger::instance();
    $logger->log($message, $type);
}

// دالة مساعدة لتسجيل الـ hooks
function tlms_log_hook($hook_name, $extra = '') {
    $logger = TLMS_Debug_Logger::instance();
    $logger->log_hook($hook_name, $extra);
}

?>