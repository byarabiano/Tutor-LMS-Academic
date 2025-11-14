<?php
/**
 * Plugin Name: Tutor LMS Academic Pro
 * Plugin URI: https://yourwebsite.com
 * Description: Advanced academic structure for Tutor LMS with multi-level categories and content isolation
 * Version: 1.0.0
 * Author: byarabiano
 * Text Domain: tutor-lms-academic-pro
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// منع الوصول المباشر
if (!defined('ABSPATH')) {
    exit;
}

// ثوابت الإضافة
define('TLMS_ACADEMIC_PRO_VERSION', '1.0.0');
define('TLMS_ACADEMIC_PRO_FILE', __FILE__);
define('TLMS_ACADEMIC_PRO_PATH', plugin_dir_path(__FILE__));
define('TLMS_ACADEMIC_PRO_URL', plugin_dir_url(__FILE__));

// تفعيل التصحيح - يمكن تعطيله لاحقاً
define('TLMS_DEBUG', true);

// التحقق من وجود Tutor LMS
register_activation_hook(__FILE__, 'tlms_academic_pro_check_dependencies');

function tlms_academic_pro_check_dependencies() {
    if (!class_exists('TUTOR\\Tutor')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Tutor LMS Academic Pro requires Tutor LMS to be installed and activated.', 'tutor-lms-academic-pro'));
    }
}

// الفئة الرئيسية للإضافة
class TutorLMS_Academic_Pro {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // نظام التصحيح أولاً
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-debug-logger.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-hook-detector.php';
        
        // باقي الملفات
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-activation.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-custom-taxonomies.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-admin-settings.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-user-registration.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-course-visibility.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-multisite-support.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-integration-handler.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-ajax-handler.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-export-import.php';
        require_once TLMS_ACADEMIC_PRO_PATH . 'includes/class-compatibility.php';
        
        if (is_admin()) {
            require_once TLMS_ACADEMIC_PRO_PATH . 'admin/class-debug-admin.php';
        }
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_plugin'), 5);
        
        // دعم Multisite
        if (is_multisite()) {
            add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        }
        
        // إضافة فلتر التصنيفات
        add_action('restrict_manage_posts', array($this, 'add_academic_category_filter'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('tutor-lms-academic-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function init_plugin() {
        tlms_log('Starting plugin initialization');
        
        // تهيئة جميع المكونات
        TLMS_Activation::init();
        TLMS_Custom_Taxonomies::instance();
        TLMS_Admin_Settings::instance();
        TLMS_User_Registration::instance();
        TLMS_Course_Visibility::instance();
        TLMS_Multisite_Support::instance();
        TLMS_Integration_Handler::instance();
        TLMS_Ajax_Handler::instance();
        TLMS_Export_Import::instance();
        TLMS_Compatibility::instance();
        TLMS_Hook_Detector::instance();
        
        if (is_admin()) {
            TLMS_Debug_Admin::instance();
        }
        
        tlms_log('Plugin initialization completed');
    }
    
    public function add_academic_category_filter() {
        global $typenow;
        
        if ($typenow === 'courses') {
            $taxonomy = 'tlms_academic_category';
            $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
            
            $args = array(
                'show_option_all' => __('All Academic Categories', 'tutor-lms-academic-pro'),
                'taxonomy' => $taxonomy,
                'name' => $taxonomy,
                'selected' => $selected,
                'show_count' => true,
                'hide_empty' => false,
                'value_field' => 'term_id',
                'orderby' => 'name',
                'hierarchical' => true
            );
            
            wp_dropdown_categories($args);
        }
    }
    
    public function add_network_admin_menu() {
        // قائمة إدارة الشبكة لـ Multisite
    }
}

// تهيئة الإضافة
function tutor_lms_academic_pro() {
    return TutorLMS_Academic_Pro::instance();
}

// بدء الإضافة
add_action('plugins_loaded', 'tutor_lms_academic_pro');

// إضافة فلتر التصنيفات - مصححة
function tlms_add_academic_category_filter_to_courses() {
    $plugin = tutor_lms_academic_pro();
    if (method_exists($plugin, 'add_academic_category_filter')) {
        $plugin->add_academic_category_filter();
    }
}
add_action('restrict_manage_posts', 'tlms_add_academic_category_filter_to_courses');

// ✅ معالج آمن لإلغاء التفعيل
register_deactivation_hook(__FILE__, 'tlms_safe_deactivation_handler');

function tlms_safe_deactivation_handler() {
    try {
        tlms_log('Safe deactivation handler started');
        
        // التحقق من وجود الدالة قبل استدعائها
        if (class_exists('TLMS_Activation') && method_exists('TLMS_Activation', 'deactivate')) {
            TLMS_Activation::deactivate();
        } else {
            // تنظيف أساسي إذا لم تكن الدالة موجودة
            wp_clear_scheduled_hook('tlms_daily_cleanup');
            flush_rewrite_rules();
            tlms_log('Used fallback deactivation method');
        }
        
        tlms_log('Safe deactivation completed');
    } catch (Exception $e) {
        // منع فشل عملية إلغاء التفعيل حتى مع وجود أخطاء
        error_log('Tutor LMS Academic Pro safe deactivation completed with errors: ' . $e->getMessage());
    }
}

// ✅ نظام تحميل آمن للملفات
function tlms_safe_require($file_path) {
    $full_path = TLMS_ACADEMIC_PRO_PATH . $file_path;
    if (file_exists($full_path)) {
        require_once $full_path;
        return true;
    } else {
        tlms_log('File not found: ' . $file_path, 'WARNING');
        return false;
    }
}
?>