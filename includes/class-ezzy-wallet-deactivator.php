<?php
/**
 * Plugin deactivator class for eZZY Wallet.
 *
 * Handles all deactivation tasks.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Deactivator
 *
 * Handles plugin deactivation procedures.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Runs cleanup tasks when the plugin is deactivated.
     * Note: Does NOT delete database tables or options.
     * Use uninstall.php for complete removal.
     *
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled events.
        self::clear_scheduled_events();

        // Clear transients.
        self::clear_transients();

        // Remove capabilities (optional - keep for reactivation).
        // self::remove_capabilities();

        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Clear any scheduled cron events.
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled hooks (for future use).
        $scheduled_hooks = array(
            'ezzy_wallet_daily_cleanup',
            'ezzy_wallet_process_pending',
            'ezzy_wallet_send_notifications',
        );

        foreach ( $scheduled_hooks as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }
        }

        // Clear all events for the hook.
        foreach ( $scheduled_hooks as $hook ) {
            wp_clear_scheduled_hook( $hook );
        }
    }

    /**
     * Clear plugin transients.
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all transients with our prefix.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_ezzy_wallet_' ) . '%',
                $wpdb->esc_like( '_transient_timeout_ezzy_wallet_' ) . '%'
            )
        );

        // Also delete from the transient cache.
        delete_transient( 'ezzy_wallet_activated' );
    }

    /**
     * Remove custom capabilities.
     *
     * Commented out by default to preserve settings on reactivation.
     *
     * @return void
     */
    private static function remove_capabilities() {
        $admin_role = get_role( 'administrator' );

        if ( $admin_role ) {
            $admin_role->remove_cap( 'ezzy_wallet_manage' );
            $admin_role->remove_cap( 'ezzy_wallet_view_all_accounts' );
            $admin_role->remove_cap( 'ezzy_wallet_edit_accounts' );
            $admin_role->remove_cap( 'ezzy_wallet_view_transactions' );
            $admin_role->remove_cap( 'ezzy_wallet_manage_settings' );
        }
    }
}
