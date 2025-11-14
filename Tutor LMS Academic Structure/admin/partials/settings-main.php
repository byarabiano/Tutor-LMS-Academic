<?php
if (!defined('ABSPATH')) {
    exit;
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
            <form method="post" action="options.php">
                <?php
                settings_fields('tlms_academic_pro_settings');
                do_settings_sections('tlms-academic-pro-settings');
                submit_button(__('Save Settings', 'tutor-lms-academic-pro'));
                ?>
            </form>
            
            <!-- Quick Actions Section -->
            <div class="tlms-quick-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
                <h2><?php _e('Quick Actions', 'tutor-lms-academic-pro'); ?></h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=tlms_academic_category'); ?>" class="button button-primary" style="text-align: center; padding: 15px;">
                        <span class="dashicons dashicons-category" style="display: block; font-size: 30px; margin-bottom: 10px;"></span>
                        <?php _e('Manage Academic Categories', 'tutor-lms-academic-pro'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('users.php'); ?>" class="button" style="text-align: center; padding: 15px;">
                        <span class="dashicons dashicons-groups" style="display: block; font-size: 30px; margin-bottom: 10px;"></span>
                        <?php _e('Manage User Categories', 'tutor-lms-academic-pro'); ?>
                    </a>
                    
                    <button type="button" class="button tlms-bulk-action" data-action="migrate_existing_users" style="text-align: center; padding: 15px;">
                        <span class="dashicons dashicons-update" style="display: block; font-size: 30px; margin-bottom: 10px;"></span>
                        <?php _e('Migrate Existing Users', 'tutor-lms-academic-pro'); ?>
                    </button>
                    
                    <button type="button" class="button tlms-bulk-action" data-action="clear_cache" style="text-align: center; padding: 15px;">
                        <span class="dashicons dashicons-performance" style="display: block; font-size: 30px; margin-bottom: 10px;"></span>
                        <?php _e('Clear Cache', 'tutor-lms-academic-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="tlms-settings-sidebar">
            <div class="tlms-info-box">
                <h3><?php _e('Plugin Information', 'tutor-lms-academic-pro'); ?></h3>
                <p><strong><?php _e('Version:', 'tutor-lms-academic-pro'); ?></strong> <?php echo esc_html(TLMS_ACADEMIC_PRO_VERSION); ?></p>
                <p><strong><?php _e('Tutor LMS:', 'tutor-lms-academic-pro'); ?></strong> 
                    <?php echo class_exists('TUTOR\Tutor') ? __('Active', 'tutor-lms-academic-pro') : __('Not Active', 'tutor-lms-academic-pro'); ?>
                </p>
            </div>
            
            <div class="tlms-info-box">
                <h3><?php _e('Export/Import Settings', 'tutor-lms-academic-pro'); ?></h3>
                <p>
                    <button type="button" id="tlms-export-settings" class="button button-primary">
                        <?php _e('Export Settings', 'tutor-lms-academic-pro'); ?>
                    </button>
                </p>
                <p>
                    <input type="file" id="tlms-import-file" accept=".json" style="display: none;">
                    <button type="button" id="tlms-import-settings" class="button">
                        <?php _e('Import Settings', 'tutor-lms-academic-pro'); ?>
                    </button>
                </p>
                <p class="description">
                    <?php _e('Backup your settings or migrate to another site.', 'tutor-lms-academic-pro'); ?>
                </p>
            </div>
            
            <div class="tlms-info-box">
                <h3><?php _e('Need Help?', 'tutor-lms-academic-pro'); ?></h3>
                <p><?php _e('Check the documentation for detailed instructions on setting up academic categories and user management.', 'tutor-lms-academic-pro'); ?></p>
                <p>
                    <a href="https://docs.yoursite.com/tutor-lms-academic-pro" target="_blank" class="button">
                        <?php _e('View Documentation', 'tutor-lms-academic-pro'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Localize AJAX URL and nonce
var tlms_admin_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('tlms_admin_nonce'); ?>'
};
</script>