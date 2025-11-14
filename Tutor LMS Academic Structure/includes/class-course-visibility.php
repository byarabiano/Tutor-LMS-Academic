<?php

class TLMS_Course_Visibility {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_filter('tutor_course_archive_arg', array($this, 'modify_course_archive_query'));
        add_filter('tutor_course/single/enrolled/nav_items', array($this, 'filter_course_navigation'));
        add_action('template_redirect', array($this, 'check_course_access'));
        
        // Frontend course filtering
        add_action('pre_get_posts', array($this, 'filter_frontend_courses'));
    }
    
    public function modify_course_archive_query($args, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return $args;
        }
        
        $user_education_type = get_user_meta($user_id, 'tlms_education_type', true);
        $user_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
        
        if (empty($user_education_type)) {
            $options = get_option('tlms_academic_pro_settings');
            $user_education_type = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
        }
        
        if ($user_education_type === 'general') {
            $args = $this->filter_general_user_courses($args, $user_id);
        } else {
            $args = $this->filter_academic_user_courses($args, $user_categories, $user_id);
        }
        
        return $args;
    }
    
    private function filter_general_user_courses($args, $user_id) {
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = array();
        }
        
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => '_tlms_education_type',
                'value' => 'general',
                'compare' => '='
            ),
            array(
                'key' => '_tlms_course_author',
                'value' => $user_id,
                'compare' => '='
            ),
            array(
                'key' => '_tlms_education_type',
                'compare' => 'NOT EXISTS'
            )
        );
        
        return $args;
    }
    
    private function filter_academic_user_courses($args, $user_categories, $user_id) {
        if (empty($user_categories)) {
            return $args;
        }
        
        $leaf_category = $this->get_leaf_category($user_categories);
        
        if ($leaf_category) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            
            $args['tax_query'][] = array(
                'taxonomy' => 'tlms_academic_category',
                'field' => 'term_id',
                'terms' => array($leaf_category),
                'operator' => 'IN'
            );
        }
        
        // Also include general courses
        if (!isset($args['tax_query'])) {
            $args['tax_query'] = array();
        }
        
        $args['tax_query']['relation'] = 'OR';
        $args['tax_query'][] = array(
            'taxonomy' => 'tlms_academic_category',
            'field' => 'term_id',
            'terms' => $this->get_general_categories(),
            'operator' => 'IN'
        );
        
        return $args;
    }
    
    public function filter_frontend_courses($query) {
        if (is_admin() || !$query->is_main_query() || !$this->is_course_query($query)) {
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        
        $user_education_type = get_user_meta($user_id, 'tlms_education_type', true);
        $user_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
        
        if (empty($user_education_type)) {
            $options = get_option('tlms_academic_pro_settings');
            $user_education_type = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
        }
        
        if ($user_education_type === 'general') {
            $this->apply_general_user_filter($query, $user_id);
        } else {
            $this->apply_academic_user_filter($query, $user_categories, $user_id);
        }
    }
    
    private function is_course_query($query) {
        return (is_post_type_archive('courses') || 
                is_tax('course-category') || 
                (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'courses'));
    }
    
    private function apply_general_user_filter($query, $user_id) {
        $meta_query = $query->get('meta_query') ?: array();
        
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_tlms_education_type',
                'value' => 'general',
                'compare' => '='
            ),
            array(
                'key' => '_tlms_course_author',
                'value' => $user_id,
                'compare' => '='
            ),
            array(
                'key' => '_tlms_education_type',
                'compare' => 'NOT EXISTS'
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
    
    private function apply_academic_user_filter($query, $user_categories, $user_id) {
        if (empty($user_categories)) {
            return;
        }
        
        $leaf_category = $this->get_leaf_category($user_categories);
        
        if ($leaf_category) {
            $tax_query = $query->get('tax_query') ?: array();
            
            $tax_query[] = array(
                'taxonomy' => 'tlms_academic_category',
                'field' => 'term_id',
                'terms' => array($leaf_category),
                'operator' => 'IN'
            );
            
            $query->set('tax_query', $tax_query);
        }
    }
    
    public function check_course_access() {
        if (!is_singular('courses')) {
            return;
        }
        
        global $post;
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $course_id = $post->ID;
        
        if (!$this->user_can_access_course($user_id, $course_id)) {
            $this->restrict_access();
        }
    }
    
    public function user_can_access_course($user_id, $course_id) {
        // Course author can always access their own courses
        $post = get_post($course_id);
        if ($post->post_author == $user_id) {
            return true;
        }
        
        // Admin and editors can access all courses
        if (current_user_can('administrator') || current_user_can('editor')) {
            return true;
        }
        
        $user_education_type = get_user_meta($user_id, 'tlms_education_type', true);
        $user_categories = get_user_meta($user_id, 'tlms_academic_categories', true);
        
        if (empty($user_education_type)) {
            $options = get_option('tlms_academic_pro_settings');
            $user_education_type = isset($options['default_user_category']) ? $options['default_user_category'] : 'general';
        }
        
        $course_education_type = get_post_meta($course_id, '_tlms_education_type', true);
        $course_categories = wp_get_post_terms($course_id, 'tlms_academic_category', array('fields' => 'ids'));
        
        if ($user_education_type === 'general') {
            return $course_education_type === 'general' || empty($course_education_type);
        } else {
            $leaf_category = $this->get_leaf_category($user_categories);
            return $course_education_type === 'general' || (in_array($leaf_category, $course_categories));
        }
    }
    
    private function get_leaf_category($categories) {
        if (!is_array($categories)) {
            return false;
        }
        
        foreach (array_reverse($categories, true) as $category) {
            if ($category) {
                return $category;
            }
        }
        return false;
    }
    
    private function get_general_categories() {
        $general_categories = get_terms(array(
            'taxonomy' => 'tlms_academic_category',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'education_type',
                    'value' => 'general'
                )
            ),
            'fields' => 'ids'
        ));
        
        return is_array($general_categories) ? $general_categories : array();
    }
    
    private function restrict_access() {
        wp_die(
            __('You do not have permission to access this course.', 'tutor-lms-academic-pro'),
            __('Access Denied', 'tutor-lms-academic-pro'),
            array('response' => 403)
        );
    }
    
    public function filter_course_navigation($nav_items) {
        // يمكن إضافة منطق إضافي هنا إذا لزم الأمر
        return $nav_items;
    }
}

?>