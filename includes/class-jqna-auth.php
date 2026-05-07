<?php
/**
 * Handles access authentication via an encrypted cookie.
 * Deliberately avoids PHP sessions for better WordPress.org compatibility.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Auth
 */
class JQNA_Auth {

	/**
	 * Cookie name.
	 */
	const COOKIE_NAME = 'jqna_pro_access';

	/**
	 * Cookie lifetime in seconds (30 days).
	 */
	const COOKIE_TTL = 2592000;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init',      array( $this, 'handle_logout' ) );
		add_action( 'wp_loaded', array( $this, 'handle_login_form' ) );
	}

	// ------------------------------------------------------------------
	// Public API
	// ------------------------------------------------------------------

	/**
	 * Return true if the current visitor has valid access.
	 *
	 * @return bool
	 */
	public static function has_access() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return false;
		}
		$payload = self::decrypt( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) );
		if ( ! $payload ) {
			return false;
		}
		$data = json_decode( $payload, true );
		if ( ! is_array( $data ) || empty( $data['uid'] ) || empty( $data['exp'] ) ) {
			return false;
		}
		// Expired?
		if ( (int) $data['exp'] < time() ) {
			self::clear_cookie();
			return false;
		}
		return true;
	}

	/**
	 * Return the authenticated user ID, or 0.
	 *
	 * @return int
	 */
	public static function get_user_id() {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return 0;
		}
		$payload = self::decrypt( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) );
		if ( ! $payload ) {
			return 0;
		}
		$data = json_decode( $payload, true );
		return ( is_array( $data ) && ! empty( $data['uid'] ) ) ? (int) $data['uid'] : 0;
	}

	/**
	 * Set access cookie for the given user.
	 *
	 * @param int $user_id WordPress user ID.
	 */
	public static function set_cookie( $user_id ) {
		$exp     = time() + self::COOKIE_TTL;
		$payload = wp_json_encode(
			array(
				'uid' => absint( $user_id ),
				'exp' => $exp,
			)
		);
		$value   = self::encrypt( $payload );

		setcookie(
			self::COOKIE_NAME,
			$value,
			array(
				'expires'  => $exp,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Clear the access cookie.
	 */
	public static function clear_cookie() {
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			setcookie(
				self::COOKIE_NAME,
				'',
				array(
					'expires'  => time() - 3600,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
			unset( $_COOKIE[ self::COOKIE_NAME ] );
		}
	}

	// ------------------------------------------------------------------
	// Form handler
	// ------------------------------------------------------------------

	/**
	 * Process the login/access form.
	 */
	public function handle_login_form() {
		if ( ! isset( $_POST['jqna_login_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['jqna_login_nonce'] ) ), 'jqna_login' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'jqna-pro' ) );
		}

		$username = isset( $_POST['jqna_username'] ) ? sanitize_text_field( wp_unslash( $_POST['jqna_username'] ) ) : '';
		$password = isset( $_POST['jqna_password'] ) ? wp_unslash( $_POST['jqna_password'] ) : '';
		$phone    = isset( $_POST['jqna_phone'] )    ? sanitize_text_field( wp_unslash( $_POST['jqna_phone'] ) ) : '';

		if ( empty( $username ) || empty( $password ) || empty( $phone ) ) {
			$this->redirect_with_error( 'empty_fields' );
			return;
		}

		// Resolve user.
		$user_id = JQNA_Woo_Validator::get_subscriber_id( $username );
		if ( ! $user_id ) {
			$this->redirect_with_error( 'invalid_user' );
			return;
		}

		// Authenticate password.
		$user_obj = get_user_by( 'id', $user_id );
		$auth     = wp_authenticate( $user_obj->user_login, $password );
		if ( is_wp_error( $auth ) ) {
			$this->redirect_with_error( 'invalid_password' );
			return;
		}

		// Validate phone against completed WooCommerce orders.
		if ( ! JQNA_Woo_Validator::validate_completed_order_phone( $user_id, $phone ) ) {
			$this->redirect_with_error( 'invalid_phone' );
			return;
		}

		// Grant access.
		self::set_cookie( $user_id );

		$redirect = remove_query_arg( array( 'jqna_error', 'jqna_logout' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle logout request.
	 */
	public function handle_logout() {
		if ( isset( $_GET['jqna_logout'] ) && '1' === $_GET['jqna_logout'] ) {
			if ( ! isset( $_GET['jqna_logout_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['jqna_logout_nonce'] ) ), 'jqna_logout' ) ) {
				return;
			}
			self::clear_cookie();
			$redirect = remove_query_arg( array( 'jqna_logout', 'jqna_logout_nonce' ) );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	// ------------------------------------------------------------------
	// Private helpers
	// ------------------------------------------------------------------

	/**
	 * Redirect back with an error query arg.
	 *
	 * @param string $code Error code.
	 */
	private function redirect_with_error( $code ) {
		$redirect = add_query_arg( 'jqna_error', rawurlencode( $code ), wp_get_referer() ?: home_url() );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Simple symmetric encrypt (AES-256-CBC).
	 *
	 * @param string $data Plain text.
	 * @return string Base64-encoded cipher text.
	 */
	private static function encrypt( $data ) {
		$key    = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
		$iv     = openssl_random_pseudo_bytes( $iv_len );
		$cipher = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
		if ( false === $cipher ) {
			return base64_encode( $data ); // Fallback – still better than plain.
		}
		return base64_encode( $iv . $cipher );
	}

	/**
	 * Decrypt a value produced by self::encrypt().
	 *
	 * @param string $data Encrypted base64 string.
	 * @return string|false
	 */
	private static function decrypt( $data ) {
		$raw    = base64_decode( $data, true );
		if ( false === $raw ) {
			return false;
		}
		$key    = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
		if ( strlen( $raw ) <= $iv_len ) {
			return false;
		}
		$iv     = substr( $raw, 0, $iv_len );
		$cipher = substr( $raw, $iv_len );
		return openssl_decrypt( $cipher, 'AES-256-CBC', $key, 0, $iv );
	}
}
