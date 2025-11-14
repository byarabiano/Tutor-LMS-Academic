<?php

class TLMS_Hook_Detector {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('all', array($this, 'log_all_hooks'));
        add_action('wp_footer', array($this, 'display_active_hooks'));
    }
    
    public function log_all_hooks($hook) {
        // تسجيل فقط الـ hooks المتعلقة بالتسجيل
        if (strpos($hook, 'tutor') !== false || 
            strpos($hook, 'register') !== false || 
            strpos($hook, 'student') !== false || 
            strpos($hook, 'instructor') !== false) {
            tlms_log("Hook fired: $hook");
        }
    }
    
    public function display_active_hooks() {
        if (!current_user_can('manage_options') || !isset($_GET['tlms_debug_hooks'])) {
            return;
        }
        
        global $wp_filter;
        $tutor_hooks = array();
        
        // جمع جميع الـ hooks المتعلقة بـ Tutor LMS
        foreach ($wp_filter as $hook_name => $hook_obj) {
            if (strpos($hook_name, 'tutor') !== false || 
                strpos($hook_name, 'register') !== false) {
                $tutor_hooks[] = $hook_name;
            }
        }
        
        sort($tutor_hooks);
        ?>
        <div style="background: #f1f1f1; border: 2px solid #0073aa; padding: 20px; margin: 20px 0; font-family: monospace;">
            <h3>🎯 Tutor LMS Hooks Detector</h3>
            <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>Total Tutor Hooks Found:</strong> <?php echo count($tutor_hooks); ?></p>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($tutor_hooks as $hook): ?>
                    <div style="padding: 5px; border-bottom: 1px solid #ddd;">
                        <strong><?php echo $hook; ?></strong>
                        <?php 
                        $callbacks = array();
                        if (isset($wp_filter[$hook])) {
                            foreach ($wp_filter[$hook]->callbacks as $priority => $functions) {
                                foreach ($functions as $func) {
                                    if (is_array($func['function'])) {
                                        $callbacks[] = get_class($func['function'][0]) . '->' . $func['function'][1] . " (priority: $priority)";
                                    } else {
                                        $callbacks[] = $func['function'] . " (priority: $priority)";
                                    }
                                }
                            }
                        }
                        if (!empty($callbacks)) {
                            echo '<br><small style="color: #666;">Callbacks: ' . implode(', ', $callbacks) . '</small>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}

?>