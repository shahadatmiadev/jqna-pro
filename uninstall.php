<?php
/**
 * Plugin uninstall handler
 *
 * @package Qnario
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom post type posts
$questions = get_posts(array(
    'post_type' => 'jqna_question',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($questions as $question) {
    wp_delete_post($question->ID, true);
}

// Delete taxonomy terms
$terms = get_terms(array(
    'taxonomy' => 'jqna_category',
    'hide_empty' => false
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'jqna_category');
}

// Delete options (if any)
delete_option('jqna_settings');

// Clear any scheduled hooks
wp_clear_scheduled_hook('jqna_daily_cleanup');