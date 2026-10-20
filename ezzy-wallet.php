<?php
/**
 * Plugin Name: eZZY Wallet
 * Plugin URI: https://bigstunnz.com/ezzy-wallet
 * Description: A professional digital wallet and financial management system for Banks, Fintech platforms, Refund portals, Investment sites, Membership platforms, E-commerce stores, and more. Features include deposits, withdrawals, transfers, multi-currency support, transaction history, and a beautiful responsive dashboard.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Author: Big Stunnz
 * Author URI: https://bigstunnz.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ezzy-wallet
 * Domain Path: /languages
 *
 * @package EzzyWallet
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin version constant.
 */
define( 'EZZY_WALLET_VERSION', '1.0.0' );

/**
 * Plugin directory path constant.
 */
define( 'EZZY_WALLET_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL constant.
 */
define( 'EZZY_WALLET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename constant.
 */
define( 'EZZY_WALLET_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'EZZY_WALLET_MIN_PHP_VERSION', '8.0' );

/**
 * Check PHP version requirements.
 *
 * @return bool True if requirements met, false otherwise.
 */
function ezzy_wallet_check_php_version() {
    return version_compare( PHP_VERSION, EZZY_WALLET_MIN_PHP_VERSION, '>=' );
}

/**
 * Display admin notice for PHP version requirement.
 *
 * @return void
 */
function ezzy_wallet_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: 1: Required PHP version, 2: Current PHP version */
                esc_html__(
                    'eZZY Wallet requires PHP version %1$s or higher. Your current PHP version is %2$s. Please upgrade PHP to use this plugin.',
                    'ezzy-wallet'
                ),
                esc_html( EZZY_WALLET_MIN_PHP_VERSION ),
                esc_html( PHP_VERSION )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Deactivate the plugin if requirements are not met.
 *
 * @return void
 */
function ezzy_wallet_deactivate_self() {
    deactivate_plugins( EZZY_WALLET_PLUGIN_BASENAME );
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['activate'] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        unset( $_GET['activate'] );
    }
}

// Check PHP version before proceeding.
if ( ! ezzy_wallet_check_php_version() ) {
    add_action( 'admin_notices', 'ezzy_wallet_php_version_notice' );
    add_action( 'admin_init', 'ezzy_wallet_deactivate_self' );
    return;
}

// Include required core files.
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-db.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-activator.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-deactivator.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-security.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-maintenance.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-data-service.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-countries.php';
require_once EZZY_WALLET_PLUGIN_DIR . 'includes/class-ezzy-wallet-license.php';

// Initialize license system.
Ezzy_Wallet_License::init();

/**
 * Plugin activation hook.
 *
 * @return void
 */
function ezzy_wallet_activate() {
    Ezzy_Wallet_Activator::activate();
}
register_activation_hook( __FILE__, 'ezzy_wallet_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function ezzy_wallet_deactivate() {
    Ezzy_Wallet_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'ezzy_wallet_deactivate' );

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function ezzy_wallet_load_textdomain() {
    load_plugin_textdomain(
        'ezzy-wallet',
        false,
        dirname( EZZY_WALLET_PLUGIN_BASENAME ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'ezzy_wallet_load_textdomain' );

/**
 * Initialize admin functionality.
 *
 * Only loads admin class when in admin context and licensed.
 *
 * @return void
 */
function ezzy_wallet_admin_init() {
    if ( is_admin() ) {
        // Check if licensed - if not, only show activation page.
        if ( ! Ezzy_Wallet_License::is_licensed() ) {
            // Add activation menu.
            add_action( 'admin_menu', 'ezzy_wallet_license_admin_menu' );
            // Redirect all ezzy-wallet admin pages to activation.
            add_action( 'admin_init', 'ezzy_wallet_license_redirect' );
            return;
        }

        // Licensed - load admin class.
        require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-admin.php';
        new Ezzy_Wallet_Admin();

        // If master license, add license management submenu (after admin menu is created).
        if ( Ezzy_Wallet_License::is_master() ) {
            add_action( 'admin_menu', 'ezzy_wallet_add_license_submenu', 99 );
        }
    }
}
add_action( 'plugins_loaded', 'ezzy_wallet_admin_init', 15 );

/**
 * Add license admin menu (for unlicensed state).
 *
 * @return void
 */
function ezzy_wallet_license_admin_menu() {
    add_menu_page(
        __( 'eZZY Wallet', 'ezzy-wallet' ),
        __( 'eZZY Wallet', 'ezzy-wallet' ),
        'manage_options',
        'ezzy-wallet',
        'ezzy_wallet_render_activation_page',
        'dashicons-money-alt',
        30
    );
}

/**
 * Add license management submenu (for master license).
 *
 * @return void
 */
function ezzy_wallet_add_license_submenu() {
    add_submenu_page(
        'ezzy-wallet',
        __( 'License Management', 'ezzy-wallet' ),
        __( 'Licenses', 'ezzy-wallet' ),
        'manage_options',
        'ezzy-wallet-licenses',
        'ezzy_wallet_render_license_management_page'
    );
}

/**
 * Redirect to activation page if not licensed.
 *
 * @return void
 */
function ezzy_wallet_license_redirect() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

    // If trying to access any ezzy-wallet page without license, redirect to activation.
    if ( strpos( $page, 'ezzy-wallet' ) === 0 && $page !== 'ezzy-wallet' ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ezzy-wallet' ) );
        exit;
    }
}

/**
 * Render activation page.
 *
 * @return void
 */
function ezzy_wallet_render_activation_page() {
    include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/license-activation.php';
}

/**
 * Render license management page.
 *
 * @return void
 */
function ezzy_wallet_render_license_management_page() {
    include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/license-management.php';
}

/**
 * Initialize frontend functionality.
 *
 * Loads frontend class for public-facing features.
 *
 * @return void
 */
function ezzy_wallet_frontend_init() {
    // Check if licensed - if not, don't load frontend
    if ( ! Ezzy_Wallet_License::is_licensed() ) {
        return;
    }
    
    // Load notifications class for frontend access to admin notifications.
    require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-notifications.php';
    require_once EZZY_WALLET_PLUGIN_DIR . 'public/class-ezzy-wallet-frontend.php';
    
    // Always instantiate for AJAX handlers, but shortcode only renders on frontend
    new Ezzy_Wallet_Frontend();
}
add_action( 'plugins_loaded', 'ezzy_wallet_frontend_init', 15 );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function ezzy_wallet_init() {
    // Check for database upgrade.
    if ( is_admin() ) {
        Ezzy_Wallet_DB::maybe_upgrade();
    }

    /**
     * Fires when eZZY Wallet is fully loaded.
     *
     * @since 1.0.0
     */
    do_action( 'ezzy_wallet_loaded' );
}
add_action( 'plugins_loaded', 'ezzy_wallet_init', 20 );

/**
 * Get plugin settings.
 *
 * Helper function to access plugin settings from anywhere.
 *
 * @param string $key     Optional. Specific setting key to retrieve.
 * @param mixed  $default Optional. Default value if setting not found.
 * @return mixed Settings array or specific setting value.
 */
function ezzy_wallet_get_settings( $key = null, $default = null ) {
    $defaults = array(
        'admin_email'               => get_option( 'admin_email' ),
        'default_currency'          => 'USD',
        'enable_email_notifications' => true,
        'enable_withdrawals'        => true,
        'primary_color'             => '#2563eb',
        'secondary_color'           => '#1e40af',
        'button_color'              => '#3b82f6',
        'card_background_color'     => '#ffffff',
        'form_background_color'     => '#f8fafc',
        'input_border_color'        => '#e2e8f0',
        'hover_color'               => '#1d4ed8',
        'maintenance_mode'          => false,
    );

    $settings = get_option( 'ezzy_wallet_settings', array() );
    $settings = wp_parse_args( $settings, $defaults );

    if ( null !== $key ) {
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    return $settings;
}

/**
 * Check if maintenance mode is active.
 *
 * Helper function to check maintenance status.
 *
 * @return bool True if maintenance mode is enabled.
 */
function ezzy_wallet_is_maintenance_mode() {
    return Ezzy_Wallet_Maintenance::is_enabled();
}

/**
 * Get wallet page URL.
 *
 * Helper function to get the wallet page URL.
 *
 * @return string Wallet page URL.
 */
function ezzy_wallet_get_page_url() {
    // Try to find the wallet page.
    $pages = get_posts(
        array(
            'post_type'      => 'page',
            'posts_per_page' => 1,
            's'              => '[ezzy_wallet]',
        )
    );

    if ( ! empty( $pages ) ) {
        return get_permalink( $pages[0]->ID );
    }

    return home_url( '/wallet/' );
}

/**
 * Check if current user has a wallet account.
 *
 * @param int $user_id User ID to check.
 * @return bool True if user has wallet account.
 */
function ezzy_wallet_user_has_account( $user_id = null ) {
    global $wpdb;

    if ( null === $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( empty( $user_id ) ) {
        return false;
    }

    $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE user_id = %d",
            $user_id
        )
    );

    return ! empty( $exists );
}

/**
 * Get user wallet balance.
 *
 * @param int $user_id User ID.
 * @return string|false Balance as decimal string or false if no account.
 */
function ezzy_wallet_get_balance( $user_id = null ) {
    global $wpdb;

    if ( null === $user_id ) {
        $user_id = get_current_user_id();
    }

    if ( empty( $user_id ) ) {
        return false;
    }

    $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $balance = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT balance FROM {$table_name} WHERE user_id = %d",
            $user_id
        )
    );

    return $balance !== null ? $balance : false;
}
