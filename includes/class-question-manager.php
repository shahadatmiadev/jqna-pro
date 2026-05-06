<?php
/**
 * Question Manager Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Question_Manager {
    
    /**
     * Register custom post type
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => __('Questions', 'jqna_'),
            'singular_name'      => __('Question', 'jqna_'),
            'menu_name'          => __('Questions', 'jqna_'),
            'add_new'            => __('Add New', 'jqna_'),
            'add_new_item'       => __('Add New Question', 'jqna_'),
            'edit_item'          => __('Edit Question', 'jqna_'),
            'new_item'           => __('New Question', 'jqna_'),
            'view_item'          => __('View Question', 'jqna_'),
            'search_items'       => __('Search Questions', 'jqna_'),
            'not_found'          => __('No questions found', 'jqna_'),
            'not_found_in_trash' => __('No questions found in trash', 'jqna_'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'author'),
            'show_in_rest'        => false,
        );
        
        register_post_type('jqna_question', $args);
    }
    
    /**
     * Register taxonomy
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'              => __('Categories', 'jqna_'),
            'singular_name'     => __('Category', 'jqna_'),
            'search_items'      => __('Search Categories', 'jqna_'),
            'all_items'         => __('All Categories', 'jqna_'),
            'parent_item'       => __('Parent Category', 'jqna_'),
            'parent_item_colon' => __('Parent Category:', 'jqna_'),
            'edit_item'         => __('Edit Category', 'jqna_'),
            'update_item'       => __('Update Category', 'jqna_'),
            'add_new_item'      => __('Add New Category', 'jqna_'),
            'new_item_name'     => __('New Category Name', 'jqna_'),
            'menu_name'         => __('Categories', 'jqna_'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'show_in_rest'       => false,
        );
        
        register_taxonomy('jqna_category', array('jqna_question'), $args);
    }
    
    /**
     * Submit new question
     *
     * @param int $user_id User ID
     * @param string $title Question title
     * @param string $content Question content
     * @return int|false Post ID or false
     */
    public static function submit_question($user_id, $title, $content) {
        $post_data = array(
            'post_title'    => sanitize_text_field($title),
            'post_content'  => $content,
            'post_status'   => 'pending',
            'post_type'     => 'jqna_question',
            'post_author'   => intval($user_id),
        );
        
        return wp_insert_post($post_data, true);
    }
    
    /**
     * Get approved questions
     *
     * @param int $category_id Category ID
     * @param int $paged Page number
     * @return array
     */
    public static function get_approved_questions($category_id = 0, $paged = 1) {
        $args = array(
            'post_type' => 'jqna_question',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        if ($category_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'jqna_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            );
        }
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Get questions count by category
     *
     * @param int $category_id Category ID
     * @return int
     */
    public static function get_questions_count($category_id = 0) {
        $args = array(
            'post_type' => 'jqna_question',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        if ($category_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'jqna_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            );
        }
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get all categories with counts
     *
     * @return array
     */
    public static function get_categories_with_counts() {
        $categories = get_terms(array(
            'taxonomy' => 'jqna_category',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($categories)) {
            return array();
        }
        
        $result = array();
        foreach ($categories as $category) {
            $result[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => self::get_questions_count($category->term_id),
            );
        }
        
        return $result;
    }
    
    /**
     * Update question answer and status
     *
     * @param int $question_id Question ID
     * @param string $answer Answer content
     * @param string $status Post status
     * @return bool
     */
    public static function update_question($question_id, $answer, $status) {
        $question_id = intval($question_id);
        $answer = wp_kses_post($answer);
        
        update_post_meta($question_id, '_jqna_answer', $answer);
        
        $result = wp_update_post(array(
            'ID' => $question_id,
            'post_status' => $status,
        ));
        
        return !is_wp_error($result);
    }
}