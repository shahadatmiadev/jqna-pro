<?php
/**
 * Plugin Name: Qnario
 * Plugin URI: https://yourwebsite.com/qnario
 * Description: Secure Q&A access system with WooCommerce order validation
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * Text Domain: jqna_
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 *
 * @package Qnario
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JQNA_VERSION', '1.0.0');
define('JQNA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JQNA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JQNA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files manually
require_once JQNA_PLUGIN_DIR . 'includes/class-question-manager.php';
require_once JQNA_PLUGIN_DIR . 'includes/class-woocommerce-validator.php';
require_once JQNA_PLUGIN_DIR . 'includes/class-plugin-loader.php';

// Include admin files (only in admin)
if (is_admin()) {
    require_once JQNA_PLUGIN_DIR . 'admin/class-admin-menu.php';
    require_once JQNA_PLUGIN_DIR . 'admin/class-question-list-table.php';
}

// Include frontend files
require_once JQNA_PLUGIN_DIR . 'frontend/class-shortcode-handler.php';
require_once JQNA_PLUGIN_DIR . 'frontend/class-access-form.php';
require_once JQNA_PLUGIN_DIR . 'frontend/class-submission-form.php';

// Register post type and taxonomy on init (important!)
add_action('init', 'jqna_register_post_types');
function jqna_register_post_types() {
    JQNA_Question_Manager::register_post_type();
    JQNA_Question_Manager::register_taxonomy();
}

register_activation_hook(__FILE__, 'jqna_activate_plugin');
function jqna_activate_plugin() {
    jqna_register_post_types();
    flush_rewrite_rules();
    
    // Create default category
    if (!term_exists('Islamic', 'jqna_category')) {
        wp_insert_term('Islamic', 'jqna_category', array(
            'description' => 'Default Islamic category',
            'slug' => 'islamic'
        ));
    }
}

register_deactivation_hook(__FILE__, 'jqna_deactivate_plugin');
function jqna_deactivate_plugin() {
    flush_rewrite_rules();
}

add_action('plugins_loaded', 'jqna_init_plugin');
function jqna_init_plugin() {
    load_plugin_textdomain('jqna_', false, dirname(JQNA_PLUGIN_BASENAME) . '/languages');
    
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Qnario requires WooCommerce.</p></div>';
        });
        return;
    }
    
    $loader = new JQNA_Plugin_Loader();
    $loader->init();
}