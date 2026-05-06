<?php
/**
 * Access Form Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_Access_Form {
    
    public function __construct() {
        add_action('init', array($this, 'start_session'));
        add_action('wp_logout', array($this, 'clear_access'));
        add_action('init', array($this, 'handle_access_form'));
    }
    
    /**
     * Start session for access tracking
     */
    public function start_session() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Clear access on logout
     */
    public function clear_access() {
        if (isset($_SESSION['jqna_verified'])) {
            unset($_SESSION['jqna_verified']);
        }
        
        if (isset($_COOKIE['jqna_verified'])) {
            setcookie('jqna_verified', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
    
    /**
     * Handle access form submission
     */
    public function handle_access_form() {
        if (!isset($_POST['jqna_access_nonce']) || !wp_verify_nonce($_POST['jqna_access_nonce'], 'jqna_access_form')) {
            return;
        }
        
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        
        if (empty($username) || empty($password) || empty($phone)) {
            $this->set_error(__('All fields are required', 'jqna_'));
            return;
        }
        
        // Authenticate user
        $user_id = JQNA_WooCommerce_Validator::get_user_by_username_or_email($username);
        if (!$user_id) {
            $this->set_error(__('Invalid username/email or user is not a subscriber', 'jqna_'));
            return;
        }
        
        $user = wp_authenticate(get_user_by('id', $user_id)->user_login, $password);
        if (is_wp_error($user)) {
            $this->set_error(__('Invalid password', 'jqna_'));
            return;
        }
        
        // Validate phone with WooCommerce orders
        if (!JQNA_WooCommerce_Validator::validate_user_phone($user_id, $phone)) {
            $this->set_error(__('Phone number does not match any completed order', 'jqna_'));
            return;
        }
        
        // Set access
        $_SESSION['jqna_verified'] = true;
        setcookie('jqna_verified', '1', time() + (7 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        
        // Redirect to same page
        wp_redirect(get_permalink());
        exit;
    }
    
    /**
     * Set error message
     */
    private function set_error($message) {
        add_action('wp_footer', function() use ($message) {
            echo '<script>alert(' . json_encode($message) . ');</script>';
        });
    }
    
    /**
     * Render access form
     */
    public function render() {
        ob_start();
        ?>
        <div class="jqna-access-container">
            <div class="jqna-access-form">
                <h2><?php esc_html_e('Access Q&A System', 'jqna_'); ?></h2>
                <p><?php esc_html_e('Please verify your identity to access the Q&A content.', 'jqna_'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('jqna_access_form', 'jqna_access_nonce'); ?>
                    
                    <div class="jqna-form-group">
                        <label for="username"><?php esc_html_e('Username or Email', 'jqna_'); ?></label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="jqna-form-group">
                        <label for="password"><?php esc_html_e('Password', 'jqna_'); ?></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="jqna-form-group">
                        <label for="phone"><?php esc_html_e('WooCommerce Order Phone Number', 'jqna_'); ?></label>
                        <input type="tel" id="phone" name="phone" required>
                        <small><?php esc_html_e('Enter the phone number used in your completed orders', 'jqna_'); ?></small>
                    </div>
                    
                    <button type="submit" class="jqna-access-button">
                        <?php esc_html_e('Verify & Access', 'jqna_'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}