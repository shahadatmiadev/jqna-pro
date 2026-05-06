<?php
/**
 * Question List Table Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class JQNA_Admin_Question_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'question',
            'plural' => 'questions',
            'ajax' => false,
        ));
    }
    
    /**
     * Prepare items for display
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $args = array(
            'post_type' => 'jqna_question',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        if ($status && in_array($status, array('publish', 'pending', 'draft'))) {
            $args['post_status'] = $status;
        }
        
        $query = new WP_Query($args);
        $this->items = $query->posts;
        
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
        ));
    }
    
    /**
     * Get columns
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Question', 'jqna_'),
            'category' => __('Category', 'jqna_'),
            'author' => __('Author', 'jqna_'),
            'status' => __('Status', 'jqna_'),
            'date' => __('Date', 'jqna_'),
            'actions' => __('Actions', 'jqna_'),
        );
    }
    
    /**
     * Get sortable columns
     */
    protected function get_sortable_columns() {
        return array(
            'title' => array('title', false),
            'date' => array('date', false),
        );
    }
    
    /**
     * Default column handler
     */
    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'category':
                $terms = wp_get_post_terms($item->ID, 'jqna_category', array('fields' => 'names'));
                return !empty($terms) ? esc_html(implode(', ', $terms)) : '—';
            case 'author':
                $author = get_user_by('id', $item->post_author);
                return $author ? esc_html($author->display_name) : '—';
            case 'status':
                $statuses = array(
                    'publish' => __('Approved', 'jqna_'),
                    'pending' => __('Pending', 'jqna_'),
                    'draft' => __('Draft', 'jqna_'),
                );
                return isset($statuses[$item->post_status]) ? esc_html($statuses[$item->post_status]) : esc_html($item->post_status);
            case 'date':
                return get_the_date('', $item);
            default:
                return print_r($item, true);
        }
    }
    
    /**
     * Column title
     */
    protected function column_title($item) {
        $edit_url = add_query_arg(array('page' => 'jqna-add-question', 'edit' => $item->ID), admin_url('admin.php'));
        $delete_url = add_query_arg(array('action' => 'delete', 'question_id' => $item->ID, '_wpnonce' => wp_create_nonce('jqna_delete_question')), admin_url('admin.php?page=jqna-questions'));
        
        $actions = array(
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'jqna_')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\')">%s</a>', esc_url($delete_url), __('Are you sure?', 'jqna_'), __('Delete', 'jqna_')),
        );
        
        return sprintf('<strong><a href="%s">%s</a></strong> %s', esc_url($edit_url), esc_html($item->post_title), $this->row_actions($actions));
    }
    
    /**
     * Column checkbox
     */
    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="question_ids[]" value="%d" />', $item->ID);
    }
    
    /**
     * Column actions
     */
    protected function column_actions($item) {
        $answer = get_post_meta($item->ID, '_jqna_answer', true);
        return !empty($answer) ? '<span class="jqna-has-answer">✓ ' . __('Answered', 'jqna_') . '</span>' : '<span class="jqna-no-answer">✗ ' . __('Not Answered', 'jqna_') . '</span>';
    }
    
    /**
     * Bulk actions
     */
    protected function get_bulk_actions() {
        return array(
            'publish' => __('Approve', 'jqna_'),
            'pending' => __('Set Pending', 'jqna_'),
            'delete' => __('Delete', 'jqna_'),
        );
    }
}