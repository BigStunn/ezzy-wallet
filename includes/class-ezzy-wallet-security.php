<?php
/**
 * Security helper class for eZZY Wallet.
 *
 * Provides security utilities, rate limiting, and audit logging.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Security
 *
 * Handles security-related functionality.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Security {

    /**
     * Rate limit window in seconds (5 minutes).
     *
     * @var int
     */
    const RATE_LIMIT_WINDOW = 300;

    /**
     * Maximum deposit requests per window.
     *
     * @var int
     */
    const MAX_DEPOSIT_REQUESTS = 5;

    /**
     * Maximum withdrawal requests per window.
     *
     * @var int
     */
    const MAX_WITHDRAWAL_REQUESTS = 3;

    /**
     * Maximum transfer requests per window.
     *
     * @var int
     */
    const MAX_TRANSFER_REQUESTS = 10;

    /**
     * Maximum profile updates per window.
     *
     * @var int
     */
    const MAX_PROFILE_UPDATES = 10;

    /**
     * Maximum amount for a single transaction.
     *
     * @var float
     */
    const MAX_TRANSACTION_AMOUNT = 1000000.00;

    /**
     * Minimum amount for a transaction.
     *
     * @var float
     */
    const MIN_TRANSACTION_AMOUNT = 0.01;

    /**
     * Audit log option key.
     *
     * @var string
     */
    const AUDIT_LOG_KEY = 'ezzy_wallet_security_audit_log';

    /**
     * Maximum audit log entries to keep.
     *
     * @var int
     */
    const MAX_AUDIT_LOG_ENTRIES = 2000;

    /**
     * Verify nonce.
     *
     * @param string $nonce  Nonce value to verify.
     * @param string $action Nonce action name.
     * @return bool True if valid, false otherwise.
     */
    public static function verify_nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Check if current user has admin capability.
     *
     * @return bool True if user can manage wallet.
     */
    public static function current_user_can_manage() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if current user can view all accounts.
     *
     * @return bool True if user can view all accounts.
     */
    public static function current_user_can_view_all_accounts() {
        return current_user_can( 'ezzy_wallet_view_all_accounts' ) || current_user_can( 'manage_options' );
    }

    /**
     * Check if current user can edit accounts.
     *
     * @return bool True if user can edit accounts.
     */
    public static function current_user_can_edit_accounts() {
        return current_user_can( 'ezzy_wallet_edit_accounts' ) || current_user_can( 'manage_options' );
    }

    /**
     * Check if current user can view transactions.
     *
     * @return bool True if user can view transactions.
     */
    public static function current_user_can_view_transactions() {
        return current_user_can( 'ezzy_wallet_view_transactions' ) || current_user_can( 'manage_options' );
    }

    /**
     * Check if current user can manage settings.
     *
     * @return bool True if user can manage settings.
     */
    public static function current_user_can_manage_settings() {
        return current_user_can( 'ezzy_wallet_manage_settings' ) || current_user_can( 'manage_options' );
    }

    /**
     * Check if current user can access their own wallet.
     *
     * @param int $user_id User ID to check.
     * @return bool True if user can access wallet.
     */
    public static function current_user_can_access_wallet( $user_id ) {
        $current_user_id = get_current_user_id();

        // User can access their own wallet.
        if ( $current_user_id === (int) $user_id ) {
            return true;
        }

        // Admins can access any wallet.
        if ( self::current_user_can_manage() ) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize and validate user ID.
     *
     * @param mixed $user_id User ID to validate.
     * @return int|false Sanitized user ID or false if invalid.
     */
    public static function validate_user_id( $user_id ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            return false;
        }

        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return false;
        }

        return $user_id;
    }

    /**
     * Validate and sanitize amount with comprehensive checks.
     *
     * @param mixed $amount Raw amount input.
     * @return array Validation result with sanitized amount.
     */
    public static function validate_amount( $amount ) {
        // Must be numeric
        if ( ! is_numeric( $amount ) ) {
            return array(
                'valid'   => false,
                'amount'  => 0,
                'message' => __( 'Amount must be a valid number.', 'ezzy-wallet' ),
            );
        }

        $amount = floatval( $amount );

        // Must be positive
        if ( $amount <= 0 ) {
            return array(
                'valid'   => false,
                'amount'  => 0,
                'message' => __( 'Amount must be greater than zero.', 'ezzy-wallet' ),
            );
        }

        // Must meet minimum
        if ( $amount < self::MIN_TRANSACTION_AMOUNT ) {
            return array(
                'valid'   => false,
                'amount'  => 0,
                'message' => sprintf(
                    __( 'Amount must be at least $%s.', 'ezzy-wallet' ),
                    number_format( self::MIN_TRANSACTION_AMOUNT, 2 )
                ),
            );
        }

        // Must not exceed maximum
        if ( $amount > self::MAX_TRANSACTION_AMOUNT ) {
            return array(
                'valid'   => false,
                'amount'  => 0,
                'message' => sprintf(
                    __( 'Amount cannot exceed $%s.', 'ezzy-wallet' ),
                    number_format( self::MAX_TRANSACTION_AMOUNT, 2 )
                ),
            );
        }

        // Round to 2 decimal places
        $amount = round( $amount, 2 );

        return array(
            'valid'   => true,
            'amount'  => $amount,
            'message' => '',
        );
    }

    /**
     * Sanitize monetary amount (legacy method).
     *
     * @param mixed $amount Amount to sanitize.
     * @return string Sanitized amount string.
     */
    public static function sanitize_amount( $amount ) {
        // Remove any non-numeric characters except decimal point and minus.
        $amount = preg_replace( '/[^0-9.\-]/', '', $amount );

        // Ensure valid decimal format.
        $amount = floatval( $amount );

        // Format to 8 decimal places.
        return number_format( $amount, 8, '.', '' );
    }

    /**
     * Sanitize transaction type.
     *
     * @param string $type Transaction type.
     * @return string Sanitized type or empty string if invalid.
     */
    public static function sanitize_transaction_type( $type ) {
        $valid_types = array(
            'deposit',
            'deposit_request',
            'withdrawal',
            'withdrawal_request',
            'transfer_in',
            'transfer_out',
            'transfer_sent',
            'transfer_received',
            'adjustment',
            'refund',
            'fee',
        );

        $type = sanitize_text_field( strtolower( $type ) );

        if ( in_array( $type, $valid_types, true ) ) {
            return $type;
        }

        return '';
    }

    /**
     * Sanitize transaction status.
     *
     * @param string $status Transaction status.
     * @return string Sanitized status or 'pending' if invalid.
     */
    public static function sanitize_transaction_status( $status ) {
        $valid_statuses = array(
            'pending',
            'completed',
            'approved',
            'rejected',
            'failed',
            'cancelled',
            'processing',
        );

        $status = sanitize_text_field( strtolower( $status ) );

        if ( in_array( $status, $valid_statuses, true ) ) {
            return $status;
        }

        return 'pending';
    }

    /**
     * Sanitize payment method.
     *
     * @param string $method Raw method input.
     * @return string Sanitized method or empty string if invalid.
     */
    public static function sanitize_payment_method( $method ) {
        $valid_methods = array(
            'bank_transfer',
            'paypal',
            'crypto',
            'wire_transfer',
            'cash',
            'other',
        );

        $method = sanitize_text_field( strtolower( $method ) );

        if ( in_array( $method, $valid_methods, true ) ) {
            return $method;
        }

        return '';
    }

    /**
     * Check rate limit for an action.
     *
     * @param int    $user_id     User ID.
     * @param string $action_type Type of action.
     * @param int    $max_allowed Maximum allowed in window.
     * @return array Rate limit result.
     */
    public static function check_rate_limit( $user_id, $action_type, $max_allowed ) {
        $transient_key = sprintf( 'ezzy_rate_%s_%d', $action_type, $user_id );
        $current_count = get_transient( $transient_key );

        if ( $current_count === false ) {
            // First request in window
            set_transient( $transient_key, 1, self::RATE_LIMIT_WINDOW );
            return array(
                'allowed'   => true,
                'remaining' => $max_allowed - 1,
                'message'   => '',
            );
        }

        $current_count = intval( $current_count );

        if ( $current_count >= $max_allowed ) {
            return array(
                'allowed'   => false,
                'remaining' => 0,
                'message'   => __( 'Too many requests. Please wait a few minutes before trying again.', 'ezzy-wallet' ),
            );
        }

        // Increment counter
        set_transient( $transient_key, $current_count + 1, self::RATE_LIMIT_WINDOW );

        return array(
            'allowed'   => true,
            'remaining' => $max_allowed - $current_count - 1,
            'message'   => '',
        );
    }

    /**
     * Check deposit rate limit.
     *
     * @param int $user_id User ID.
     * @return array Rate limit result.
     */
    public static function check_deposit_rate_limit( $user_id ) {
        return self::check_rate_limit( $user_id, 'deposit', self::MAX_DEPOSIT_REQUESTS );
    }

    /**
     * Check withdrawal rate limit.
     *
     * @param int $user_id User ID.
     * @return array Rate limit result.
     */
    public static function check_withdrawal_rate_limit( $user_id ) {
        return self::check_rate_limit( $user_id, 'withdrawal', self::MAX_WITHDRAWAL_REQUESTS );
    }

    /**
     * Check transfer rate limit.
     *
     * @param int $user_id User ID.
     * @return array Rate limit result.
     */
    public static function check_transfer_rate_limit( $user_id ) {
        return self::check_rate_limit( $user_id, 'transfer', self::MAX_TRANSFER_REQUESTS );
    }

    /**
     * Check profile update rate limit.
     *
     * @param int $user_id User ID.
     * @return array Rate limit result.
     */
    public static function check_profile_rate_limit( $user_id ) {
        return self::check_rate_limit( $user_id, 'profile', self::MAX_PROFILE_UPDATES );
    }

    /**
     * Verify sufficient balance for withdrawal/transfer.
     *
     * @param int   $user_id User ID.
     * @param float $amount  Amount required.
     * @return array Verification result.
     */
    public static function verify_sufficient_balance( $user_id, $amount ) {
        $balance = Ezzy_Wallet_Data_Service::get_user_balance( $user_id );
        
        if ( $balance === null || $balance === false ) {
            return array(
                'sufficient' => false,
                'balance'    => 0,
                'message'    => __( 'Could not retrieve wallet balance.', 'ezzy-wallet' ),
            );
        }

        $balance = floatval( $balance );
        $amount = floatval( $amount );

        if ( $amount > $balance ) {
            return array(
                'sufficient' => false,
                'balance'    => $balance,
                'message'    => sprintf(
                    __( 'Insufficient balance. You have $%s available.', 'ezzy-wallet' ),
                    number_format( $balance, 2 )
                ),
            );
        }

        return array(
            'sufficient' => true,
            'balance'    => $balance,
            'message'    => '',
        );
    }

    /**
     * Check if transaction is already processed.
     *
     * @param array $transaction Transaction data.
     * @return bool True if already processed.
     */
    public static function is_transaction_processed( $transaction ) {
        $final_statuses = array( 'approved', 'completed', 'rejected' );
        return in_array( $transaction['status'], $final_statuses, true );
    }

    /**
     * Validate transaction ID and get transaction data.
     *
     * @param mixed $transaction_id Raw transaction ID.
     * @return array Validation result with transaction data.
     */
    public static function validate_transaction_id( $transaction_id ) {
        global $wpdb;

        $transaction_id = absint( $transaction_id );

        if ( $transaction_id <= 0 ) {
            return array(
                'valid'       => false,
                'transaction' => null,
                'message'     => __( 'Invalid transaction ID.', 'ezzy-wallet' ),
            );
        }

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();
        $transaction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $transaction_id
            ),
            ARRAY_A
        );

        if ( ! $transaction ) {
            return array(
                'valid'       => false,
                'transaction' => null,
                'message'     => __( 'Transaction not found.', 'ezzy-wallet' ),
            );
        }

        return array(
            'valid'       => true,
            'transaction' => $transaction,
            'message'     => '',
        );
    }

    /**
     * Get client IP address.
     *
     * @return string Client IP address.
     */
    public static function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        // Handle comma-separated IPs (from proxies).
        if ( strpos( $ip, ',' ) !== false ) {
            $ips = explode( ',', $ip );
            $ip  = trim( $ips[0] );
        }

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : 'unknown';
    }

    /**
     * Log audit event.
     *
     * @param string $action      Action performed.
     * @param int    $user_id     User ID affected.
     * @param array  $details     Additional details.
     * @param int    $admin_id    Admin ID (if admin action).
     * @return void
     */
    public static function log_audit_event( $action, $user_id, $details = array(), $admin_id = 0 ) {
        $logs = get_option( self::AUDIT_LOG_KEY, array() );

        $entry = array(
            'timestamp'  => current_time( 'mysql' ),
            'action'     => sanitize_text_field( $action ),
            'user_id'    => absint( $user_id ),
            'admin_id'   => absint( $admin_id ),
            'ip_address' => self::get_client_ip(),
            'details'    => $details,
        );

        // Add to beginning of array
        array_unshift( $logs, $entry );

        // Trim to max entries
        if ( count( $logs ) > self::MAX_AUDIT_LOG_ENTRIES ) {
            $logs = array_slice( $logs, 0, self::MAX_AUDIT_LOG_ENTRIES );
        }

        update_option( self::AUDIT_LOG_KEY, $logs, false );
    }

    /**
     * Get recent audit logs.
     *
     * @param int    $limit  Number of entries to return.
     * @param string $action Filter by action type.
     * @return array Audit log entries.
     */
    public static function get_audit_logs( $limit = 100, $action = '' ) {
        $logs = get_option( self::AUDIT_LOG_KEY, array() );

        if ( ! empty( $action ) ) {
            $logs = array_filter( $logs, function( $log ) use ( $action ) {
                return $log['action'] === $action;
            });
        }

        return array_slice( $logs, 0, $limit );
    }

    /**
     * Log security event (legacy method).
     *
     * @param string $event   Event type.
     * @param string $message Event message.
     * @param array  $data    Additional data.
     * @return void
     */
    public static function log_event( $event, $message, $data = array() ) {
        // Log to audit system
        self::log_audit_event( $event, get_current_user_id(), array(
            'message' => $message,
            'data'    => $data,
        ) );

        // Also log to error log if debug is enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_data = array(
                'event'     => $event,
                'message'   => $message,
                'user_id'   => get_current_user_id(),
                'ip'        => self::get_client_ip(),
                'timestamp' => current_time( 'mysql' ),
                'data'      => $data,
            );

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( '[eZZY Wallet] ' . wp_json_encode( $log_data ) );
        }
    }

    /**
     * Generate a secure random token.
     *
     * @param int $length Token length.
     * @return string Random token.
     */
    public static function generate_token( $length = 32 ) {
        return bin2hex( random_bytes( $length / 2 ) );
    }

    /**
     * Hash sensitive data.
     *
     * @param string $data Data to hash.
     * @return string Hashed data.
     */
    public static function hash_data( $data ) {
        return hash( 'sha256', $data . wp_salt( 'auth' ) );
    }

    /**
     * Verify hashed data.
     *
     * @param string $data Data to verify.
     * @param string $hash Expected hash.
     * @return bool True if match.
     */
    public static function verify_hash( $data, $hash ) {
        return hash_equals( self::hash_data( $data ), $hash );
    }
}
