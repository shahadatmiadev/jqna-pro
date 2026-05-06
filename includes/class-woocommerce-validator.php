<?php
/**
 * WooCommerce Validator Class
 *
 * @package Qnario
 */

if (!defined('ABSPATH')) {
    exit;
}

class JQNA_WooCommerce_Validator {
    
    /**
     * Validate user by phone number from completed orders
     *
     * @param int $user_id User ID
     * @param string $phone Phone number to validate
     * @return bool
     */
    public static function validate_user_phone($user_id, $phone) {
        if (empty($user_id) || empty($phone)) {
            return false;
        }
        
        // Sanitize phone number
        $phone = sanitize_text_field($phone);
        
        // Query orders for this user with completed status
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => 'completed',
            'limit' => -1,
            'return' => 'ids'
        ));
        
        if (empty($orders)) {
            return false;
        }
        
        // Check each order for matching billing phone
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            
            $billing_phone = $order->get_billing_phone();
            if ($billing_phone === $phone) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get user by username or email
     *
     * @param string $username Username or email
     * @return int|false User ID or false
     */
    public static function get_user_by_username_or_email($username) {
        $username = sanitize_text_field($username);
        
        if (is_email($username)) {
            $user = get_user_by('email', $username);
        } else {
            $user = get_user_by('login', $username);
        }
        
        if ($user && !in_array('subscriber', (array) $user->roles)) {
            return false;
        }
        
        return $user ? $user->ID : false;
    }
}