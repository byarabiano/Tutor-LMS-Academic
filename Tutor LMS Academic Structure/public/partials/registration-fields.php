<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('tlms_academic_pro_settings');
if (!isset($options['enabled']) || !$options['enabled']) {
    return;
}

$education_types = isset($options['education_types']) ? $options['education_types'] : array();
?>

<div class="tlms-registration-fields">
    <h3><?php _e('Academic Information', 'tutor-lms-academic-pro'); ?></h3>
    
    <p>
        <label for="tlms_education_type"><?php _e('Education Type', 'tutor-lms-academic-pro'); ?><br>
            <select name="tlms_education_type" id="tlms_education_type" class="input" required>
                <option value=""><?php _e('Select Education Type', 'tutor-lms-academic-pro'); ?></option>
                <?php foreach ($education_types as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>">
                        <?php echo $this->get_education_type_label($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    
    <div id="tlms_academic_categories_container" style="display: none;">
        <!-- Dynamic category fields will be loaded here via AJAX -->
    </div>
</div>

<script type="text/javascript">
// Localize AJAX URL and nonce for public scripts
var tlms_public_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('tlms_ajax_nonce'); ?>'
};
</script>