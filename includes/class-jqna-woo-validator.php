<?php
/**
 * WooCommerce order phone validation.
 *
 * @package JQNA_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JQNA_Woo_Validator
 */
class JQNA_Woo_Validator {

	/**
	 * Resolve a subscriber user by username or email.
	 *
	 * @param string $input Username or email.
	 * @return int|false User ID or false.
	 */
	public static function get_subscriber_id( $input ) {
		$input = sanitize_text_field( $input );

		if ( is_email( $input ) ) {
			$user = get_user_by( 'email', $input );
		} else {
			$user = get_user_by( 'login', $input );
		}

		if ( ! $user || ! in_array( 'subscriber', (array) $user->roles, true ) ) {
			return false;
		}

		return $user->ID;
	}

	/**
	 * Check whether the given phone number belongs to any completed WooCommerce
	 * order placed by $user_id.
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $phone   Phone number to check.
	 * @return bool
	 */
	public static function validate_completed_order_phone( $user_id, $phone ) {
		$clean_input = self::normalise_phone( $phone );

		if ( empty( $clean_input ) ) {
			return false;
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => absint( $user_id ),
				'status'      => array( 'wc-completed' ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);

		foreach ( $orders as $order ) {
			$order_phone = self::normalise_phone( $order->get_billing_phone() );
			if ( self::phones_match( $order_phone, $clean_input ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strip non-digit characters and leading zeros / country codes.
	 *
	 * @param string $phone Raw phone string.
	 * @return string Digits only, stripped.
	 */
	private static function normalise_phone( $phone ) {
		// Keep digits only.
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		// Strip leading zero.
		$phone = ltrim( $phone, '0' );
		return $phone;
	}

	/**
	 * Flexible phone comparison (last 10 and last 9 digits).
	 *
	 * @param string $a Normalised phone A.
	 * @param string $b Normalised phone B.
	 * @return bool
	 */
	private static function phones_match( $a, $b ) {
		if ( $a === $b ) {
			return true;
		}
		// Compare last 10 digits.
		$a10 = substr( $a, -10 );
		$b10 = substr( $b, -10 );
		if ( ! empty( $a10 ) && $a10 === $b10 ) {
			return true;
		}
		// Compare last 9 digits (handles leading 0 variance).
		$a9 = substr( $a, -9 );
		$b9 = substr( $b, -9 );
		if ( ! empty( $a9 ) && $a9 === $b9 ) {
			return true;
		}
		return false;
	}
}
