<?php

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('tlms_academic_pro_settings');
delete_option('tlms_academic_pro_version');
delete_option('tlms_compatibility_report');

// Clean up user meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'tlms_%'");

// Clean up post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_tlms_%'");

// Clean up terms and taxonomies
$terms = get_terms(array(
    'taxonomy' => 'tlms_academic_category',
    'hide_empty' => false,
    'fields' => 'ids'
));

if (!empty($terms) && !is_wp_error($terms)) {
    foreach ($terms as $term_id) {
        wp_delete_term($term_id, 'tlms_academic_category');
    }
}

// Clean up integration options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'tlms_pmpro_level_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'tlms_%'");

// Multisite cleanup
if (is_multisite()) {
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        delete_option('tlms_academic_pro_settings');
        delete_option('tlms_academic_pro_version');
        delete_option('tlms_compatibility_report');
        
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'tlms_%'");
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_tlms_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'tlms_%'");
        
        restore_current_blog();
    }
    
    delete_site_option('tlms_network_settings');
}

// Flush rewrite rules
flush_rewrite_rules();