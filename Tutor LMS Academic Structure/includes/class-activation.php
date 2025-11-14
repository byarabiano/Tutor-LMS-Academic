<?php

class TLMS_Activation {
    
    public static function init() {
        register_activation_hook(TLMS_ACADEMIC_PRO_FILE, array(__CLASS__, 'activate'));
        register_deactivation_hook(TLMS_ACADEMIC_PRO_FILE, array(__CLASS__, 'deactivate'));
        register_uninstall_hook(TLMS_ACADEMIC_PRO_FILE, array(__CLASS__, 'uninstall'));
    }
    
    public static function activate($network_wide) {
        if (is_multisite() && $network_wide) {
            self::activate_multisite($network_wide);
        } else {
            self::activate_single_site();
        }
    }
    
    private static function activate_single_site() {
        // Set default options
        $default_options = array(
            'enabled' => true,
            'max_levels' => 5,
            'education_types' => array('university', 'school', 'general'),
            'default_user_category' => 'general',
            'isolation_enabled' => true
        );
        
        update_option('tlms_academic_pro_settings', $default_options);
        
        // إنشاء تصنيفات مسبقة
        self::create_sample_categories();
        
        // Flush rewrite rules for custom taxonomies
        flush_rewrite_rules();
    }
    
    private static function create_sample_categories() {
        // تصنيفات الجامعات
        $university_categories = array(
            'جامعة القاهرة' => array(
                'كلية الهندسة' => array(
                    'هندسة مدنية' => array(),
                    'هندسة كهربائية' => array(),
                    'هندسة ميكانيكية' => array()
                ),
                'كلية الطب' => array(
                    'طب عام' => array(),
                    'طب أسنان' => array(),
                    'صيدلة' => array()
                )
            ),
            'جامعة عين شمس' => array(
                'كلية التجارة' => array(
                    'محاسبة' => array(),
                    'إدارة أعمال' => array(),
                    'تسويق' => array()
                )
            )
        );
        
        // تصنيفات المدارس
        $school_categories = array(
            'حكومية' => array(
                'ابتدائية' => array(
                    'الصف الأول' => array(),
                    'الصف الثاني' => array(),
                    'الصف الثالث' => array()
                ),
                'إعدادية' => array(
                    'الصف الأول الإعدادي' => array(),
                    'الصف الثاني الإعدادي' => array(),
                    'الصف الثالث الإعدادي' => array()
                ),
                'ثانوية' => array(
                    'الصف الأول الثانوي' => array(),
                    'الصف الثاني الثانوي' => array(),
                    'الصف الثالث الثانوي' => array()
                )
            ),
            'خاصة' => array(
                'لغات' => array(),
                'دولية' => array()
            )
        );
        
        // إنشاء تصنيفات الجامعات
        foreach ($university_categories as $university => $colleges) {
            $univ_term = wp_insert_term($university, 'tlms_academic_category', array('slug' => sanitize_title($university)));
            
            if (!is_wp_error($univ_term)) {
                update_term_meta($univ_term['term_id'], 'education_type', 'university');
                
                foreach ($colleges as $college => $departments) {
                    $college_term = wp_insert_term($college, 'tlms_academic_category', array(
                        'slug' => sanitize_title($college),
                        'parent' => $univ_term['term_id']
                    ));
                    
                    if (!is_wp_error($college_term)) {
                        update_term_meta($college_term['term_id'], 'education_type', 'university');
                        
                        foreach ($departments as $department => $programs) {
                            $dept_term = wp_insert_term($department, 'tlms_academic_category', array(
                                'slug' => sanitize_title($department),
                                'parent' => $college_term['term_id']
                            ));
                            
                            if (!is_wp_error($dept_term)) {
                                update_term_meta($dept_term['term_id'], 'education_type', 'university');
                            }
                        }
                    }
                }
            }
        }
        
        // إنشاء تصنيفات المدارس
        foreach ($school_categories as $school_type => $levels) {
            $type_term = wp_insert_term($school_type, 'tlms_academic_category', array('slug' => sanitize_title($school_type)));
            
            if (!is_wp_error($type_term)) {
                update_term_meta($type_term['term_id'], 'education_type', 'school');
                
                foreach ($levels as $level => $grades) {
                    $level_term = wp_insert_term($level, 'tlms_academic_category', array(
                        'slug' => sanitize_title($level),
                        'parent' => $type_term['term_id']
                    ));
                    
                    if (!is_wp_error($level_term)) {
                        update_term_meta($level_term['term_id'], 'education_type', 'school');
                        
                        foreach ($grades as $grade => $empty) {
                            $grade_term = wp_insert_term($grade, 'tlms_academic_category', array(
                                'slug' => sanitize_title($grade),
                                'parent' => $level_term['term_id']
                            ));
                            
                            if (!is_wp_error($grade_term)) {
                                update_term_meta($grade_term['term_id'], 'education_type', 'school');
                            }
                        }
                    }
                }
            }
        }
    }
    
    private static function activate_multisite($network_wide) {
        global $wpdb;
        
        if ($network_wide) {
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::activate_single_site();
                restore_current_blog();
            }
        }
    }
    
    // ✅ الإصلاح: إضافة دالة deactivate المفقودة
    public static function deactivate() {
        tlms_log('Plugin deactivation started');
        
        try {
            // تنظيف الجدول الزمني
            wp_clear_scheduled_hook('tlms_daily_cleanup');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            tlms_log('Plugin deactivation completed successfully');
        } catch (Exception $e) {
            tlms_log('Deactivation error: ' . $e->getMessage(), 'ERROR');
        }
    }
    
    public static function uninstall() {
        if (!current_user_can('delete_plugins')) {
            return;
        }
        
        // Clean up options
        delete_option('tlms_academic_pro_settings');
        delete_option('tlms_academic_pro_version');
        
        // Multisite cleanup
        if (is_multisite()) {
            global $wpdb;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                delete_option('tlms_academic_pro_settings');
                delete_option('tlms_academic_pro_version');
                restore_current_blog();
            }
        }
    }
}

?>