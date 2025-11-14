<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('tlms_academic_pro_settings');
if (!isset($options['enabled']) || !$options['enabled']) {
    return;
}

$user_id = get_current_user_id();
$education_type = get_user_meta($user_id, 'tlms_education_type', true);
$academic_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
if (!is_array($academic_categories)) {
    $academic_categories = array();
}

$education_types = isset($options['education_types']) ? $options['education_types'] : array();
?>

<h3><?php _e('Academic Information', 'tutor-lms-academic-pro'); ?></h3>

<table class="form-table">
    <tr>
        <th><label for="tlms_education_type"><?php _e('Education Type', 'tutor-lms-academic-pro'); ?></label></th>
        <td>
            <select name="tlms_education_type" id="tlms_education_type">
                <option value=""><?php _e('Select Education Type', 'tutor-lms-academic-pro'); ?></option>
                <?php foreach ($education_types as $type): ?>
                    <option value="<?php echo esc_attr($type); ?>" <?php selected($education_type, $type); ?>>
                        <?php echo $this->get_education_type_label($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php _e('Select your primary education type.', 'tutor-lms-academic-pro'); ?>
            </p>
        </td>
    </tr>
    
    <tr id="tlms_academic_categories_row" style="display: <?php echo $education_type ? 'table-row' : 'none'; ?>;">
        <th><label><?php _e('Academic Categories', 'tutor-lms-academic-pro'); ?></label></th>
        <td id="tlms_academic_categories_container">
            <?php if ($education_type): ?>
                <?php echo $this->render_category_fields($education_type, $academic_categories); ?>
            <?php else: ?>
                <p class="description"><?php _e('Please select an education type first.', 'tutor-lms-academic-pro'); ?></p>
            <?php endif; ?>
        </td>
    </tr>
</table>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle education type change
    $('#tlms_education_type').change(function() {
        var educationType = $(this).val();
        var $container = $('#tlms_academic_categories_container');
        var $row = $('#tlms_academic_categories_row');
        
        if (educationType) {
            $row.show();
            
            // Show loading
            $container.html('<div class="tlms-loading"></div>');
            
            // Load categories via AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tlms_get_academic_categories',
                    education_type: educationType,
                    level: 0,
                    selected_categories: <?php echo json_encode($academic_categories); ?>,
                    nonce: '<?php echo wp_create_nonce('tlms_ajax_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data);
                    } else {
                        $container.html('<p class="description"><?php _e('Error loading categories.', 'tutor-lms-academic-pro'); ?></p>');
                    }
                },
                error: function() {
                    $container.html('<p class="description"><?php _e('Error loading categories. Please try again.', 'tutor-lms-academic-pro'); ?></p>');
                }
            });
        } else {
            $row.hide();
            $container.empty();
        }
    });
});
</script>