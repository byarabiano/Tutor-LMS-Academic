<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_multisite()) {
    echo '<div class="notice notice-warning"><p>' . __('Multisite is not enabled. These settings only apply to multisite installations.', 'tutor-lms-academic-pro') . '</p></div>';
    return;
}

$network_settings = get_site_option('tlms_network_settings', array());
?>

<div class="tlms-multisite-settings">
    <h2><?php _e('Multisite Network Settings', 'tutor-lms-academic-pro'); ?></h2>
    
    <div class="tlms-info-box">
        <p><?php _e('Configure how Tutor LMS Academic Pro behaves across your WordPress network. These settings apply to all sites in the network.', 'tutor-lms-academic-pro'); ?></p>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="tlms_network_default_enabled"><?php _e('Enable by Default', 'tutor-lms-academic-pro'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="tlms_network_settings[default_enabled]" id="tlms_network_default_enabled" value="1" 
                        <?php checked(isset($network_settings['default_enabled']) ? $network_settings['default_enabled'] : true); ?> />
                    <?php _e('Enable Tutor LMS Academic Pro by default for new sites', 'tutor-lms-academic-pro'); ?>
                </label>
                <p class="description">
                    <?php _e('When enabled, new sites will have the plugin activated with default settings.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="tlms_network_allow_site_settings"><?php _e('Allow Site Settings', 'tutor-lms-academic-pro'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="tlms_network_settings[allow_site_settings]" id="tlms_network_allow_site_settings" value="1" 
                        <?php checked(isset($network_settings['allow_site_settings']) ? $network_settings['allow_site_settings'] : true); ?> />
                    <?php _e('Allow individual sites to modify Academic Pro settings', 'tutor-lms-academic-pro'); ?>
                </label>
                <p class="description">
                    <?php _e('When disabled, site administrators cannot change the plugin settings.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label><?php _e('Default Education Types', 'tutor-lms-academic-pro'); ?></label>
            </th>
            <td>
                <?php
                $default_types = isset($network_settings['default_education_types']) ? 
                    $network_settings['default_education_types'] : 
                    array('university', 'school', 'general');
                ?>
                <fieldset>
                    <legend class="screen-reader-text"><?php _e('Default Education Types', 'tutor-lms-academic-pro'); ?></legend>
                    
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
                </fieldset>
                <p class="description">
                    <?php _e('Select which education types are available by default for new sites.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="tlms_network_default_levels"><?php _e('Default Category Levels', 'tutor-lms-academic-pro'); ?></label>
            </th>
            <td>
                <select name="tlms_network_settings[default_levels]" id="tlms_network_default_levels">
                    <?php for ($i = 3; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                            <?php selected(isset($network_settings['default_levels']) ? $network_settings['default_levels'] : 5, $i); ?>>
                            <?php echo $i; ?> <?php _e('levels', 'tutor-lms-academic-pro'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <p class="description">
                    <?php _e('Default number of category levels for new sites.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <?php if (is_network_admin()): ?>
        <div class="tlms-network-tools">
            <h3><?php _e('Network Management Tools', 'tutor-lms-academic-pro'); ?></h3>
            
            <div class="tlms-info-box">
                <h4><?php _e('Network-wide Category Sync', 'tutor-lms-academic-pro'); ?></h4>
                <p><?php _e('Synchronize academic categories across all sites in the network.', 'tutor-lms-academic-pro'); ?></p>
                <button type="button" class="button" id="tlms-network-sync">
                    <?php _e('Sync Categories Network-wide', 'tutor-lms-academic-pro'); ?>
                </button>
            </div>
            
            <div class="tlms-info-box">
                <h4><?php _e('Export Network Settings', 'tutor-lms-academic-pro'); ?></h4>
                <p><?php _e('Export network settings for backup or migration.', 'tutor-lms-academic-pro'); ?></p>
                <button type="button" class="button" id="tlms-export-network-settings">
                    <?php _e('Export Network Settings', 'tutor-lms-academic-pro'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Network sync handler
    $('#tlms-network-sync').on('click', function() {
        if (confirm('<?php _e('This will synchronize categories across all sites. Continue?', 'tutor-lms-academic-pro'); ?>')) {
            var $button = $(this);
            var originalText = $button.text();
            
            $button.text('Syncing...').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tlms_network_sync',
                    nonce: '<?php echo wp_create_nonce('tlms_network_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data);
                    }
                    $button.text(originalText).prop('disabled', false);
                },
                error: function() {
                    alert('<?php _e('Error syncing network. Please try again.', 'tutor-lms-academic-pro'); ?>');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        }
    });
});
</script>