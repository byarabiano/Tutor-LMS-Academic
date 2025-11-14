<?php
if (!defined('ABSPATH')) {
    exit;
}

global $post;

$course_id = $post->ID;
$education_type = get_post_meta($course_id, '_tlms_education_type', true);
$selected_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
?>

<div class="tlms-course-visibility-meta">
    <h4><?php _e('Academic Visibility Settings', 'tutor-lms-academic-pro'); ?></h4>
    
    <?php wp_nonce_field('tlms_course_visibility_nonce', 'tlms_course_visibility_nonce'); ?>
    
    <div class="tlms-field-group">
        <label for="tlms_education_type"><strong><?php _e('Education Type:', 'tutor-lms-academic-pro'); ?></strong></label>
        <select name="tlms_education_type" id="tlms_education_type" style="width: 100%;">
            <option value=""><?php _e('Select Education Type', 'tutor-lms-academic-pro'); ?></option>
            <option value="university" <?php selected($education_type, 'university'); ?>><?php _e('University', 'tutor-lms-academic-pro'); ?></option>
            <option value="school" <?php selected($education_type, 'school'); ?>><?php _e('School', 'tutor-lms-academic-pro'); ?></option>
            <option value="general" <?php selected($education_type, 'general'); ?>><?php _e('General Courses', 'tutor-lms-academic-pro'); ?></option>
        </select>
        <p class="description">
            <?php _e('Select the education type for this course. This determines which users can see the course.', 'tutor-lms-academic-pro'); ?>
        </p>
    </div>
    
    <div id="tlms_course_categories_container" style="<?php echo $education_type ? '' : 'display: none;'; ?>">
        <label><strong><?php _e('Academic Categories:', 'tutor-lms-academic-pro'); ?></strong></label>
        <p class="description"><?php _e('Select the academic categories that can access this course. Leave empty to make it available to all categories of the selected education type.', 'tutor-lms-academic-pro'); ?></p>
        
        <div class="tlms-categories-checkboxes">
            <?php
            if ($education_type) {
                $categories = get_terms(array(
                    'taxonomy' => 'tlms_academic_category',
                    'hide_empty' => false,
                    'meta_query' => array(
                        array(
                            'key' => 'education_type',
                            'value' => $education_type
                        )
                    )
                ));
                
                if ($categories && !is_wp_error($categories)) {
                    echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
                    foreach ($categories as $category) {
                        $checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="tlms_academic_categories[]" value="' . $category->term_id . '" ' . $checked . '> ';
                        echo $category->name;
                        echo '</label>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>' . __('No categories found for this education type.', 'tutor-lms-academic-pro') . '</p>';
                }
            } else {
                echo '<p>' . __('Please select an education type first.', 'tutor-lms-academic-pro') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle education type change
    $('#tlms_education_type').change(function() {
        var educationType = $(this).val();
        var $container = $('#tlms_course_categories_container');
        
        if (educationType) {
            $container.show();
            
            // Reload categories via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tlms_get_course_categories',
                    education_type: educationType,
                    selected_categories: <?php echo json_encode($selected_categories); ?>,
                    nonce: '<?php echo wp_create_nonce('tlms_course_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $container.find('.tlms-categories-checkboxes').html(response.data);
                    }
                }
            });
        } else {
            $container.hide();
        }
    });
});
</script>