<?php
if (!defined('ABSPATH')) {
    exit;
}

global $post;
$course_id = $post->ID;
$selected_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
?>

<div class="tutor-option-field-row">
    <div class="tutor-option-field-label">
        <label for="tlms_academic_categories">
            <?php _e('Academic Categories', 'tutor-lms-academic-pro'); ?>
        </label>
        <p class="tutor-option-field-desc">
            <?php _e('Select the academic categories for this course. This will determine which users can see and access the course based on their academic profile.', 'tutor-lms-academic-pro'); ?>
        </p>
    </div>
    
    <div class="tutor-option-field">
        <?php
        $categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'parent' => 0
        ));
        
        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<div class="tlms-course-categories-checklist" style="max-height: 300px; overflow-y: auto; border: 1px solid #dcdfe5; border-radius: 6px; padding: 15px;">';
            $this->display_category_checkboxes_formatted($categories, $selected_categories);
            echo '</div>';
        } else {
            echo '<p>' . __('No academic categories found. Please create categories first from Tutor LMS → Academic Structure.', 'tutor-lms-academic-pro') . '</p>';
            echo '<a href="' . admin_url('edit-tags.php?taxonomy=tlms_academic_category') . '" class="tutor-btn tutor-btn-primary">';
            echo __('Manage Academic Categories', 'tutor-lms-academic-pro');
            echo '</a>';
        }
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // التأكد من أن الحقول محفوظة مع إعدادات Tutor LMS
    $('form#tutor-frontend-course-builder').on('submit', function() {
        var categories = [];
        $('input[name="tlms_course_categories[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        
        // إضافة الحقول المخفية للتأكد من حفظها
        if (categories.length > 0) {
            $('#tlms_course_categories_nonce').remove();
            $(this).append('<input type="hidden" name="tlms_course_categories_nonce" value="<?php echo wp_create_nonce('tlms_course_categories_nonce'); ?>">');
        }
    });
});
</script>

<?php
// دالة مساعدة لعرض التصنيفات بشكل منسق
private function display_category_checkboxes_formatted($categories, $selected_categories, $level = 0) {
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        $checked = in_array($category->term_id, $selected_categories) ? 'checked' : '';
        $education_type = get_term_meta($category->term_id, 'education_type', true);
        $badge_color = $this->get_education_type_badge_color($education_type);
        
        echo '<div class="tutor-form-check" style="margin-bottom: 8px; margin-left: ' . ($level * 20) . 'px;">';
        echo '<input type="checkbox" id="tlms_cat_' . $category->term_id . '" name="tlms_course_categories[]" value="' . $category->term_id . '" ' . $checked . ' class="tutor-form-check-input">';
        echo '<label for="tlms_cat_' . $category->term_id . '" class="tutor-form-check-label" style="display: inline-flex; align-items: center; gap: 8px;">';
        echo $category->name;
        echo '<span class="tutor-badge-label" style="background: ' . $badge_color . '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; text-transform: uppercase;">';
        echo $education_type;
        echo '</span>';
        echo '</label>';
        echo '</div>';
        
        // عرض التصنيفات الفرعية
        $child_categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'parent' => $category->term_id
        ));
        
        if (!empty($child_categories) && !is_wp_error($child_categories)) {
            $this->display_category_checkboxes_formatted($child_categories, $selected_categories, $level + 1);
        }
    }
}

private function get_education_type_badge_color($education_type) {
    $colors = array(
        'university' => '#3498db',
        'school' => '#e74c3c',
        'general' => '#2ecc71'
    );
    
    return isset($colors[$education_type]) ? $colors[$education_type] : '#95a5a6';
}
?>