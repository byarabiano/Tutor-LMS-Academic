<?php

class TLMS_Admin_Settings {
    
    private static $instance = null;
    private $settings_page;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        $this->settings_page = add_submenu_page(
            'tutor',
            __('Academic Pro Settings', 'tutor-lms-academic-pro'),
            __('Academic Pro Settings', 'tutor-lms-academic-pro'),
            'manage_tutor',
            'tlms-academic-pro-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('tlms_academic_pro_settings', 'tlms_academic_pro_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'tlms_general_settings',
            __('General Settings', 'tutor-lms-academic-pro'),
            array($this, 'general_settings_section_callback'),
            'tlms-academic-pro-settings'
        );
        
        add_settings_field(
            'enabled',
            __('Enable Academic Structure', 'tutor-lms-academic-pro'),
            array($this, 'enabled_field_callback'),
            'tlms-academic-pro-settings',
            'tlms_general_settings'
        );
        
        add_settings_field(
            'max_levels',
            __('Maximum Category Levels', 'tutor-lms-academic-pro'),
            array($this, 'max_levels_field_callback'),
            'tlms-academic-pro-settings',
            'tlms_general_settings'
        );
        
        // Education Types Section
        add_settings_section(
            'tlms_education_types',
            __('Education Types', 'tutor-lms-academic-pro'),
            array($this, 'education_types_section_callback'),
            'tlms-academic-pro-settings'
        );
        
        add_settings_field(
            'education_types',
            __('Available Education Types', 'tutor-lms-academic-pro'),
            array($this, 'education_types_field_callback'),
            'tlms-academic-pro-settings',
            'tlms_education_types'
        );
        
        // User Management Section
        add_settings_section(
            'tlms_user_management',
            __('User Management', 'tutor-lms-academic-pro'),
            array($this, 'user_management_section_callback'),
            'tlms-academic-pro-settings'
        );
        
        add_settings_field(
            'default_user_category',
            __('Default Category for Existing Users', 'tutor-lms-academic-pro'),
            array($this, 'default_user_category_field_callback'),
            'tlms-academic-pro-settings',
            'tlms_user_management'
        );
        
        // Integration Section
        add_settings_section(
            'tlms_integrations',
            __('Plugin Integrations', 'tutor-lms-academic-pro'),
            array($this, 'integrations_section_callback'),
            'tlms-academic-pro-settings'
        );
        
        add_settings_field(
            'enable_integrations',
            __('Enable Integrations', 'tutor-lms-academic-pro'),
            array($this, 'enable_integrations_field_callback'),
            'tlms-academic-pro-settings',
            'tlms_integrations'
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== $this->settings_page) {
            return;
        }
        
        wp_enqueue_script('tlms-admin-settings', TLMS_ACADEMIC_PRO_URL . 'admin/js/admin.js', array('jquery'), TLMS_ACADEMIC_PRO_VERSION, true);
        wp_enqueue_style('tlms-admin-style', TLMS_ACADEMIC_PRO_URL . 'admin/css/admin.css', array(), TLMS_ACADEMIC_PRO_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('tlms-admin-settings', 'tlms_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tlms_admin_nonce')
        ));
    }
    
    public function general_settings_section_callback() {
        echo '<p>' . __('Configure the general settings for the academic structure.', 'tutor-lms-academic-pro') . '</p>';
    }
    
    public function enabled_field_callback() {
        $options = get_option('tlms_academic_pro_settings');
        $enabled = isset($options['enabled']) ? $options['enabled'] : true;
        ?>
        <input type="checkbox" name="tlms_academic_pro_settings[enabled]" value="1" <?php checked(1, $enabled); ?> />
        <p class="description"><?php _e('Enable or disable the academic structure system.', 'tutor-lms-academic-pro'); ?></p>
        <?php
    }
    
    public function max_levels_field_callback() {
        $options = get_option('tlms_academic_pro_settings');
        $max_levels = isset($options['max_levels']) ? $options['max_levels'] : 5;
        ?>
        <select name="tlms_academic_pro_settings[max_levels]">
            <?php for ($i = 3; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php selected($max_levels, $i); ?>><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
        <p class="description"><?php _e('Maximum number of category levels in the academic hierarchy.', 'tutor-lms-academic-pro'); ?></p>
        <?php
    }
    
    public function education_types_section_callback() {
        echo '<p>' . __('Select which education types are available for users.', 'tutor-lms-academic-pro') . '</p>';
    }
    
    public function education_types_field_callback() {
        $options = get_option('tlms_academic_pro_settings');
        $education_types = isset($options['education_types']) ? $options['education_types'] : array('university', 'school', 'general');
        $all_types = array(
            'university' => __('Universities', 'tutor-lms-academic-pro'),
            'school' => __('Schools', 'tutor-lms-academic-pro'),
            'general' => __('General Courses', 'tutor-lms-academic-pro')
        );
        ?>
        <?php foreach ($all_types as $key => $label): ?>
            <label>
                <input type="checkbox" name="tlms_academic_pro_settings[education_types][]" 
                       value="<?php echo $key; ?>" <?php checked(in_array($key, $education_types)); ?> />
                <?php echo $label; ?>
            </label><br>
        <?php endforeach; ?>
        <p class="description"><?php _e('Select which education types users can choose during registration.', 'tutor-lms-academic-pro'); ?></p>
        <?php
    }
    
    public function user_management_section_callback() {
        echo '<p>' . __('Settings for user management and existing users.', 'tutor-lms-academic-pro') . '</p>';
    }
    
    public function default_user_category_field_callback() {
        $options = get_option('tlms_academic_pro_settings');
        $default_category = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
        ?>
        <select name="tlms_academic_pro_settings[default_user_category]">
            <option value="general" <?php selected($default_category, 'general'); ?>><?php _e('General Courses', 'tutor-lms-academic-pro'); ?></option>
            <option value="university" <?php selected($default_category, 'university'); ?>><?php _e('University Category', 'tutor-lms-academic-pro'); ?></option>
            <option value="school" <?php selected($default_category, 'school'); ?>><?php _e('School Category', 'tutor-lms-academic-pro'); ?></option>
        </select>
        <p class="description"><?php _e('Default category for users registered before activating this plugin.', 'tutor-lms-academic-pro'); ?></p>
        <?php
    }
    
    public function integrations_section_callback() {
        echo '<p>' . __('Manage integrations with other plugins.', 'tutor-lms-academic-pro') . '</p>';
    }
    
    public function enable_integrations_field_callback() {
        $options = get_option('tlms_academic_pro_settings');
        $enable_integrations = isset($options['enable_integrations']) ? $options['enable_integrations'] : true;
        ?>
        <input type="checkbox" name="tlms_academic_pro_settings[enable_integrations]" value="1" <?php checked(1, $enable_integrations); ?> />
        <p class="description"><?php _e('Automatically detect and integrate with compatible plugins.', 'tutor-lms-academic-pro'); ?></p>
        <?php
    }
    
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        // Sanitize general settings
        $sanitized_input['enabled'] = isset($input['enabled']) ? (bool) $input['enabled'] : false;
        $sanitized_input['max_levels'] = isset($input['max_levels']) ? intval($input['max_levels']) : 5;
        $sanitized_input['enable_integrations'] = isset($input['enable_integrations']) ? (bool) $input['enable_integrations'] : false;
        $sanitized_input['default_user_category'] = sanitize_text_field($input['default_user_category']);
        
        // Sanitize education types
        $sanitized_input['education_types'] = array();
        if (isset($input['education_types']) && is_array($input['education_types'])) {
            foreach ($input['education_types'] as $type) {
                $sanitized_input['education_types'][] = sanitize_text_field($type);
            }
        }
        
        return $sanitized_input;
    }
    
    public function settings_page() {
        if (!current_user_can('manage_tutor')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'tutor-lms-academic-pro'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="tlms-settings-container">
                <div class="tlms-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('tlms_academic_pro_settings');
                        do_settings_sections('tlms-academic-pro-settings');
                        submit_button(__('Save Settings', 'tutor-lms-academic-pro'));
                        ?>
                    </form>
                </div>
                
                <div class="tlms-settings-sidebar">
                    <div class="tlms-info-box">
                        <h3><?php _e('Quick Actions', 'tutor-lms-academic-pro'); ?></h3>
                        <p>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=tlms_academic_category'); ?>" class="button button-primary">
                                <?php _e('Manage Academic Categories', 'tutor-lms-academic-pro'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('users.php'); ?>" class="button">
                                <?php _e('Manage User Categories', 'tutor-lms-academic-pro'); ?>
                            </a>
                        </p>
                    </div>
                    
                    <div class="tlms-info-box">
                        <h3><?php _e('Export/Import', 'tutor-lms-academic-pro'); ?></h3>
                        <p>
                            <button type="button" id="tlms-export-settings" class="button"><?php _e('Export Settings', 'tutor-lms-academic-pro'); ?></button>
                            <button type="button" id="tlms-import-settings" class="button"><?php _e('Import Settings', 'tutor-lms-academic-pro'); ?></button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

?>