<?php
/**
 * Uninstall handler for eZZY Wallet.
 *
 * Fired when the plugin is uninstalled/deleted.
 * 
 * IMPORTANT: This file intentionally does NOT delete user data.
 * All wallet accounts, transactions, user data, and settings are preserved
 * so that reinstalling the plugin restores all data.
 * 
 * Only caches and transients are cleared.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up caches only on uninstall.
 * 
 * DATA PRESERVATION POLICY:
 * - Database tables (accounts, transactions, notifications) are KEPT
 * - Plugin settings are KEPT
 * - User meta data is KEPT
 * - License data is KEPT
 * - Only transients and caches are cleared
 * 
 * This ensures that if the admin accidentally deletes the plugin,
 * all user data remains intact and will be restored on reinstall.
 */
function ezzy_wallet_uninstall() {
    global $wpdb;

    // Only clear transients (temporary cached data).
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like( '_transient_ezzy_wallet_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_ezzy_wallet_' ) . '%'
        )
    );

    // Clear WordPress object cache.
    wp_cache_flush();

    /**
     * Fires after eZZY Wallet cache cleanup during uninstall.
     * 
     * NOTE: Database tables and settings are intentionally preserved.
     * If you need to completely remove all data, use the 
     * ezzy_wallet_complete_uninstall() function below manually.
     *
     * @since 1.0.0
     */
    do_action( 'ezzy_wallet_uninstall_cache_cleared' );
}

// Run cache cleanup only.
ezzy_wallet_uninstall();

/**
 * Complete data removal function.
 * 
 * This function is NOT called automatically.
 * It can be called manually if you need to completely remove all plugin data.
 * 
 * Usage: Add this to your theme's functions.php temporarily:
 * 
 *     if ( function_exists( 'ezzy_wallet_complete_uninstall' ) ) {
 *         ezzy_wallet_complete_uninstall();
 *     }
 * 
 * WARNING: This will permanently delete all wallet data!
 */
function ezzy_wallet_complete_uninstall() {
    global $wpdb;

    // Only allow admins to run this.
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    // Drop database tables.
    $tables = array(
        $wpdb->prefix . 'ezzy_wallet_accounts',
        $wpdb->prefix . 'ezzy_wallet_transactions',
        $wpdb->prefix . 'ezzy_wallet_notifications',
    );

    foreach ( $tables as $table ) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }

    // Delete all plugin options.
    $plugin_options = array(
        'ezzy_wallet_version',
        'ezzy_wallet_installed_at',
        'ezzy_wallet_decimal_places',
        'ezzy_wallet_currency_symbol',
        'ezzy_wallet_withdrawals_enabled',
        'ezzy_wallet_db_version',
        'ezzy_wallet_settings',
        'ezzy_wallet_license_data',
        'ezzy_wallet_activation_codes',
        'ezzy_wallet_license_setup_v2',
        'ezzy_wallet_audit_log',
        'ezzy_wallet_security_audit_log',
    );

    foreach ( $plugin_options as $option ) {
        delete_option( $option );
    }

    // Delete all user meta with ezzy_wallet_ prefix.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            $wpdb->esc_like( 'ezzy_wallet_' ) . '%'
        )
    );

    // Delete profile images.
    $upload_dir = wp_upload_dir();
    $profile_dir = $upload_dir['basedir'] . '/ezzy-wallet-profiles';
    if ( is_dir( $profile_dir ) ) {
        $files = glob( $profile_dir . '/*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        rmdir( $profile_dir );
    }

    // Delete all transients.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like( '_transient_ezzy_wallet_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_ezzy_wallet_' ) . '%'
        )
    );

    // Remove capabilities from all roles.
    $capabilities = array(
        'ezzy_wallet_manage',
        'ezzy_wallet_view_all_accounts',
        'ezzy_wallet_edit_accounts',
        'ezzy_wallet_view_transactions',
        'ezzy_wallet_manage_settings',
    );

    $roles = wp_roles()->roles;

    foreach ( $roles as $role_name => $role_info ) {
        $role = get_role( $role_name );
        if ( $role ) {
            foreach ( $capabilities as $cap ) {
                $role->remove_cap( $cap );
            }
        }
    }

    // Clear cache.
    wp_cache_flush();

    return true;
}
