<?php
/**
 * Data Service class for eZZY Wallet.
 *
 * Handles wallet data retrieval, currency conversion, and crypto prices.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Data_Service
 *
 * Manages data operations for the wallet dashboard.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Data_Service {

    /**
     * Exchange rate cache duration (1 hour).
     */
    const EXCHANGE_RATE_CACHE_DURATION = 3600;

    /**
     * Crypto price cache duration (10 minutes).
     */
    const CRYPTO_PRICE_CACHE_DURATION = 600;

    /**
     * Exchange rate transient key.
     */
    const EXCHANGE_RATE_TRANSIENT = 'ezzy_wallet_exchange_rates';

    /**
     * Crypto price transient key.
     */
    const CRYPTO_PRICE_TRANSIENT = 'ezzy_wallet_crypto_prices';

    /**
     * Supported currencies.
     *
     * @var array
     */
    private static $supported_currencies = array( 'USD', 'EUR', 'GBP' );

    /**
     * Currency symbols.
     *
     * @var array
     */
    private static $currency_symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
    );

    /**
     * Get user's wallet account data.
     *
     * @param int $user_id User ID.
     * @return array|null Wallet account data or null.
     */
    public static function get_user_wallet( $user_id ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return $account;
    }

    /**
     * Get user's wallet balance.
     *
     * @param int $user_id User ID.
     * @return float Balance in USD.
     */
    public static function get_user_balance( $user_id ) {
        $wallet = self::get_user_wallet( $user_id );
        return $wallet ? (float) $wallet['balance'] : 0.0;
    }

    /**
     * Get admin default currency.
     *
     * @return string Currency code (USD, EUR, GBP).
     */
    public static function get_admin_default_currency() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $currency = isset( $settings['default_currency'] ) ? $settings['default_currency'] : 'USD';
        return in_array( $currency, self::$supported_currencies, true ) ? $currency : 'USD';
    }

    /**
     * Get user's preferred currency.
     *
     * @param int $user_id User ID.
     * @return string Currency code.
     */
    public static function get_user_currency_preference( $user_id ) {
        $preference = get_user_meta( $user_id, 'ezzy_wallet_currency_preference', true );
        
        if ( empty( $preference ) || ! in_array( $preference, self::$supported_currencies, true ) ) {
            return self::get_admin_default_currency();
        }
        
        return $preference;
    }

    /**
     * Set user's currency preference.
     *
     * @param int    $user_id  User ID.
     * @param string $currency Currency code.
     * @return bool True on success.
     */
    public static function set_user_currency_preference( $user_id, $currency ) {
        if ( ! in_array( $currency, self::$supported_currencies, true ) ) {
            return false;
        }
        
        return update_user_meta( $user_id, 'ezzy_wallet_currency_preference', $currency );
    }

    /**
     * Get currency symbol.
     *
     * @param string $currency Currency code.
     * @return string Currency symbol.
     */
    public static function get_currency_symbol( $currency ) {
        return isset( self::$currency_symbols[ $currency ] ) ? self::$currency_symbols[ $currency ] : '$';
    }

    /**
     * Get supported currencies.
     *
     * @return array Supported currencies.
     */
    public static function get_supported_currencies() {
        return self::$supported_currencies;
    }

    /**
     * Get exchange rates from API or cache.
     *
     * @return array Exchange rates with USD as base.
     */
    public static function get_exchange_rates() {
        // Check cache first.
        $cached_rates = get_transient( self::EXCHANGE_RATE_TRANSIENT );
        
        if ( false !== $cached_rates ) {
            return $cached_rates;
        }

        // Fetch from API.
        $rates = self::fetch_exchange_rates();
        
        if ( ! empty( $rates ) ) {
            set_transient( self::EXCHANGE_RATE_TRANSIENT, $rates, self::EXCHANGE_RATE_CACHE_DURATION );
            return $rates;
        }

        // Fallback rates if API fails.
        return self::get_fallback_exchange_rates();
    }

    /**
     * Fetch exchange rates from API.
     *
     * @return array|false Exchange rates or false on failure.
     */
    private static function fetch_exchange_rates() {
        // Using exchangerate-api.com (free tier)
        $api_url = 'https://api.exchangerate-api.com/v4/latest/USD';
        
        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || ! isset( $data['rates'] ) ) {
            return false;
        }

        return array(
            'USD' => 1,
            'EUR' => isset( $data['rates']['EUR'] ) ? (float) $data['rates']['EUR'] : 0.92,
            'GBP' => isset( $data['rates']['GBP'] ) ? (float) $data['rates']['GBP'] : 0.79,
        );
    }

    /**
     * Get fallback exchange rates.
     *
     * @return array Fallback rates.
     */
    private static function get_fallback_exchange_rates() {
        return array(
            'USD' => 1,
            'EUR' => 0.92,
            'GBP' => 0.79,
        );
    }

    /**
     * Convert amount from USD to target currency.
     *
     * @param float  $amount   Amount in USD.
     * @param string $currency Target currency.
     * @return float Converted amount.
     */
    public static function convert_from_usd( $amount, $currency ) {
        if ( 'USD' === $currency ) {
            return $amount;
        }

        $rates = self::get_exchange_rates();
        $rate = isset( $rates[ $currency ] ) ? $rates[ $currency ] : 1;
        
        return $amount * $rate;
    }

    /**
     * Convert amount to USD from source currency.
     *
     * @param float  $amount   Amount in source currency.
     * @param string $currency Source currency.
     * @return float Amount in USD.
     */
    public static function convert_to_usd( $amount, $currency ) {
        if ( 'USD' === $currency ) {
            return $amount;
        }

        $rates = self::get_exchange_rates();
        $rate = isset( $rates[ $currency ] ) ? $rates[ $currency ] : 1;
        
        return $amount / $rate;
    }

    /**
     * Get crypto prices from API or cache.
     *
     * @return array Crypto prices in USD.
     */
    public static function get_crypto_prices() {
        // Check cache first.
        $cached_prices = get_transient( self::CRYPTO_PRICE_TRANSIENT );
        
        if ( false !== $cached_prices ) {
            return $cached_prices;
        }

        // Fetch from API.
        $prices = self::fetch_crypto_prices();
        
        if ( ! empty( $prices ) ) {
            set_transient( self::CRYPTO_PRICE_TRANSIENT, $prices, self::CRYPTO_PRICE_CACHE_DURATION );
            return $prices;
        }

        // Fallback prices if API fails.
        return self::get_fallback_crypto_prices();
    }

    /**
     * Fetch crypto prices from CoinGecko API.
     *
     * @return array|false Crypto prices or false on failure.
     */
    private static function fetch_crypto_prices() {
        // CoinGecko free API
        $api_url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether&vs_currencies=usd';
        
        $response = wp_remote_get( $api_url, array(
            'timeout' => 10,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) ) {
            return false;
        }

        return array(
            'BTC'  => isset( $data['bitcoin']['usd'] ) ? (float) $data['bitcoin']['usd'] : 65000,
            'ETH'  => isset( $data['ethereum']['usd'] ) ? (float) $data['ethereum']['usd'] : 3500,
            'USDT' => isset( $data['tether']['usd'] ) ? (float) $data['tether']['usd'] : 1,
        );
    }

    /**
     * Get fallback crypto prices.
     *
     * @return array Fallback prices.
     */
    private static function get_fallback_crypto_prices() {
        return array(
            'BTC'  => 65000,
            'ETH'  => 3500,
            'USDT' => 1,
        );
    }

    /**
     * Convert USD amount to crypto.
     *
     * @param float  $usd_amount Amount in USD.
     * @param string $crypto     Crypto symbol (BTC, ETH, USDT).
     * @return float Crypto amount.
     */
    public static function convert_usd_to_crypto( $usd_amount, $crypto ) {
        $prices = self::get_crypto_prices();
        $price = isset( $prices[ $crypto ] ) ? $prices[ $crypto ] : 1;
        
        if ( $price <= 0 ) {
            return 0;
        }
        
        return $usd_amount / $price;
    }

    /**
     * Get user's balance converted to crypto values.
     *
     * @param int    $user_id  User ID.
     * @param string $currency User's display currency.
     * @return array Crypto conversions.
     */
    public static function get_balance_crypto_conversions( $user_id, $currency = null ) {
        $balance_usd = self::get_user_balance( $user_id );
        
        if ( null === $currency ) {
            $currency = self::get_user_currency_preference( $user_id );
        }
        
        // Convert balance to user's currency first (for display)
        $balance_display = self::convert_from_usd( $balance_usd, $currency );
        
        // Get crypto conversions (always from USD balance)
        $crypto_prices = self::get_crypto_prices();
        
        return array(
            'BTC'  => $balance_usd > 0 ? self::convert_usd_to_crypto( $balance_usd, 'BTC' ) : 0,
            'ETH'  => $balance_usd > 0 ? self::convert_usd_to_crypto( $balance_usd, 'ETH' ) : 0,
            'USDT' => $balance_usd > 0 ? self::convert_usd_to_crypto( $balance_usd, 'USDT' ) : 0,
        );
    }

    /**
     * Get user's recent transactions.
     * Only shows deposit, withdrawal, and transfer transactions.
     * Excludes: profile_update, account_created, admin_permission_change, admin_adjustment.
     *
     * @param int $user_id User ID.
     * @param int $limit   Number of transactions to retrieve.
     * @return array Transactions.
     */
    public static function get_user_transactions( $user_id, $limit = 10 ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Only show deposit, withdrawal, transfer transactions
        // Exclude: profile_update, account_created, admin_permission_change, admin_adjustment
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE user_id = %d 
                 AND type IN ('deposit', 'withdrawal', 'transfer', 'deposit_success', 'withdraw_success', 'transfer_success', 'deposit_request', 'withdrawal_request', 'transfer_sent', 'transfer_received')
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        return $transactions ? $transactions : array();
    }

    /**
     * Get user's transactions with pagination.
     * Excludes non-financial transactions.
     *
     * @param int $user_id  User ID.
     * @param int $page     Page number (1-based).
     * @param int $per_page Number of items per page.
     * @return array Paginated result with transactions, total, total_pages, current_page.
     */
    public static function get_user_transactions_paginated( $user_id, $page = 1, $per_page = 10 ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Calculate offset
        $page = max( 1, $page );
        $offset = ( $page - 1 ) * $per_page;

        // Get total count of financial transactions (deposit, withdrawal, transfer only)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} 
                 WHERE user_id = %d 
                 AND type IN ('deposit', 'withdrawal', 'transfer', 'deposit_success', 'withdraw_success', 'transfer_success', 'deposit_request', 'withdrawal_request', 'transfer_sent', 'transfer_received')",
                $user_id
            )
        );

        $total = (int) $total;
        $total_pages = ceil( $total / $per_page );

        // Get paginated transactions
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE user_id = %d 
                 AND type IN ('deposit', 'withdrawal', 'transfer', 'deposit_success', 'withdraw_success', 'transfer_success', 'deposit_request', 'withdrawal_request', 'transfer_sent', 'transfer_received')
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        return array(
            'transactions' => $transactions ? $transactions : array(),
            'total'        => $total,
            'total_pages'  => max( 1, $total_pages ),
            'current_page' => $page,
            'per_page'     => $per_page,
        );
    }

    /**
     * Get active notifications for user.
     *
     * @param int $user_id User ID.
     * @return array Notifications.
     */
    public static function get_user_notifications( $user_id ) {
        global $wpdb;

        $notifications = array();

        // Get admin notifications.
        $admin_notifications = Ezzy_Wallet_Notifications::get_active_notifications();
        
        foreach ( $admin_notifications as $notification ) {
            $notifications[] = array(
                'id'         => $notification['id'],
                'title'      => $notification['title'],
                'message'    => $notification['message'],
                'type'       => $notification['type'],
                'source'     => 'admin',
                'created_at' => $notification['created_at'],
            );
        }

        // Get user's system notifications from transactions.
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $system_notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, type, description, status, created_at 
                 FROM {$transactions_table} 
                 WHERE user_id = %d 
                 AND type IN ('profile_update', 'account_created', 'admin_adjustment', 'admin_permission_change')
                 ORDER BY created_at DESC 
                 LIMIT 20",
                $user_id
            ),
            ARRAY_A
        );

        foreach ( $system_notifications as $notification ) {
            $notifications[] = array(
                'id'         => 'tx_' . $notification['id'],
                'title'      => self::get_notification_title( $notification['type'] ),
                'message'    => $notification['description'],
                'type'       => 'system',
                'source'     => 'system',
                'created_at' => $notification['created_at'],
            );
        }

        // Sort by date descending.
        usort( $notifications, function( $a, $b ) {
            return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
        } );

        return $notifications;
    }

    /**
     * Get notification title based on type.
     *
     * @param string $type Notification type.
     * @return string Title.
     */
    private static function get_notification_title( $type ) {
        $titles = array(
            'profile_update'          => __( 'Profile Updated', 'ezzy-wallet' ),
            'account_created'         => __( 'Welcome to eZZY Wallet', 'ezzy-wallet' ),
            'admin_adjustment'        => __( 'Balance Adjustment', 'ezzy-wallet' ),
            'admin_permission_change' => __( 'Account Permission Change', 'ezzy-wallet' ),
            'request_approved'        => __( 'Request Approved', 'ezzy-wallet' ),
            'request_rejected'        => __( 'Request Rejected', 'ezzy-wallet' ),
            'transfer_notification'   => __( 'Transfer Received', 'ezzy-wallet' ),
        );

        return isset( $titles[ $type ] ) ? $titles[ $type ] : __( 'Notification', 'ezzy-wallet' );
    }

    /**
     * Get user profile data.
     *
     * @param int $user_id User ID.
     * @return array Profile data.
     */
    public static function get_user_profile( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            return array();
        }

        return array(
            'user_id'      => $user_id,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'phone'        => get_user_meta( $user_id, 'ezzy_wallet_phone', true ),
            'country'      => get_user_meta( $user_id, 'ezzy_wallet_country', true ),
            'address'      => get_user_meta( $user_id, 'ezzy_wallet_address', true ),
            'avatar_url'   => get_avatar_url( $user_id, array( 'size' => 200 ) ),
            'registered'   => $user->user_registered,
        );
    }

    /**
     * Update user profile.
     *
     * @param int   $user_id User ID.
     * @param array $data    Profile data.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function update_user_profile( $user_id, $data ) {
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            return new WP_Error( 'invalid_user', __( 'User not found.', 'ezzy-wallet' ) );
        }

        $updated_fields = array();
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $send_notifications = ! empty( $settings['enable_email_notifications'] );

        // Update email if changed.
        if ( isset( $data['email'] ) && $data['email'] !== $user->user_email ) {
            if ( ! is_email( $data['email'] ) ) {
                return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'ezzy-wallet' ) );
            }
            
            if ( email_exists( $data['email'] ) && email_exists( $data['email'] ) !== $user_id ) {
                return new WP_Error( 'email_exists', __( 'Email address already in use.', 'ezzy-wallet' ) );
            }
            
            wp_update_user( array(
                'ID'         => $user_id,
                'user_email' => sanitize_email( $data['email'] ),
            ) );
            $updated_fields[] = 'email';
        }

        // Update phone.
        if ( isset( $data['phone'] ) ) {
            $old_phone = get_user_meta( $user_id, 'ezzy_wallet_phone', true );
            if ( $data['phone'] !== $old_phone ) {
                update_user_meta( $user_id, 'ezzy_wallet_phone', sanitize_text_field( $data['phone'] ) );
                $updated_fields[] = 'phone';
            }
        }

        // Update country.
        if ( isset( $data['country'] ) ) {
            $old_country = get_user_meta( $user_id, 'ezzy_wallet_country', true );
            if ( $data['country'] !== $old_country ) {
                update_user_meta( $user_id, 'ezzy_wallet_country', sanitize_text_field( $data['country'] ) );
                $updated_fields[] = 'country';
            }
        }

        // Update address.
        if ( isset( $data['address'] ) ) {
            $old_address = get_user_meta( $user_id, 'ezzy_wallet_address', true );
            if ( $data['address'] !== $old_address ) {
                update_user_meta( $user_id, 'ezzy_wallet_address', sanitize_textarea_field( $data['address'] ) );
                $updated_fields[] = 'address';
            }
        }

        // Update display name.
        if ( isset( $data['display_name'] ) ) {
            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => sanitize_text_field( $data['display_name'] ),
            ) );
        }

        // Log profile update if any fields changed.
        if ( ! empty( $updated_fields ) ) {
            self::log_profile_update( $user_id, $updated_fields );
            
            // Send notification if enabled.
            if ( $send_notifications ) {
                self::send_profile_update_email( $user_id, $updated_fields );
            }
        }

        return true;
    }

    /**
     * Log profile update transaction.
     *
     * @param int   $user_id        User ID.
     * @param array $updated_fields Updated field names.
     * @return void
     */
    private static function log_profile_update( $user_id, $updated_fields ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();
        
        $fields_text = implode( ', ', $updated_fields );
        $description = sprintf(
            __( 'User updated profile information: %s', 'ezzy-wallet' ),
            $fields_text
        );

        $wpdb->insert(
            $table_name,
            array(
                'user_id'     => $user_id,
                'type'        => 'profile_update',
                'amount'      => '0.00000000',
                'description' => $description,
                'status'      => 'system',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Send profile update email notification.
     *
     * @param int   $user_id        User ID.
     * @param array $updated_fields Updated field names.
     * @return void
     */
    private static function send_profile_update_email( $user_id, $updated_fields ) {
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            return;
        }

        $fields_text = implode( ', ', $updated_fields );
        $subject = __( 'Your eZZY Wallet Profile Was Updated', 'ezzy-wallet' );
        $message = sprintf(
            __( "Hello %s,\n\nYour wallet profile information has been updated.\n\nUpdated fields: %s\n\nIf you did not make these changes, please contact support immediately.\n\nThank you,\neZZY Wallet Team", 'ezzy-wallet' ),
            $user->display_name,
            $fields_text
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Format balance for display.
     *
     * @param float  $amount   Amount.
     * @param string $currency Currency code.
     * @return string Formatted balance.
     */
    public static function format_balance( $amount, $currency = 'USD' ) {
        $symbol = self::get_currency_symbol( $currency );
        return $symbol . number_format( $amount, 2, '.', ',' );
    }

    /**
     * Format crypto amount for display.
     *
     * @param float  $amount Amount.
     * @param string $crypto Crypto symbol.
     * @return string Formatted amount.
     */
    public static function format_crypto_amount( $amount, $crypto ) {
        switch ( $crypto ) {
            case 'BTC':
                return number_format( $amount, 6, '.', ',' );
            case 'ETH':
                return number_format( $amount, 4, '.', ',' );
            case 'USDT':
                return number_format( $amount, 2, '.', ',' );
            default:
                return number_format( $amount, 4, '.', ',' );
        }
    }

    /**
     * Format transaction type for display.
     *
     * @param string $type Transaction type.
     * @return string Formatted type.
     */
    public static function format_transaction_type( $type ) {
        $types = array(
            'deposit_request'         => __( 'Deposit Request', 'ezzy-wallet' ),
            'deposit_success'         => __( 'Deposit successful! Balance has been updated.', 'ezzy-wallet' ),
            'withdraw_request'        => __( 'Withdraw Request', 'ezzy-wallet' ),
            'withdraw_success'        => __( 'Withdraw successful! Balance has been updated.', 'ezzy-wallet' ),
            'transfer_request'        => __( 'Transfer Request', 'ezzy-wallet' ),
            'transfer_success'        => __( 'Transfer successful! Balance has been updated.', 'ezzy-wallet' ),
            'admin_adjustment'        => __( 'Balance has been updated', 'ezzy-wallet' ),
            'admin_permission_change' => __( 'Permission Change', 'ezzy-wallet' ),
            'profile_update'          => __( 'Profile Update', 'ezzy-wallet' ),
            'account_created'         => __( 'Account Created', 'ezzy-wallet' ),
        );

        return isset( $types[ $type ] ) ? $types[ $type ] : ucwords( str_replace( '_', ' ', $type ) );
    }

    /**
     * Get transaction amount display class.
     *
     * @param string $type   Transaction type.
     * @param float  $amount Amount.
     * @return string CSS class.
     */
    public static function get_transaction_amount_class( $type, $amount ) {
        $positive_types = array( 'deposit_request', 'deposit_success', 'admin_adjustment' );
        
        if ( in_array( $type, $positive_types, true ) && $amount > 0 ) {
            return 'is-positive';
        }
        
        return '';
    }

    /**
     * Format transaction amount for display.
     *
     * @param string $type     Transaction type.
     * @param float  $amount   Amount.
     * @param string $currency Currency.
     * @return string Formatted amount.
     */
    public static function format_transaction_amount( $type, $amount, $currency = 'USD' ) {
        $symbol = self::get_currency_symbol( $currency );
        $formatted = number_format( abs( $amount ), 2, '.', ',' );
        
        $positive_types = array( 'deposit_request', 'deposit_success' );
        $negative_types = array( 'withdraw_request', 'withdraw_success', 'transfer_request', 'transfer_success' );
        $neutral_types = array( 'profile_update', 'account_created', 'admin_permission_change' );
        
        if ( in_array( $type, $neutral_types, true ) ) {
            return '-';
        }
        
        if ( in_array( $type, $positive_types, true ) || $amount > 0 ) {
            return '+' . $symbol . $formatted;
        }
        
        if ( in_array( $type, $negative_types, true ) ) {
            return '-' . $symbol . $formatted;
        }
        
        return '-' . $symbol . $formatted;
    }

    /**
     * Get admin notifications for a user with read status.
     *
     * @param int $user_id User ID.
     * @return array Notifications with read status.
     */
    public static function get_admin_notifications_for_user( $user_id ) {
        global $wpdb;

        $notifications_table = Ezzy_Wallet_DB::get_notifications_table_name();
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT n.*, 
                        CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read
                 FROM {$notifications_table} n
                 LEFT JOIN {$reads_table} r ON n.id = r.notification_id AND r.user_id = %d
                 WHERE n.is_active = 1
                 ORDER BY n.created_at DESC",
                $user_id
            ),
            ARRAY_A
        );

        return $notifications ? $notifications : array();
    }

    /**
     * Get all notifications for user (admin + system) with read status.
     * Only includes profile updates and account created - NOT admin permission changes.
     *
     * @param int $user_id User ID.
     * @return array All notifications.
     */
    public static function get_all_notifications_for_user( $user_id ) {
        global $wpdb;

        $notifications = array();

        // 1. Get admin notifications with read status
        $notifications_table = Ezzy_Wallet_DB::get_notifications_table_name();
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $admin_notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT n.id, n.title, n.message, n.type, n.created_at,
                        CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                        'admin' as source
                 FROM {$notifications_table} n
                 LEFT JOIN {$reads_table} r ON n.id = r.notification_id AND r.user_id = %d
                 WHERE n.is_active = 1
                 ORDER BY n.created_at DESC",
                $user_id
            ),
            ARRAY_A
        );

        foreach ( $admin_notifications as $notification ) {
            $notifications[] = array(
                'id'         => $notification['id'],
                'title'      => $notification['title'],
                'message'    => $notification['message'],
                'type'       => $notification['type'],
                'source'     => 'admin',
                'is_read'    => (bool) $notification['is_read'],
                'created_at' => $notification['created_at'],
            );
        }

        // 2. Get system notifications from transactions
        // Include: profile_update, account_created, request_approved, request_rejected, transfer_notification
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $system_notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.type, t.description, t.status, t.created_at,
                        CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read
                 FROM {$transactions_table} t
                 LEFT JOIN {$reads_table} r ON CONCAT('tx_', t.id) = r.notification_id AND r.user_id = %d
                 WHERE t.user_id = %d 
                 AND t.type IN ('profile_update', 'account_created', 'request_approved', 'request_rejected', 'transfer_notification')
                 ORDER BY t.created_at DESC 
                 LIMIT 30",
                $user_id,
                $user_id
            ),
            ARRAY_A
        );

        foreach ( $system_notifications as $notification ) {
            $notifications[] = array(
                'id'         => 'tx_' . $notification['id'],
                'title'      => self::get_notification_title( $notification['type'] ),
                'message'    => $notification['description'],
                'type'       => 'system',
                'source'     => 'system',
                'is_read'    => (bool) $notification['is_read'],
                'created_at' => $notification['created_at'],
            );
        }

        // Sort by date descending
        usort( $notifications, function( $a, $b ) {
            return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
        } );

        return $notifications;
    }

    /**
     * Get unread notification count for a user.
     * Counts both admin notifications AND system notifications (profile updates).
     *
     * @param int $user_id User ID.
     * @return int Unread count.
     */
    public static function get_unread_notification_count( $user_id ) {
        global $wpdb;

        $notifications_table = Ezzy_Wallet_DB::get_notifications_table_name();
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();

        // Count unread admin notifications
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $admin_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$notifications_table} n
                 LEFT JOIN {$reads_table} r ON CAST(n.id AS CHAR) = r.notification_id AND r.user_id = %d
                 WHERE n.is_active = 1 AND r.id IS NULL",
                $user_id
            )
        );

        // Count unread system notifications (all types shown in Messages panel)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $system_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$transactions_table} t
                 LEFT JOIN {$reads_table} r ON CONCAT('tx_', t.id) = r.notification_id AND r.user_id = %d
                 WHERE t.user_id = %d 
                 AND t.type IN ('profile_update', 'account_created', 'request_approved', 'request_rejected', 'transfer_notification')
                 AND r.id IS NULL",
                $user_id,
                $user_id
            )
        );

        return (int) $admin_count + (int) $system_count;
    }

    /**
     * Mark a notification as read for a user.
     * Handles both admin notifications (numeric ID) and system notifications (tx_ID format).
     *
     * @param int    $user_id         User ID.
     * @param string $notification_id Notification ID (can be numeric or "tx_123" format).
     * @return bool True on success.
     */
    public static function mark_notification_read( $user_id, $notification_id ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_notification_reads_table_name();

        // Convert to string for consistency
        $notification_id = (string) $notification_id;

        // Check if already read.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE user_id = %d AND notification_id = %s",
                $user_id,
                $notification_id
            )
        );

        if ( $exists ) {
            return true;
        }

        // Insert read record.
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'user_id'         => $user_id,
                'notification_id' => $notification_id,
                'read_at'         => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s' )
        );

        return false !== $inserted;
    }

    /**
     * Mark all notifications as read for a user.
     * Includes both admin notifications and system notifications.
     *
     * @param int $user_id User ID.
     * @return int Number of notifications marked as read.
     */
    public static function mark_all_notifications_read( $user_id ) {
        global $wpdb;

        $notifications_table = Ezzy_Wallet_DB::get_notifications_table_name();
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();

        $count = 0;

        // Get all unread admin notification IDs.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $unread_admin_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT CAST(n.id AS CHAR) 
                 FROM {$notifications_table} n
                 LEFT JOIN {$reads_table} r ON CAST(n.id AS CHAR) = r.notification_id AND r.user_id = %d
                 WHERE n.is_active = 1 AND r.id IS NULL",
                $user_id
            )
        );

        foreach ( $unread_admin_ids as $notification_id ) {
            if ( self::mark_notification_read( $user_id, $notification_id ) ) {
                $count++;
            }
        }

        // Get all unread system notification IDs (profile_update, account_created).
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $unread_system_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT CONCAT('tx_', t.id) 
                 FROM {$transactions_table} t
                 LEFT JOIN {$reads_table} r ON CONCAT('tx_', t.id) = r.notification_id AND r.user_id = %d
                 WHERE t.user_id = %d 
                 AND t.type IN ('profile_update', 'account_created')
                 AND r.id IS NULL",
                $user_id,
                $user_id
            )
        );

        foreach ( $unread_system_ids as $notification_id ) {
            if ( self::mark_notification_read( $user_id, $notification_id ) ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get notifications with pagination.
     *
     * @param int $user_id  User ID.
     * @param int $page     Page number (1-based).
     * @param int $per_page Number of items per page.
     * @return array Paginated result.
     */
    public static function get_notifications_paginated( $user_id, $page = 1, $per_page = 10 ) {
        // Get all notifications first
        $all_notifications = self::get_all_notifications_for_user( $user_id );
        
        $total = count( $all_notifications );
        $total_pages = ceil( $total / $per_page );
        $page = max( 1, min( $page, $total_pages ) );
        $offset = ( $page - 1 ) * $per_page;
        
        // Slice the array for pagination
        $notifications = array_slice( $all_notifications, $offset, $per_page );
        
        return array(
            'notifications' => $notifications,
            'total'         => $total,
            'total_pages'   => max( 1, $total_pages ),
            'current_page'  => $page,
            'per_page'      => $per_page,
        );
    }

    /**
     * Delete all notifications for a user.
     * Deletes both system notifications (from transactions) and marks admin notifications as read.
     *
     * @param int $user_id User ID.
     * @return int Number of items deleted.
     */
    public static function delete_all_user_notifications( $user_id ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();
        $count = 0;

        // Delete system notifications from transactions (all types shown in Messages panel)
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$transactions_table} 
                 WHERE user_id = %d 
                 AND type IN ('profile_update', 'account_created', 'request_approved', 'request_rejected', 'transfer_notification')",
                $user_id
            )
        );
        
        if ( $deleted !== false ) {
            $count += $deleted;
        }

        // Delete all read records for this user
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$reads_table} WHERE user_id = %d",
                $user_id
            )
        );

        // Mark all admin notifications as read (we don't delete admin notifications, just mark as read)
        self::mark_all_notifications_read( $user_id );

        return $count;
    }

    /**
     * Delete all transactions for a user.
     * Only deletes financial transactions (deposit, withdrawal, transfer).
     *
     * @param int $user_id User ID.
     * @return int Number of transactions deleted.
     */
    public static function delete_all_user_transactions( $user_id ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();

        // Delete only financial transactions
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$transactions_table} 
                 WHERE user_id = %d 
                 AND type IN ('deposit', 'withdrawal', 'transfer', 'deposit_success', 'withdraw_success', 'transfer_success', 'deposit_request', 'withdrawal_request', 'transfer_sent', 'transfer_received')",
                $user_id
            )
        );

        return $deleted !== false ? $deleted : 0;
    }

    /* ==========================================================================
       WALLET ACTIONS - DEPOSIT, WITHDRAW, TRANSFER
       ========================================================================== */

    /**
     * Create a deposit request.
     *
     * @param int    $user_id  User ID.
     * @param float  $amount   Deposit amount.
     * @param string $method   Deposit method (bitcoin, paypal, cashapp).
     * @param string $note     Optional reference note.
     * @return array Result with success status and message.
     */
    public static function create_deposit_request( $user_id, $amount, $method, $note = '' ) {
        global $wpdb;

        // Validate amount
        $amount = floatval( $amount );
        if ( $amount <= 0 ) {
            return array(
                'success' => false,
                'message' => __( 'Amount must be greater than 0.', 'ezzy-wallet' ),
            );
        }

        // Validate method
        $valid_methods = array( 'bitcoin', 'paypal', 'cashapp' );
        if ( ! in_array( $method, $valid_methods, true ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid deposit method.', 'ezzy-wallet' ),
            );
        }

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Create metadata
        $metadata = array(
            'method' => $method,
            'note'   => sanitize_textarea_field( $note ),
        );

        // Method labels for description
        $method_labels = array(
            'bitcoin' => 'Bitcoin',
            'paypal'  => 'PayPal',
            'cashapp' => 'CashApp',
        );

        $description = sprintf(
            __( 'Deposit request via %s. Amount: $%s. Awaiting admin approval.', 'ezzy-wallet' ),
            $method_labels[ $method ],
            number_format( $amount, 2 )
        );

        // Insert transaction
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'user_id'     => $user_id,
                'type'        => 'deposit_request',
                'amount'      => number_format( $amount, 8, '.', '' ),
                'description' => $description,
                'status'      => 'pending',
                'metadata'    => wp_json_encode( $metadata ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to create deposit request. Please try again.', 'ezzy-wallet' ),
            );
        }

        $transaction_id = $wpdb->insert_id;

        // Send notification to admin if enabled
        self::maybe_send_admin_notification(
            'deposit_request',
            $user_id,
            $amount,
            $method
        );

        return array(
            'success'        => true,
            'message'        => __( 'Deposit request submitted successfully! Your request is pending admin approval.', 'ezzy-wallet' ),
            'transaction_id' => $transaction_id,
        );
    }

    /**
     * Create a withdrawal request.
     *
     * @param int    $user_id     User ID.
     * @param float  $amount      Withdrawal amount.
     * @param string $method      Withdrawal method.
     * @param string $destination Destination address/email/tag.
     * @param string $note        Optional note.
     * @return array Result with success status and message.
     */
    public static function create_withdrawal_request( $user_id, $amount, $method, $destination, $note = '' ) {
        global $wpdb;

        // Validate amount
        $amount = floatval( $amount );
        if ( $amount <= 0 ) {
            return array(
                'success' => false,
                'message' => __( 'Amount must be greater than 0.', 'ezzy-wallet' ),
            );
        }

        // Check user balance
        $balance = self::get_user_balance( $user_id );
        if ( $amount > $balance ) {
            return array(
                'success' => false,
                'message' => __( 'Insufficient balance for this withdrawal.', 'ezzy-wallet' ),
            );
        }

        // Check if withdrawals are disabled for this user
        $wallet = self::get_user_wallet( $user_id );
        $global_disabled = get_option( 'ezzy_wallet_withdrawals_disabled', '0' ) === '1';
        $user_disabled = isset( $wallet['withdrawals_disabled'] ) ? (int) $wallet['withdrawals_disabled'] : 0;
        
        // Three-state logic: 0=Follow Global, 1=Individually Disabled, 2=Individually Enabled
        if ( $user_disabled === 1 || ( $user_disabled === 0 && $global_disabled ) ) {
            return array(
                'success' => false,
                'message' => __( 'Withdrawals are currently disabled for your account.', 'ezzy-wallet' ),
            );
        }

        // Validate method
        $valid_methods = array( 'bitcoin', 'paypal', 'cashapp', 'bank_transfer' );
        if ( ! in_array( $method, $valid_methods, true ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid withdrawal method.', 'ezzy-wallet' ),
            );
        }

        // Validate destination
        if ( empty( trim( $destination ) ) ) {
            return array(
                'success' => false,
                'message' => __( 'Please provide a valid withdrawal destination.', 'ezzy-wallet' ),
            );
        }

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Create metadata
        $metadata = array(
            'method'      => $method,
            'destination' => sanitize_text_field( $destination ),
            'note'        => sanitize_textarea_field( $note ),
        );

        // Method labels
        $method_labels = array(
            'bitcoin'       => 'Bitcoin',
            'paypal'        => 'PayPal',
            'cashapp'       => 'CashApp',
            'bank_transfer' => 'Bank Transfer',
        );

        $description = sprintf(
            __( 'Withdrawal request via %s. Amount: $%s. Awaiting admin approval.', 'ezzy-wallet' ),
            $method_labels[ $method ],
            number_format( $amount, 2 )
        );

        // Insert transaction
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'user_id'     => $user_id,
                'type'        => 'withdrawal_request',
                'amount'      => number_format( $amount, 8, '.', '' ),
                'description' => $description,
                'status'      => 'pending',
                'metadata'    => wp_json_encode( $metadata ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to create withdrawal request. Please try again.', 'ezzy-wallet' ),
            );
        }

        $transaction_id = $wpdb->insert_id;

        // Send notification to admin
        self::maybe_send_admin_notification(
            'withdrawal_request',
            $user_id,
            $amount,
            $method
        );

        return array(
            'success'        => true,
            'message'        => __( 'Withdrawal request submitted successfully! Your request is pending admin approval.', 'ezzy-wallet' ),
            'transaction_id' => $transaction_id,
        );
    }

    /**
     * Execute a transfer between users.
     *
     * @param int    $sender_id   Sender user ID.
     * @param string $recipient   Recipient username or email.
     * @param float  $amount      Transfer amount.
     * @param string $message     Optional message.
     * @return array Result with success status and message.
     */
    public static function execute_transfer( $sender_id, $recipient, $amount, $message = '' ) {
        global $wpdb;

        // Validate amount
        $amount = floatval( $amount );
        if ( $amount <= 0 ) {
            return array(
                'success' => false,
                'message' => __( 'Amount must be greater than 0.', 'ezzy-wallet' ),
            );
        }

        // Check sender balance
        $sender_balance = self::get_user_balance( $sender_id );
        if ( $amount > $sender_balance ) {
            return array(
                'success' => false,
                'message' => __( 'Insufficient balance for this transfer.', 'ezzy-wallet' ),
            );
        }

        // Find recipient user
        $recipient = sanitize_text_field( trim( $recipient ) );
        $recipient_user = null;

        // Try by email first
        if ( is_email( $recipient ) ) {
            $recipient_user = get_user_by( 'email', $recipient );
        }
        
        // Try by username
        if ( ! $recipient_user ) {
            $recipient_user = get_user_by( 'login', $recipient );
        }

        if ( ! $recipient_user ) {
            return array(
                'success' => false,
                'message' => __( 'Recipient not found. Please check the username or email.', 'ezzy-wallet' ),
            );
        }

        $recipient_id = $recipient_user->ID;

        // Cannot send to self
        if ( $recipient_id === $sender_id ) {
            return array(
                'success' => false,
                'message' => __( 'You cannot transfer funds to yourself.', 'ezzy-wallet' ),
            );
        }

        // Check if recipient has a wallet
        $recipient_wallet = self::get_user_wallet( $recipient_id );
        if ( ! $recipient_wallet ) {
            return array(
                'success' => false,
                'message' => __( 'Recipient does not have a wallet account.', 'ezzy-wallet' ),
            );
        }

        // Get sender info
        $sender_user = get_userdata( $sender_id );
        $sender_name = $sender_user ? $sender_user->display_name : 'Unknown';
        $recipient_name = $recipient_user->display_name;

        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();

        // Start transaction
        $wpdb->query( 'START TRANSACTION' );

        try {
            // Deduct from sender
            $sender_updated = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$accounts_table} 
                     SET balance = balance - %f,
                         total_transfer = total_transfer + %f,
                         updated_at = %s
                     WHERE user_id = %d AND balance >= %f",
                    $amount,
                    $amount,
                    current_time( 'mysql' ),
                    $sender_id,
                    $amount
                )
            );

            if ( ! $sender_updated ) {
                throw new Exception( __( 'Failed to deduct from sender balance.', 'ezzy-wallet' ) );
            }

            // Add to recipient
            $recipient_updated = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$accounts_table} 
                     SET balance = balance + %f,
                         updated_at = %s
                     WHERE user_id = %d",
                    $amount,
                    current_time( 'mysql' ),
                    $recipient_id
                )
            );

            if ( ! $recipient_updated ) {
                throw new Exception( __( 'Failed to add to recipient balance.', 'ezzy-wallet' ) );
            }

            // Create sender transaction (Transfer Sent)
            $sender_metadata = array(
                'recipient_id'   => $recipient_id,
                'recipient_name' => $recipient_name,
                'message'        => sanitize_textarea_field( $message ),
            );

            $sender_description = sprintf(
                __( 'Transfer sent to %s. Amount: $%s.', 'ezzy-wallet' ),
                $recipient_name,
                number_format( $amount, 2 )
            );

            $wpdb->insert(
                $transactions_table,
                array(
                    'user_id'     => $sender_id,
                    'type'        => 'transfer_sent',
                    'amount'      => number_format( $amount, 8, '.', '' ),
                    'description' => $sender_description,
                    'status'      => 'completed',
                    'metadata'    => wp_json_encode( $sender_metadata ),
                    'created_at'  => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );

            // Create recipient transaction (Transfer Received)
            $recipient_metadata = array(
                'sender_id'   => $sender_id,
                'sender_name' => $sender_name,
                'message'     => sanitize_textarea_field( $message ),
            );

            $recipient_description = sprintf(
                __( 'Transfer received from %s. Amount: $%s.', 'ezzy-wallet' ),
                $sender_name,
                number_format( $amount, 2 )
            );

            $wpdb->insert(
                $transactions_table,
                array(
                    'user_id'     => $recipient_id,
                    'type'        => 'transfer_received',
                    'amount'      => number_format( $amount, 8, '.', '' ),
                    'description' => $recipient_description,
                    'status'      => 'completed',
                    'metadata'    => wp_json_encode( $recipient_metadata ),
                    'created_at'  => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );

            $wpdb->query( 'COMMIT' );

            // Send notification to recipient
            self::create_transfer_notification( $recipient_id, $sender_name, $amount, $message );

            return array(
                'success'     => true,
                'message'     => sprintf(
                    __( 'Successfully transferred $%s to %s!', 'ezzy-wallet' ),
                    number_format( $amount, 2 ),
                    $recipient_name
                ),
                'new_balance' => self::get_user_balance( $sender_id ),
            );

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }

    /**
     * Maybe send admin notification for deposit/withdrawal requests.
     *
     * @param string $type    Request type.
     * @param int    $user_id User ID.
     * @param float  $amount  Amount.
     * @param string $method  Payment method.
     * @return void
     */
    private static function maybe_send_admin_notification( $type, $user_id, $amount, $method ) {
        $admin_email = get_option( 'ezzy_wallet_admin_email', get_option( 'admin_email' ) );
        
        if ( empty( $admin_email ) ) {
            return;
        }

        $user = get_userdata( $user_id );
        $username = $user ? $user->user_login : 'Unknown';
        $user_email = $user ? $user->user_email : 'Unknown';

        $method_labels = array(
            'bitcoin'       => 'Bitcoin',
            'paypal'        => 'PayPal',
            'cashapp'       => 'CashApp',
            'bank_transfer' => 'Bank Transfer',
        );

        $method_label = isset( $method_labels[ $method ] ) ? $method_labels[ $method ] : $method;

        if ( $type === 'deposit_request' ) {
            $subject = sprintf(
                __( '[%s] New Deposit Request - $%s', 'ezzy-wallet' ),
                get_bloginfo( 'name' ),
                number_format( $amount, 2 )
            );

            $body = sprintf(
                __( "A new deposit request has been submitted.\n\nUser: %s\nEmail: %s\nAmount: $%s\nMethod: %s\n\nPlease review this request in the admin panel.", 'ezzy-wallet' ),
                $username,
                $user_email,
                number_format( $amount, 2 ),
                $method_label
            );
        } else {
            $subject = sprintf(
                __( '[%s] New Withdrawal Request - $%s', 'ezzy-wallet' ),
                get_bloginfo( 'name' ),
                number_format( $amount, 2 )
            );

            $body = sprintf(
                __( "A new withdrawal request has been submitted.\n\nUser: %s\nEmail: %s\nAmount: $%s\nMethod: %s\n\nPlease review this request in the admin panel.", 'ezzy-wallet' ),
                $username,
                $user_email,
                number_format( $amount, 2 ),
                $method_label
            );
        }

        wp_mail( $admin_email, $subject, $body );
    }

    /**
     * Create transfer notification for recipient.
     *
     * @param int    $recipient_id Recipient user ID.
     * @param string $sender_name  Sender display name.
     * @param float  $amount       Transfer amount.
     * @param string $message      Optional message.
     * @return void
     */
    private static function create_transfer_notification( $recipient_id, $sender_name, $amount, $message = '' ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();

        // Create a notification entry
        $notification_text = sprintf(
            __( 'You received $%s from %s.', 'ezzy-wallet' ),
            number_format( $amount, 2 ),
            $sender_name
        );

        if ( ! empty( $message ) ) {
            $notification_text .= ' ' . __( 'Message:', 'ezzy-wallet' ) . ' ' . $message;
        }

        // Insert as a system notification transaction
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id'     => $recipient_id,
                'type'        => 'transfer_notification',
                'amount'      => 0,
                'description' => $notification_text,
                'status'      => 'completed',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get user by username or email.
     *
     * @param string $identifier Username or email.
     * @return WP_User|false User object or false.
     */
    public static function find_user_by_identifier( $identifier ) {
        $identifier = sanitize_text_field( trim( $identifier ) );
        
        // Try email first
        if ( is_email( $identifier ) ) {
            $user = get_user_by( 'email', $identifier );
            if ( $user ) {
                return $user;
            }
        }
        
        // Try username
        return get_user_by( 'login', $identifier );
    }
}
