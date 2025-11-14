<?php

class TLMS_Zoom_Integration {
    
    private $is_active = false;
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_integration'));
    }
    
    public function init_integration() {
        if (!class_exists('TUTOR_ZOOM\Init')) {
            return;
        }
        
        $this->is_active = true;
        
        // Add integration hooks
        add_filter('tutor_zoom_meeting_access', array($this, 'check_meeting_access'), 10, 2);
        add_action('tutor_zoom_before_create_meeting', array($this, 'validate_meeting_creation'));
        add_filter('tutor_zoom_meeting_list', array($this, 'filter_meeting_list'));
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_zoom_meta_boxes'));
    }
    
    public function check_meeting_access($has_access, $meeting_id) {
        if (!$this->is_active || !$has_access) {
            return $has_access;
        }
        
        $user_id = get_current_user_id();
        $course_id = $this->get_course_from_meeting($meeting_id);
        
        if (!$course_id) {
            return $has_access;
        }
        
        // Check academic access to the course
        return $this->user_has_course_access($user_id, $course_id);
    }
    
    public function validate_meeting_creation($data) {
        $user_id = get_current_user_id();
        $course_id = isset($data['post_ID']) ? $data['post_ID'] : 0;
        
        if (!$course_id) {
            return;
        }
        
        if (!$this->user_has_course_access($user_id, $course_id, true)) {
            wp_die(__('You do not have permission to create meetings for this course.', 'tutor-lms-academic-pro'));
        }
    }
    
    public function filter_meeting_list($meetings) {
        if (!$this->is_active || empty($meetings)) {
            return $meetings;
        }
        
        $user_id = get_current_user_id();
        $filtered_meetings = array();
        
        foreach ($meetings as $meeting) {
            $course_id = $this->get_course_from_meeting($meeting->ID);
            
            if (!$course_id || $this->user_has_course_access($user_id, $course_id)) {
                $filtered_meetings[] = $meeting;
            }
        }
        
        return $filtered_meetings;
    }
    
    public function get_course_from_meeting($meeting_id) {
        return get_post_meta($meeting_id, '_tutor_zm_for_course', true);
    }
    
    public function user_has_course_access($user_id, $course_id, $is_instructor = false) {
        if ($is_instructor) {
            // Instructors should have access to their own courses
            $course = get_post($course_id);
            return $course && $course->post_author == $user_id;
        }
        
        // Use the main course visibility class to check access
        $course_visibility = TLMS_Course_Visibility::instance();
        return $course_visibility->user_can_access_course($user_id, $course_id);
    }
    
    public function add_zoom_meta_boxes() {
        if (!function_exists('tutor_zoom_meeting')) {
            return;
        }
        
        add_meta_box(
            'tlms-zoom-academic-info',
            __('Academic Access Information', 'tutor-lms-academic-pro'),
            array($this, 'zoom_meta_box_callback'),
            'tutor_zoom_meeting',
            'side',
            'low'
        );
    }
    
    public function zoom_meta_box_callback($post) {
        $course_id = get_post_meta($post->ID, '_tutor_zm_for_course', true);
        
        if (!$course_id) {
            echo '<p>' . __('No course associated with this meeting.', 'tutor-lms-academic-pro') . '</p>';
            return;
        }
        
        $course = get_post($course_id);
        $education_type = get_post_meta($course_id, '_tlms_education_type', true);
        $categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'names'));
        ?>
        <div class="tlms-zoom-academic-info">
            <p><strong><?php _e('Course:', 'tutor-lms-academic-pro'); ?></strong> 
               <?php echo $course ? $course->post_title : __('Unknown', 'tutor-lms-academic-pro'); ?>
            </p>
            
            <p><strong><?php _e('Education Type:', 'tutor-lms-academic-pro'); ?></strong> 
               <?php echo $education_type ? ucfirst($education_type) : __('General', 'tutor-lms-academic-pro'); ?>
            </p>
            
            <?php if (!empty($categories)): ?>
                <p><strong><?php _e('Academic Categories:', 'tutor-lms-academic-pro'); ?></strong></p>
                <ul style="margin-left: 20px;">
                    <?php foreach ($categories as $category): ?>
                        <li><?php echo $category; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><strong><?php _e('Academic Categories:', 'tutor-lms-academic-pro'); ?></strong> 
                   <?php _e('All categories', 'tutor-lms-academic-pro'); ?>
                </p>
            <?php endif; ?>
            
            <p class="description">
                <?php _e('This meeting will only be accessible to users who have access to the associated course based on academic categories.', 'tutor-lms-academic-pro'); ?>
            </p>
        </div>
        <?php
    }
}

?>