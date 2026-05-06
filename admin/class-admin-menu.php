<?php
/**
 * Admin Menu Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Admin_Menu {
    
    private $question_list_table;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'handle_question_actions'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('Qnario', 'jqna_'),
            __('Qnario', 'jqna_'),
            'manage_options',
            'jqna-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-format-chat',
            30
        );
        
        // All Questions submenu
        add_submenu_page(
            'jqna-dashboard',
            __('All Questions', 'jqna_'),
            __('All Questions', 'jqna_'),
            'manage_options',
            'jqna-questions',
            array($this, 'render_questions_page')
        );
        
        // Add New submenu
        add_submenu_page(
            'jqna-dashboard',
            __('Add New Question', 'jqna_'),
            __('Add New Question', 'jqna_'),
            'manage_options',
            'jqna-add-question',
            array($this, 'render_add_question_page')
        );
        
        // Categories submenu
        add_submenu_page(
            'jqna-dashboard',
            __('Categories', 'jqna_'),
            __('Categories', 'jqna_'),
            'manage_options',
            'edit-tags.php?taxonomy=jqna_category'
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'jqna') !== false) {
            wp_enqueue_style('jqna-admin-style', JQNA_PLUGIN_URL . 'admin/css/admin-style.css', array(), JQNA_VERSION);
            wp_enqueue_editor();
            wp_enqueue_media();
        }
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $stats = array(
            'total' => wp_count_posts('jqna_question'),
            'categories' => wp_count_terms('jqna_category'),
        );
        ?>
        <div class="wrap jqna-admin-wrap">
            <h1><?php esc_html_e('Qnario Dashboard', 'jqna_'); ?></h1>
            
            <div class="jqna-stats-grid">
                <div class="jqna-stat-card">
                    <h3><?php esc_html_e('Total Questions', 'jqna_'); ?></h3>
                    <div class="jqna-stat-number">
                        <?php echo intval($stats['total']->publish + $stats['total']->pending); ?>
                    </div>
                </div>
                
                <div class="jqna-stat-card">
                    <h3><?php esc_html_e('Published', 'jqna_'); ?></h3>
                    <div class="jqna-stat-number"><?php echo intval($stats['total']->publish); ?></div>
                </div>
                
                <div class="jqna-stat-card">
                    <h3><?php esc_html_e('Pending', 'jqna_'); ?></h3>
                    <div class="jqna-stat-number"><?php echo intval($stats['total']->pending); ?></div>
                </div>
                
                <div class="jqna-stat-card">
                    <h3><?php esc_html_e('Categories', 'jqna_'); ?></h3>
                    <div class="jqna-stat-number"><?php echo intval($stats['categories']); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render questions page
     */
    public function render_questions_page() {
        if (!class_exists('JQNA_Admin_Question_List_Table')) {
            require_once JQNA_PLUGIN_DIR . 'admin/class-question-list-table.php';
        }
        
        $this->question_list_table = new JQNA_Admin_Question_List_Table();
        $this->question_list_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('All Questions', 'jqna_'); ?></h1>
            <form method="post">
                <?php
                $this->question_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render add question page
     */
    public function render_add_question_page() {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $question = null;
        
        if ($edit_id) {
            $question = get_post($edit_id);
        }
        ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? esc_html__('Edit Question', 'jqna_') : esc_html__('Add New Question', 'jqna_'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('jqna_save_question', 'jqna_question_nonce'); ?>
                <input type="hidden" name="question_id" value="<?php echo esc_attr($edit_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="question_title"><?php esc_html_e('Question Title', 'jqna_'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="question_title" name="question_title" class="regular-text" 
                                   value="<?php echo $question ? esc_attr($question->post_title) : ''; ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_content"><?php esc_html_e('Question Content', 'jqna_'); ?></label>
                        </th>
                        <td>
                            <textarea id="question_content" name="question_content" rows="5" class="large-text"><?php 
                                echo $question ? esc_textarea($question->post_content) : ''; 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_answer"><?php esc_html_e('Answer', 'jqna_'); ?></label>
                        </th>
                        <td>
                            <?php 
                            $answer = $question ? get_post_meta($question->ID, '_jqna_answer', true) : '';
                            wp_editor($answer, 'question_answer', array(
                                'textarea_name' => 'question_answer',
                                'textarea_rows' => 10,
                                'media_buttons' => true,
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_category"><?php esc_html_e('Category', 'jqna_'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_categories(array(
                                'taxonomy' => 'jqna_category',
                                'name' => 'question_category',
                                'id' => 'question_category',
                                'hide_empty' => false,
                                'selected' => $question ? wp_get_post_terms($question->ID, 'jqna_category', array('fields' => 'ids'))[0] ?? 0 : 0,
                                'show_option_none' => __('Select Category', 'jqna_'),
                            ));
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="question_status"><?php esc_html_e('Status', 'jqna_'); ?></label>
                        </th>
                        <td>
                            <select name="question_status" id="question_status">
                                <option value="pending" <?php selected($question ? $question->post_status : 'pending', 'pending'); ?>>
                                    <?php esc_html_e('Pending', 'jqna_'); ?>
                                </option>
                                <option value="publish" <?php selected($question ? $question->post_status : '', 'publish'); ?>>
                                    <?php esc_html_e('Approved', 'jqna_'); ?>
                                </option>
                                <option value="draft" <?php selected($question ? $question->post_status : '', 'draft'); ?>>
                                    <?php esc_html_e('Draft', 'jqna_'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button($edit_id ? __('Update Question', 'jqna_') : __('Add Question', 'jqna_')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle question save/update actions
     */
    public function handle_question_actions() {
        if (!isset($_POST['jqna_question_nonce']) || !wp_verify_nonce($_POST['jqna_question_nonce'], 'jqna_save_question')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $title = isset($_POST['question_title']) ? sanitize_text_field($_POST['question_title']) : '';
        $content = isset($_POST['question_content']) ? wp_kses_post($_POST['question_content']) : '';
        $answer = isset($_POST['question_answer']) ? wp_kses_post($_POST['question_answer']) : '';
        $status = isset($_POST['question_status']) ? sanitize_text_field($_POST['question_status']) : 'pending';
        $category = isset($_POST['question_category']) ? intval($_POST['question_category']) : 0;
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
            'post_type' => 'jqna_question',
        );
        
        if ($question_id) {
            $post_data['ID'] = $question_id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_data['post_author'] = get_current_user_id();
            $post_id = wp_insert_post($post_data);
        }
        
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_jqna_answer', $answer);
            
            if ($category) {
                wp_set_post_terms($post_id, array($category), 'jqna_category');
            }
            
            $redirect_url = add_query_arg(array('page' => 'jqna-questions', 'message' => 'saved'), admin_url('admin.php'));
            wp_redirect($redirect_url);
            exit;
        }
    }
}