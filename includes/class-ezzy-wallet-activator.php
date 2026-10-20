<?php
/**
 * Plugin activator class for eZZY Wallet.
 *
 * Handles all activation tasks including database setup.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Activator
 *
 * Handles plugin activation procedures.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Activator {

    /**
     * Activate the plugin.
     *
     * Runs all necessary setup tasks when the plugin is activated.
     *
     * @return void
     */
    public static function activate() {
        // Check minimum requirements.
        if ( ! self::check_requirements() ) {
            self::activation_failed();
            return;
        }

        // Create database tables.
        self::create_database_tables();

        // Set default options.
        self::set_default_options();

        // Add custom capabilities.
        self::add_capabilities();

        // Clear any cached data.
        self::clear_cache();

        // Set activation flag for welcome screen (future use).
        set_transient( 'ezzy_wallet_activated', true, 30 );

        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Check if all requirements are met.
     *
     * @return bool True if requirements met, false otherwise.
     */
    private static function check_requirements() {
        // Check PHP version.
        if ( version_compare( PHP_VERSION, EZZY_WALLET_MIN_PHP_VERSION, '<' ) ) {
            return false;
        }

        // Check WordPress version.
        if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Handle activation failure.
     *
     * @return void
     */
    private static function activation_failed() {
        deactivate_plugins( EZZY_WALLET_PLUGIN_BASENAME );

        wp_die(
            sprintf(
                /* translators: 1: Required PHP version */
                esc_html__(
                    'eZZY Wallet requires PHP %1$s or higher and WordPress 5.8 or higher. Please upgrade your server.',
                    'ezzy-wallet'
                ),
                esc_html( EZZY_WALLET_MIN_PHP_VERSION )
            ),
            esc_html__( 'Plugin Activation Error', 'ezzy-wallet' ),
            array(
                'response'  => 200,
                'back_link' => true,
            )
        );
    }

    /**
     * Create database tables.
     *
     * @return void
     */
    private static function create_database_tables() {
        Ezzy_Wallet_DB::create_tables();
    }

    /**
     * Set default plugin options.
     *
     * @return void
     */
    private static function set_default_options() {
        $default_options = array(
            'ezzy_wallet_version'           => EZZY_WALLET_VERSION,
            'ezzy_wallet_installed_at'      => current_time( 'mysql' ),
            'ezzy_wallet_decimal_places'    => 8,
            'ezzy_wallet_currency_symbol'   => '$',
            'ezzy_wallet_withdrawals_enabled' => true,
        );

        foreach ( $default_options as $option_name => $option_value ) {
            if ( false === get_option( $option_name ) ) {
                add_option( $option_name, $option_value );
            }
        }
    }

    /**
     * Add custom capabilities.
     *
     * Placeholder for future capability management.
     *
     * @return void
     */
    private static function add_capabilities() {
        $admin_role = get_role( 'administrator' );

        if ( $admin_role ) {
            // Wallet management capabilities.
            $admin_role->add_cap( 'ezzy_wallet_manage' );
            $admin_role->add_cap( 'ezzy_wallet_view_all_accounts' );
            $admin_role->add_cap( 'ezzy_wallet_edit_accounts' );
            $admin_role->add_cap( 'ezzy_wallet_view_transactions' );
            $admin_role->add_cap( 'ezzy_wallet_manage_settings' );
        }
    }

    /**
     * Clear any cached data.
     *
     * @return void
     */
    private static function clear_cache() {
        wp_cache_flush();
    }
}
