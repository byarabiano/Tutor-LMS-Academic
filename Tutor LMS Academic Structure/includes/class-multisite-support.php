<?php

class TLMS_Multisite_Support {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        if (!is_multisite()) {
            return;
        }
        
        add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
        add_action('network_admin_edit_tlms_network_settings', array($this, 'save_network_settings'));
        add_action('wp_initialize_site', array($this, 'initialize_new_site'), 10, 2);
        add_filter('network_admin_plugin_action_links', array($this, 'add_network_plugin_links'), 10, 2);
    }
    
    public function add_network_admin_menu() {
        add_menu_page(
            __('Tutor LMS Academic Pro', 'tutor-lms-academic-pro'),
            __('Tutor Academic Pro', 'tutor-lms-academic-pro'),
            'manage_network_options',
            'tlms-network-settings',
            array($this, 'network_settings_page'),
            'dashicons-welcome-learn-more',
            100
        );
    }
    
    public function network_settings_page() {
        $network_settings = get_site_option('tlms_network_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Tutor LMS Academic Pro - Network Settings', 'tutor-lms-academic-pro'); ?></h1>
            
            <form method="post" action="edit.php?action=tlms_network_settings">
                <?php wp_nonce_field('tlms_network_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_enabled"><?php _e('Enable by Default', 'tutor-lms-academic-pro'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="tlms_network_settings[default_enabled]" id="default_enabled" value="1" 
                                <?php checked(isset($network_settings['default_enabled']) ? $network_settings['default_enabled'] : true); ?> />
                            <p class="description">
                                <?php _e('Enable Tutor LMS Academic Pro by default for new sites.', 'tutor-lms-academic-pro'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="allow_site_settings"><?php _e('Allow Site Settings', 'tutor-lms-academic-pro'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="tlms_network_settings[allow_site_settings]" id="allow_site_settings" value="1" 
                                <?php checked(isset($network_settings['allow_site_settings']) ? $network_settings['allow_site_settings'] : true); ?> />
                            <p class="description">
                                <?php _e('Allow individual sites to modify Academic Pro settings.', 'tutor-lms-academic-pro'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_education_types"><?php _e('Default Education Types', 'tutor-lms-academic-pro'); ?></label>
                        </th>
                        <td>
                            <?php
                            $default_types = isset($network_settings['default_education_types']) ? 
                                $network_settings['default_education_types'] : 
                                array('university', 'school', 'general');
                            ?>
                            <label>
                                <input type="checkbox" name="tlms_network_settings[default_education_types][]" value="university" 
                                    <?php checked(in_array('university', $default_types)); ?> />
                                <?php _e('Universities', 'tutor-lms-academic-pro'); ?>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="tlms_network_settings[default_education_types][]" value="school" 
                                    <?php checked(in_array('school', $default_types)); ?> />
                                <?php _e('Schools', 'tutor-lms-academic-pro'); ?>
                            </label><br>
                            
                            <label>
                                <input type="checkbox" name="tlms_network_settings[default_education_types][]" value="general" 
                                    <?php checked(in_array('general', $default_types)); ?> />
                                <?php _e('General Courses', 'tutor-lms-academic-pro'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Network Settings', 'tutor-lms-academic-pro')); ?>
            </form>
            
            <div class="tlms-network-tools">
                <h2><?php _e('Network Tools', 'tutor-lms-academic-pro'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Site', 'tutor-lms-academic-pro'); ?></th>
                            <th><?php _e('Status', 'tutor-lms-academic-pro'); ?></th>
                            <th><?php _e('Actions', 'tutor-lms-academic-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sites = get_sites();
                        foreach ($sites as $site) {
                            switch_to_blog($site->blog_id);
                            $is_enabled = get_option('tlms_academic_pro_settings') ? 'Enabled' : 'Disabled';
                            restore_current_blog();
                            ?>
                            <tr>
                                <td><?php echo $site->blogname; ?> (<?php echo $site->domain; ?>)</td>
                                <td><?php echo $is_enabled; ?></td>
                                <td>
                                    <a href="<?php echo get_admin_url($site->blog_id, 'admin.php?page=tlms-academic-pro-settings'); ?>">
                                        <?php _e('Manage Settings', 'tutor-lms-academic-pro'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function save_network_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'tlms_network_settings_nonce')) {
            wp_die(__('Security check failed.', 'tutor-lms-academic-pro'));
        }
        
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have permission to manage network settings.', 'tutor-lms-academic-pro'));
        }
        
        $settings = array(
            'default_enabled' => isset($_POST['tlms_network_settings']['default_enabled']),
            'allow_site_settings' => isset($_POST['tlms_network_settings']['allow_site_settings']),
            'default_education_types' => isset($_POST['tlms_network_settings']['default_education_types']) ? 
                $_POST['tlms_network_settings']['default_education_types'] : array()
        );
        
        update_site_option('tlms_network_settings', $settings);
        
        wp_redirect(add_query_arg(array('page' => 'tlms-network-settings', 'updated' => 'true'), network_admin_url('admin.php')));
        exit;
    }
    
    public function initialize_new_site($site) {
        $network_settings = get_site_option('tlms_network_settings', array());
        
        if (isset($network_settings['default_enabled']) && $network_settings['default_enabled']) {
            switch_to_blog($site->blog_id);
            
            $default_settings = array(
                'enabled' => true,
                'max_levels' => 5,
                'education_types' => isset($network_settings['default_education_types']) ? 
                    $network_settings['default_education_types'] : array('university', 'school', 'general'),
                'default_user_category' => 'general',
                'enable_integrations' => true
            );
            
            update_option('tlms_academic_pro_settings', $default_settings);
            
            restore_current_blog();
        }
    }
    
    public function add_network_plugin_links($links, $plugin_file) {
        if (plugin_basename(TLMS_ACADEMIC_PRO_FILE) === $plugin_file) {
            $links[] = '<a href="' . network_admin_url('admin.php?page=tlms-network-settings') . '">' . 
                       __('Network Settings', 'tutor-lms-academic-pro') . '</a>';
        }
        return $links;
    }
}

?>