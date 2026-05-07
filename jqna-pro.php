<?php
/**
 * Plugin Name: JQNA Pro - Q&A System
 * Plugin URI:  https://wordpress.org/plugins/jqna-pro/
 * Description: A secure, WooCommerce-verified Question & Answer system with category filtering, AJAX pagination, and admin moderation.
 * Version:     1.0.0
 * Author:      Shahadat Mia
 * Author URI:  https://yourwebsite.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jqna-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 *
 * @package JQNA_Pro
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'JQNA_PRO_VERSION',    '1.0.0' );
define( 'JQNA_PRO_FILE',       __FILE__ );
define( 'JQNA_PRO_DIR',        plugin_dir_path( __FILE__ ) );
define( 'JQNA_PRO_URL',        plugin_dir_url( __FILE__ ) );
define( 'JQNA_PRO_BASENAME',   plugin_basename( __FILE__ ) );

// Require files.
require_once JQNA_PRO_DIR . 'includes/class-jqna-post-type.php';
require_once JQNA_PRO_DIR . 'includes/class-jqna-auth.php';
require_once JQNA_PRO_DIR . 'includes/class-jqna-woo-validator.php';
require_once JQNA_PRO_DIR . 'includes/class-jqna-ajax.php';
require_once JQNA_PRO_DIR . 'frontend/class-jqna-shortcode.php';

if ( is_admin() ) {
	require_once JQNA_PRO_DIR . 'admin/class-jqna-admin.php';
}

/**
 * Main plugin bootstrap class.
 */
final class JQNA_Pro {

	/**
	 * Single instance.
	 *
	 * @var JQNA_Pro
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return JQNA_Pro
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( JQNA_PRO_FILE,   array( $this, 'activate' ) );
		register_deactivation_hook( JQNA_PRO_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialise components.
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain(
			'jqna-pro',
			false,
			dirname( JQNA_PRO_BASENAME ) . '/languages'
		);

		// WooCommerce dependency check.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_woo_missing' ) );
			return;
		}

		// Boot components.
		new JQNA_Post_Type();
		new JQNA_Auth();
		new JQNA_Ajax();
		new JQNA_Shortcode();

		if ( is_admin() ) {
			new JQNA_Admin();
		}
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function notice_woo_missing() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__(
			'JQNA Pro requires WooCommerce to be installed and activated.',
			'jqna-pro'
		);
		echo '</p></div>';
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Create default "Islamic" category.
		if ( ! taxonomy_exists( 'jqna_category' ) ) {
			// Register temporarily so wp_insert_term works.
			$post_type = new JQNA_Post_Type();
			$post_type->register_all();
		}

		$existing = get_terms(
			array(
				'taxonomy'   => 'jqna_category',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( empty( $existing ) || is_wp_error( $existing ) ) {
			wp_insert_term(
				'Islamic',
				'jqna_category',
				array(
					'slug'        => 'islamic',
					'description' => esc_html__( 'Default Islamic Q&A category', 'jqna-pro' ),
				)
			);
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}

// Boot.
JQNA_Pro::instance();
