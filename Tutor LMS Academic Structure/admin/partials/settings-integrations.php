<?php
if (!defined('ABSPATH')) {
    exit;
}

$integration_handler = TLMS_Integration_Handler::instance();
$active_integrations = $integration_handler->get_active_integrations();
?>

<div class="tlms-integrations-settings">
    <h2><?php _e('Plugin Integrations', 'tutor-lms-academic-pro'); ?></h2>
    
    <div class="tlms-info-box">
        <p><?php _e('Tutor LMS Academic Pro can integrate with other popular plugins to enhance functionality. Active integrations are automatically detected.', 'tutor-lms-academic-pro'); ?></p>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <?php _e('Enable Integrations', 'tutor-lms-academic-pro'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="tlms_academic_pro_settings[enable_integrations]" value="1" 
                        <?php checked(isset($options['enable_integrations']) ? $options['enable_integrations'] : true); ?> />
                    <?php _e('Automatically detect and integrate with compatible plugins', 'tutor-lms-academic-pro'); ?>
                </label>
                <p class="description">
                    <?php _e('When enabled, the plugin will automatically detect and integrate with supported plugins.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <div class="tlms-integrations-status">
        <h3><?php _e('Integration Status', 'tutor-lms-academic-pro'); ?></h3>
        
        <div class="tlms-integrations-list">
            <?php
            $integrations = array(
                'restrict-content-pro' => array(
                    'name' => __('Restrict Content Pro', 'tutor-lms-academic-pro'),
                    'description' => __('Integrate membership and content restriction features.', 'tutor-lms-academic-pro'),
                    'icon' => 'dashicons-lock'
                ),
                'paid-memberships-pro' => array(
                    'name' => __('Paid Memberships Pro', 'tutor-lms-academic-pro'),
                    'description' => __('Sync membership levels with academic categories.', 'tutor-lms-academic-pro'),
                    'icon' => 'dashicons-groups'
                ),
                'woocommerce-subscriptions' => array(
                    'name' => __('WooCommerce Subscriptions', 'tutor-lms-academic-pro'),
                    'description' => __('Manage course access based on subscription plans.', 'tutor-lms-academic-pro'),
                    'icon' => 'dashicons-cart'
                ),
                'zoom-integration' => array(
                    'name' => __('Zoom Integration', 'tutor-lms-academic-pro'),
                    'description' => __('Restrict Zoom meetings based on academic categories.', 'tutor-lms-academic-pro'),
                    'icon' => 'dashicons-video-alt3'
                ),
                'buddypress' => array(
                    'name' => __('BuddyPress', 'tutor-lms-academic-pro'),
                    'description' => __('Sync academic categories with BuddyPress groups.', 'tutor-lms-academic-pro'),
                    'icon' => 'dashicons-buddicons-community'
                )
            );
            
            foreach ($integrations as $integration => $data):
                $is_active = $integration_handler->is_integration_active($integration);
                $status_class = $is_active ? 'active' : 'inactive';
                $status_text = $is_active ? __('Active', 'tutor-lms-academic-pro') : __('Inactive', 'tutor-lms-academic-pro');
            ?>
                <div class="tlms-integration-status <?php echo $status_class; ?>">
                    <div class="tlms-integration-icon">
                        <span class="dashicons <?php echo $data['icon']; ?>"></span>
                    </div>
                    <div class="tlms-integration-info">
                        <h4><?php echo $data['name']; ?></h4>
                        <p><?php echo $data['description']; ?></p>
                        <span class="tlms-integration-badge"><?php echo $status_text; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="tlms-integration-settings">
        <h3><?php _e('Integration Settings', 'tutor-lms-academic-pro'); ?></h3>
        
        <div class="tlms-info-box">
            <h4><?php _e('Restrict Content Pro', 'tutor-lms-academic-pro'); ?></h4>
            <p>
                <label>
                    <input type="checkbox" name="tlms_academic_pro_settings[rcp_sync_memberships]" value="1" 
                        <?php checked(isset($options['rcp_sync_memberships']) ? $options['rcp_sync_memberships'] : false); ?> />
                    <?php _e('Sync academic categories with membership levels', 'tutor-lms-academic-pro'); ?>
                </label>
            </p>
        </div>
        
        <div class="tlms-info-box">
            <h4><?php _e('BuddyPress', 'tutor-lms-academic-pro'); ?></h4>
            <p>
                <label>
                    <input type="checkbox" name="tlms_academic_pro_settings[bp_sync_groups]" value="1" 
                        <?php checked(isset($options['bp_sync_groups']) ? $options['bp_sync_groups'] : false); ?> />
                    <?php _e('Automatically create BuddyPress groups for academic categories', 'tutor-lms-academic-pro'); ?>
                </label>
            </p>
        </div>
    </div>
</div>