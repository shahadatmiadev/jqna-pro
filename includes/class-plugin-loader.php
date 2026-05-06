<?php
/**
 * Plugin Loader Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Plugin_Loader {
    
    /**
     * Initialize all plugin components
     */
    public function init() {
        // Initialize admin
        if (is_admin()) {
            new JQNA_Admin_Menu();
        }
        
        // Initialize frontend
        new JQNA_Shortcode_Handler();
        
        // Initialize AJAX handlers
        add_action('wp_ajax_jqna_filter_questions', array($this, 'handle_ajax_filter'));
        add_action('wp_ajax_nopriv_jqna_filter_questions', array($this, 'handle_ajax_filter'));
        add_action('wp_ajax_jqna_submit_question', array($this, 'handle_ajax_submit'));
        add_action('wp_ajax_nopriv_jqna_submit_question', array($this, 'handle_ajax_submit'));
    }
    
    /**
     * Handle AJAX filter
     */
    public function handle_ajax_filter() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jqna_ajax_nonce')) {
            wp_die('Security check failed', '', array('response' => 403));
        }
        
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        
        $questions = JQNA_Question_Manager::get_approved_questions($category_id, $paged);
        $total = JQNA_Question_Manager::get_questions_count($category_id);
        
        ob_start();
        if (!empty($questions)) {
            foreach ($questions as $question) {
                $answer = get_post_meta($question->ID, '_jqna_answer', true);
                ?>
                <div class="jqna-accordion-item">
                    <div class="jqna-accordion-header">
                        <h3><?php echo esc_html($question->post_title); ?></h3>
                        <span class="jqna-toggle-icon">+</span>
                    </div>
                    <div class="jqna-accordion-content" style="display: none;">
                        <div class="jqna-question-content">
                            <?php echo wp_kses_post($question->post_content); ?>
                        </div>
                        <?php if ($answer) : ?>
                            <div class="jqna-answer-content">
                                <strong><?php esc_html_e('Answer:', 'jqna_'); ?></strong>
                                <?php echo wp_kses_post($answer); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="jqna-no-questions">
                <?php esc_html_e('No questions found.', 'jqna_'); ?>
            </div>
            <?php
        }
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'total' => $total,
            'max_pages' => ceil($total / 10)
        ));
    }
    
    /**
     * Handle AJAX question submission
     */
    public function handle_ajax_submit() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jqna_submit_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'jqna_')));
        }
        
        // Verify user is logged in and has access
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login to submit questions', 'jqna_')));
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        
        if (empty($title) || empty($content)) {
            wp_send_json_error(array('message' => __('Please fill in all fields', 'jqna_')));
        }
        
        $result = JQNA_Question_Manager::submit_question(get_current_user_id(), $title, $content);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Question submitted successfully!', 'jqna_')));
        } else {
            wp_send_json_error(array('message' => __('Error submitting question', 'jqna_')));
        }
    }
}