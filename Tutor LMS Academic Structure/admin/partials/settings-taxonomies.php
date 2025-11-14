<?php
/**
 * ملف: admin/partials/settings-taxonomies.php
 * التعديل: تصحيح استدعاء الدالة
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('tlms_academic_pro_settings');
$max_levels = isset($options['max_levels']) ? $options['max_levels'] : 5;
$level_names = isset($options['level_names']) ? $options['level_names'] : array();

// الحصول على instance من الفئة بدلاً من استدعاء دالة غير موجودة
$taxonomy_handler = TLMS_Custom_Taxonomies::instance();
?>

<div class="tlms-taxonomy-settings">
    <h2><?php _e('Academic Categories Structure', 'tutor-lms-academic-pro'); ?></h2>
    
    <div class="tlms-info-box">
        <p><?php _e('Configure the hierarchical structure of your academic categories. You can define up to 10 levels of categorization.', 'tutor-lms-academic-pro'); ?></p>
    </div>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="tlms_max_levels"><?php _e('Maximum Category Levels', 'tutor-lms-academic-pro'); ?></label>
            </th>
            <td>
                <select name="tlms_academic_pro_settings[max_levels]" id="tlms_max_levels">
                    <?php for ($i = 3; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($max_levels, $i); ?>>
                            <?php echo $i; ?> <?php _e('levels', 'tutor-lms-academic-pro'); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <p class="description">
                    <?php _e('Maximum number of hierarchical levels in your academic structure.', 'tutor-lms-academic-pro'); ?>
                </p>
            </td>
        </tr>
    </table>
    
    <div class="tlms-level-builder">
        <h3><?php _e('Level Names Configuration', 'tutor-lms-academic-pro'); ?></h3>
        <p class="description"><?php _e('Define custom names for each level in your academic hierarchy.', 'tutor-lms-academic-pro'); ?></p>
        
        <div id="tlms-category-levels">
            <?php for ($i = 0; $i < $max_levels; $i++): ?>
                <div class="tlms-level" data-level="<?php echo $i; ?>">
                    <h4><?php printf(__('Level %d', 'tutor-lms-academic-pro'), $i + 1); ?></h4>
                    <label for="tlms_level_name_<?php echo $i; ?>"><?php _e('Level Name:', 'tutor-lms-academic-pro'); ?></label>
                    <input type="text" 
                           id="tlms_level_name_<?php echo $i; ?>" 
                           name="tlms_academic_pro_settings[level_names][<?php echo $i; ?>]" 
                           value="<?php echo isset($level_names[$i]) ? esc_attr($level_names[$i]) : ''; ?>" 
                           placeholder="<?php echo $taxonomy_handler->get_default_level_name($i); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php printf(__('Display name for level %d (e.g., %s)', 'tutor-lms-academic-pro'), $i + 1, $taxonomy_handler->get_default_level_name($i)); ?>
                    </p>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <div class="tlms-taxonomy-tools">
        <h3><?php _e('Category Management Tools', 'tutor-lms-academic-pro'); ?></h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
            <div class="tlms-info-box">
                <h4><?php _e('Category Validation', 'tutor-lms-academic-pro'); ?></h4>
                <p><?php _e('Check for issues in your category structure.', 'tutor-lms-academic-pro'); ?></p>
                <button type="button" class="button tlms-bulk-action" data-action="validate_categories">
                    <?php _e('Validate Categories', 'tutor-lms-academic-pro'); ?>
                </button>
            </div>
            
            <div class="tlms-info-box">
                <h4><?php _e('Bulk Category Assignment', 'tutor-lms-academic-pro'); ?></h4>
                <p><?php _e('Assign categories to multiple users at once.', 'tutor-lms-academic-pro'); ?></p>
                <a href="<?php echo admin_url('users.php?page=tlms-bulk-assign'); ?>" class="button">
                    <?php _e('Bulk Assign', 'tutor-lms-academic-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// إزالة الدالة المساعدة القديمة التي كانت تسبب المشكلة
// لأننا الآن نستخدم الدالة من الـ instance
?>