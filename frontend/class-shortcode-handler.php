<?php
/**
 * Shortcode Handler Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Shortcode_Handler {
    
    private $access_form;
    private $submission_form;
    
    public function __construct() {
        $this->access_form = new JQNA_Access_Form();
        $this->submission_form = new JQNA_Submission_Form();
        
        add_shortcode('jqna_pro', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'jqna_pro')) {
            wp_enqueue_style('jqna-frontend-style', JQNA_PLUGIN_URL . 'frontend/css/frontend-style.css', array(), JQNA_VERSION);
            wp_enqueue_script('jqna-frontend-script', JQNA_PLUGIN_URL . 'frontend/js/frontend-script.js', array('jquery'), JQNA_VERSION, true);
            
            wp_localize_script('jqna-frontend-script', 'jqna_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('jqna_ajax_nonce'),
                'submit_nonce' => wp_create_nonce('jqna_submit_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'jqna_'),
                    'error' => __('An error occurred', 'jqna_'),
                    'prev' => __('Previous', 'jqna_'),
                    'next' => __('Next', 'jqna_'),
                ),
            ));
        }
    }
    
    /**
     * Render main shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
        ), $atts, 'jqna_pro');
        
        // Check if user has access
        if (!$this->check_user_access()) {
            return $this->access_form->render();
        }
        
        // Render Q&A system
        ob_start();
        ?>
        <div class="jqna-container">
            <div class="jqna-layout">
                <div class="jqna-left-column">
                    <div class="jqna-questions-list" data-paged="1" data-category="0">
                        <?php $this->render_questions_list(0, 1); ?>
                    </div>
                    <div class="jqna-pagination"></div>
                </div>
                
                <div class="jqna-right-column">
                    <div class="jqna-categories-list">
                        <h3><?php esc_html_e('Categories', 'jqna_'); ?></h3>
                        <button class="jqna-filter-reset" data-category="0">
                            <?php esc_html_e('All Categories', 'jqna_'); ?>
                        </button>
                        <?php $this->render_categories_list(); ?>
                    </div>
                </div>
            </div>
            
            <div class="jqna-submission-section">
                <h3><?php esc_html_e('Submit Your Question', 'jqna_'); ?></h3>
                <?php echo $this->submission_form->render(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check user access
     */
    private function check_user_access() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (isset($_SESSION['jqna_verified']) && $_SESSION['jqna_verified'] === true) {
            return true;
        }
        
        if (isset($_COOKIE['jqna_verified'])) {
            $_SESSION['jqna_verified'] = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Render questions list
     */
    private function render_questions_list($category_id, $paged) {
        $questions = JQNA_Question_Manager::get_approved_questions($category_id, $paged);
        
        if (empty($questions)) {
            echo '<div class="jqna-no-questions">' . esc_html__('No questions found.', 'jqna_') . '</div>';
            return;
        }
        
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
    }
    
    /**
     * Render categories list
     */
    private function render_categories_list() {
        $categories = JQNA_Question_Manager::get_categories_with_counts();
        
        foreach ($categories as $category) {
            ?>
            <button class="jqna-category-filter" data-category="<?php echo esc_attr($category['id']); ?>">
                <?php echo esc_html($category['name']); ?>
                <span class="count">(<?php echo intval($category['count']); ?>)</span>
            </button>
            <?php
        }
    }
}