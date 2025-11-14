<?php

class TLMS_Debug_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
    }
    
    public function add_debug_menu() {
        add_submenu_page(
            'tutor',
            __('Academic Pro Debug', 'tutor-lms-academic-pro'),
            __('Academic Pro Debug', 'tutor-lms-academic-pro'),
            'manage_options',
            'tlms-debug',
            array($this, 'debug_page')
        );
    }
    
    public function debug_page() {
        $logger = TLMS_Debug_Logger::instance();
        
        if (isset($_POST['clear_log'])) {
            $logger->clear_log();
            echo '<div class="notice notice-success"><p>' . __('Log cleared successfully!', 'tutor-lms-academic-pro') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tutor LMS Academic Pro - Debug Log', 'tutor-lms-academic-pro'); ?></h1>
            
            <div style="margin: 20px 0;">
                <form method="post">
                    <button type="submit" name="clear_log" class="button button-secondary">
                        <?php _e('Clear Log', 'tutor-lms-academic-pro'); ?>
                    </button>
                </form>
            </div>
            
            <div style="background: #f6f7f7; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px;">
                <h3><?php _e('Debug Log Content', 'tutor-lms-academic-pro'); ?></h3>
                <pre style="background: #1d2327; color: #f0f0f1; padding: 15px; border-radius: 4px; overflow: auto; max-height: 500px; white-space: pre-wrap;"><?php echo esc_html($logger->get_log_content()); ?></pre>
            </div>
            
            <div style="margin-top: 20px;">
                <h3><?php _e('Current Hooks Status', 'tutor-lms-academic-pro'); ?></h3>
                <ul>
                    <li><strong>tutor_after_student_registration_before_submit:</strong> <?php echo has_action('tutor_after_student_registration_before_submit') ? '✅ Registered' : '❌ Not registered'; ?></li>
                    <li><strong>tutor_after_instructor_registration_before_submit:</strong> <?php echo has_action('tutor_after_instructor_registration_before_submit') ? '✅ Registered' : '❌ Not registered'; ?></li>
                    <li><strong>tutor_student_registration_after_terms:</strong> <?php echo has_action('tutor_student_registration_after_terms') ? '✅ Registered' : '❌ Not registered'; ?></li>
                    <li><strong>tutor_instructor_registration_after_terms:</strong> <?php echo has_action('tutor_instructor_registration_after_terms') ? '✅ Registered' : '❌ Not registered'; ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}

?>