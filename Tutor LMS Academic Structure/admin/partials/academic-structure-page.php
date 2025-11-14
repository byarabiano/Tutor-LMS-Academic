<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Academic Structure Management', 'tutor-lms-academic-pro'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This page allows you to manage the academic categories hierarchy for universities, schools, and general courses.', 'tutor-lms-academic-pro'); ?></p>
    </div>
    
    <h2 class="nav-tab-wrapper">
        <a href="#universities" class="nav-tab nav-tab-active"><?php _e('🏛️ Universities', 'tutor-lms-academic-pro'); ?></a>
        <a href="#schools" class="nav-tab"><?php _e('🎓 Schools', 'tutor-lms-academic-pro'); ?></a>
        <a href="#general" class="nav-tab"><?php _e('📚 General Courses', 'tutor-lms-academic-pro'); ?></a>
        <a href="<?php echo admin_url('edit-tags.php?taxonomy=tlms_academic_category'); ?>" class="nav-tab">
            <?php _e('➕ Add New Categories', 'tutor-lms-academic-pro'); ?>
        </a>
    </h2>
    
    <div id="universities" class="tlms-tab-content active">
        <h3><?php _e('University Structure', 'tutor-lms-academic-pro'); ?></h3>
        <p><?php _e('Manage university categories hierarchy. These categories will be available for university-level courses.', 'tutor-lms-academic-pro'); ?></p>
        <?php $this->display_categories_table('university'); ?>
    </div>
    
    <div id="schools" class="tlms-tab-content">
        <h3><?php _e('School Structure', 'tutor-lms-academic-pro'); ?></h3>
        <p><?php _e('Manage school categories hierarchy. These categories will be available for school-level courses.', 'tutor-lms-academic-pro'); ?></p>
        <?php $this->display_categories_table('school'); ?>
    </div>
    
    <div id="general" class="tlms-tab-content">
        <h3><?php _e('General Courses Categories', 'tutor-lms-academic-pro'); ?></h3>
        <p><?php _e('Manage general course categories. These categories will be available for all users regardless of their education type.', 'tutor-lms-academic-pro'); ?></p>
        <?php $this->display_categories_table('general'); ?>
    </div>
</div>

<style>
.tlms-tab-content { 
    display: none; 
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}
.tlms-tab-content.active { 
    display: block; 
}
.tlms-categories-table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
}
.tlms-categories-table th, 
.tlms-categories-table td { 
    border: 1px solid #ddd; 
    padding: 12px; 
    text-align: left; 
}
.tlms-categories-table th { 
    background: #f5f5f5; 
    font-weight: 600;
}
.tlms-category-level-0 { 
    font-weight: bold; 
    background: #e7f3ff; 
}
.tlms-category-level-1 { 
    padding-left: 30px; 
    background: #f0f8ff; 
}
.tlms-category-level-2 { 
    padding-left: 60px; 
    background: #f9fcff; 
}
.tlms-category-level-3 { 
    padding-left: 90px; 
}
.tlms-category-level-4 { 
    padding-left: 120px; 
}
.tlms-no-categories {
    background: #f9f9f9;
    padding: 20px;
    text-align: center;
    border: 1px dashed #ccc;
}
.tlms-no-categories p {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // إذا كان الرابط خارجياً، اتركه يعمل بشكل طبيعي
        if (target.indexOf('#') !== 0) {
            return true;
        }
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tlms-tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>