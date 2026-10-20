<?php
/**
 * Frontend functionality for eZZY Wallet.
 *
 * Handles the public-facing wallet interface.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Frontend
 *
 * Manages the frontend wallet interface.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Frontend {

    /**
     * Maximum login attempts allowed.
     *
     * @var int
     */
    const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds.
     *
     * @var int
     */
    const LOCKOUT_DURATION = 900; // 15 minutes

    /**
     * Form messages.
     *
     * @var array
     */
    private $messages = array();

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'ezzy_wallet', array( $this, 'render_wallet_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'init', array( $this, 'handle_form_submissions' ) );
        add_action( 'init', array( $this, 'start_session' ) );
        
        // AJAX handlers for Phase 6.
        add_action( 'wp_ajax_ezzy_wallet_switch_currency', array( $this, 'ajax_switch_currency' ) );
        add_action( 'wp_ajax_ezzy_wallet_update_profile', array( $this, 'ajax_update_profile' ) );
        add_action( 'wp_ajax_ezzy_wallet_get_dashboard_data', array( $this, 'ajax_get_dashboard_data' ) );
        add_action( 'wp_ajax_ezzy_wallet_get_notifications', array( $this, 'ajax_get_notifications' ) );
        add_action( 'wp_ajax_ezzy_wallet_mark_notifications_read', array( $this, 'ajax_mark_notifications_read' ) );
        add_action( 'wp_ajax_ezzy_wallet_get_all_transactions', array( $this, 'ajax_get_all_transactions' ) );
        add_action( 'wp_ajax_ezzy_wallet_get_notifications_paginated', array( $this, 'ajax_get_notifications_paginated' ) );
        add_action( 'wp_ajax_ezzy_wallet_delete_all_messages', array( $this, 'ajax_delete_all_messages' ) );
        add_action( 'wp_ajax_ezzy_wallet_delete_all_transactions', array( $this, 'ajax_delete_all_transactions' ) );
        
        // Account Settings AJAX handlers
        add_action( 'wp_ajax_ezzy_wallet_setup_2fa', array( $this, 'ajax_setup_2fa' ) );
        add_action( 'wp_ajax_ezzy_wallet_verify_2fa', array( $this, 'ajax_verify_2fa' ) );
        add_action( 'wp_ajax_ezzy_wallet_disable_2fa', array( $this, 'ajax_disable_2fa' ) );
        add_action( 'wp_ajax_ezzy_wallet_change_password', array( $this, 'ajax_change_password' ) );
        add_action( 'wp_ajax_ezzy_wallet_delete_account', array( $this, 'ajax_delete_account' ) );
        
        // Support AJAX handler
        add_action( 'wp_ajax_ezzy_wallet_send_support_email', array( $this, 'ajax_send_support_email' ) );
        
        // Wallet Actions AJAX handlers
        add_action( 'wp_ajax_ezzy_wallet_deposit_request', array( $this, 'ajax_deposit_request' ) );
        add_action( 'wp_ajax_ezzy_wallet_withdraw_request', array( $this, 'ajax_withdraw_request' ) );
        add_action( 'wp_ajax_ezzy_wallet_transfer', array( $this, 'ajax_transfer' ) );
        
        // Profile image upload handler
        add_action( 'wp_ajax_ezzy_wallet_upload_profile_image', array( $this, 'ajax_upload_profile_image' ) );
    }

    /**
     * Start session for rate limiting.
     *
     * @return void
     */
    public function start_session() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Only load on pages with our shortcode.
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'ezzy_wallet' ) ) {
            return;
        }

        // Enqueue frontend styles.
        wp_enqueue_style(
            'ezzy-wallet-frontend',
            EZZY_WALLET_PLUGIN_URL . 'public/css/ezzy-wallet-frontend.css',
            array(),
            EZZY_WALLET_VERSION
        );

        // Enqueue dashboard styles if user is logged in.
        if ( is_user_logged_in() ) {
            wp_enqueue_style(
                'ezzy-wallet-dashboard',
                EZZY_WALLET_PLUGIN_URL . 'public/css/ezzy-wallet-dashboard.css',
                array(),
                EZZY_WALLET_VERSION
            );

            // Add inline styles for theme colors.
            $theme_css = $this->generate_theme_css();
            wp_add_inline_style( 'ezzy-wallet-dashboard', $theme_css );

            // Add CSS isolation styles (loaded last to override everything)
            $isolation_css = $this->generate_isolation_css();
            wp_add_inline_style( 'ezzy-wallet-dashboard', $isolation_css );

            wp_enqueue_script(
                'ezzy-wallet-dashboard',
                EZZY_WALLET_PLUGIN_URL . 'public/js/ezzy-wallet-dashboard.js',
                array( 'jquery' ),
                EZZY_WALLET_VERSION,
                true
            );

            // Localize dashboard script.
            wp_localize_script(
                'ezzy-wallet-dashboard',
                'ezzyWalletFrontend',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'ezzy_wallet_frontend_nonce' ),
                )
            );
        }

        // Enqueue frontend scripts.
        wp_enqueue_script(
            'ezzy-wallet-auth',
            EZZY_WALLET_PLUGIN_URL . 'public/js/ezzy-wallet-auth.js',
            array( 'jquery' ),
            EZZY_WALLET_VERSION,
            true
        );

        // Localize script.
        wp_localize_script(
            'ezzy-wallet-auth',
            'ezzyWalletFrontend',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ezzy_wallet_frontend_nonce' ),
            )
        );
    }

    /**
     * Generate theme CSS variables from settings.
     *
     * @return string CSS variables.
     */
    private function generate_theme_css() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $skin_theme = isset( $settings['dashboard_skin_theme'] ) ? $settings['dashboard_skin_theme'] : 'classic_blue';

        // Get skin theme colors
        $themes = self::get_skin_themes();
        $theme = isset( $themes[ $skin_theme ] ) ? $themes[ $skin_theme ] : $themes['classic_blue'];

        // Convert hex to RGB for rgba usage.
        $primary_rgb = $this->hex_to_rgb( $theme['primary'] );
        $secondary_rgb = $this->hex_to_rgb( $theme['secondary'] );
        $accent_rgb = $this->hex_to_rgb( $theme['accent'] );

        // Check if this is a dark theme
        $is_dark = in_array( $skin_theme, array( 'midnight_dark', 'cyber_neon' ), true );
        
        // Generate text colors based on theme
        $gray = $is_dark ? '#94A3B8' : '#71717a';
        $gray_light = $is_dark ? '#64748B' : '#a1a1aa';
        $line = $is_dark ? '#334155' : '#e4e4e7';
        $light = $is_dark ? '#1E293B' : '#f4f4f5';

        $css = ':root {
            /* Skin Theme: ' . esc_attr( $theme['name'] ) . ' */
            --ezzy-primary: ' . esc_attr( $theme['primary'] ) . ';
            --ezzy-secondary: ' . esc_attr( $theme['secondary'] ) . ';
            --ezzy-primary-rgb: ' . esc_attr( $primary_rgb ) . ';
            --ezzy-secondary-rgb: ' . esc_attr( $secondary_rgb ) . ';
            --ezzy-accent: ' . esc_attr( $theme['accent'] ) . ';
            --ezzy-accent-rgb: ' . esc_attr( $accent_rgb ) . ';
            --ezzy-white: ' . esc_attr( $theme['card_bg'] ) . ';
            --ezzy-dark: ' . esc_attr( $theme['text'] ) . ';
            --ezzy-gray: ' . esc_attr( $gray ) . ';
            --ezzy-gray-light: ' . esc_attr( $gray_light ) . ';
            --ezzy-light: ' . esc_attr( $light ) . ';
            --ezzy-line: ' . esc_attr( $line ) . ';
            --ezzy-bg: ' . esc_attr( $theme['background'] ) . ';
            --ezzy-form-bg: ' . esc_attr( $theme['form_bg'] ) . ';
            --ezzy-input-border: ' . esc_attr( $theme['input_border'] ) . ';
            --ezzy-hover: ' . esc_attr( $theme['hover'] ) . ';
            
            /* Legacy theme variables for compatibility */
            --ezzy-theme-primary: ' . esc_attr( $theme['primary'] ) . ';
            --ezzy-theme-secondary: ' . esc_attr( $theme['secondary'] ) . ';
            --ezzy-theme-button: ' . esc_attr( $theme['primary'] ) . ';
            --ezzy-theme-card-bg: ' . esc_attr( $theme['card_bg'] ) . ';
            --ezzy-theme-form-bg: ' . esc_attr( $theme['form_bg'] ) . ';
            --ezzy-theme-input-border: ' . esc_attr( $theme['input_border'] ) . ';
            --ezzy-theme-hover: ' . esc_attr( $theme['hover'] ) . ';
            --ezzy-theme-primary-rgb: ' . esc_attr( $primary_rgb ) . ';
        }';

        // Add dark theme specific overrides
        if ( $is_dark ) {
            $css .= '
            .ezzy-dashboard {
                background: ' . esc_attr( $theme['background'] ) . ';
            }
            .ezzy-sidebar,
            .ezzy-header,
            .ezzy-card,
            .ezzy-wallet-card,
            .ezzy-form-container {
                background: ' . esc_attr( $theme['card_bg'] ) . ';
                color: ' . esc_attr( $theme['text'] ) . ';
            }
            .ezzy-panel,
            .ezzy-transactions-panel,
            .ezzy-messages-panel,
            .ezzy-profile-panel,
            .ezzy-settings-panel,
            .ezzy-support-panel {
                background: ' . esc_attr( $theme['card_bg'] ) . ';
                color: ' . esc_attr( $theme['text'] ) . ';
            }
            .ezzy-modal-content,
            .ezzy-action-modal,
            .ezzy-support-modal {
                background: ' . esc_attr( $theme['card_bg'] ) . ';
                color: ' . esc_attr( $theme['text'] ) . ';
            }
            .ezzy-form-input,
            .ezzy-form-select,
            .ezzy-form-textarea {
                background: ' . esc_attr( $theme['form_bg'] ) . ';
                border-color: ' . esc_attr( $theme['input_border'] ) . ';
                color: ' . esc_attr( $theme['text'] ) . ';
            }
            .ezzy-nav-item,
            .ezzy-panel-title,
            .ezzy-section-title,
            .ezzy-field-value {
                color: ' . esc_attr( $theme['text'] ) . ';
            }
            .ezzy-footer-nav {
                background: ' . esc_attr( $theme['card_bg'] ) . ';
                border-color: ' . esc_attr( $line ) . ';
            }
            .ezzy-transactions-table th,
            .ezzy-transactions-table td {
                border-color: ' . esc_attr( $line ) . ';
            }';
        }

        return $css;
    }

    /**
     * Generate CSS isolation rules to override theme/plugin styles.
     * Uses maximum specificity to ensure our styles take precedence.
     *
     * @return string CSS isolation rules.
     */
    private function generate_isolation_css() {
        // Get current theme settings
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $skin_key = isset( $settings['dashboard_skin_theme'] ) ? $settings['dashboard_skin_theme'] : 'classic_blue';
        $themes = self::get_skin_themes();
        $theme = isset( $themes[ $skin_key ] ) ? $themes[ $skin_key ] : $themes['classic_blue'];

        $css = '
        /* ==========================================================================
           CSS ISOLATION - Maximum Specificity Override
           Ensures plugin styles override ALL theme/plugin/Elementor styles
           ========================================================================== */

        /* Root level isolation - reset inherited Elementor/theme variables */
        .ezzy-dashboard,
        body .ezzy-dashboard,
        html body .ezzy-dashboard,
        .elementor-widget-container .ezzy-dashboard,
        .elementor .ezzy-dashboard,
        .elementor-element .ezzy-dashboard {
            --e-global-color-primary: ' . esc_attr( $theme['primary'] ) . ' !important;
            --e-global-color-secondary: ' . esc_attr( $theme['secondary'] ) . ' !important;
            --e-global-color-accent: ' . esc_attr( $theme['accent'] ) . ' !important;
            --e-global-typography-primary-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            --e-global-typography-secondary-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            --e-global-typography-text-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        /* Force font family on ALL elements with maximum specificity */
        html body .ezzy-dashboard,
        html body .ezzy-dashboard *,
        html body .ezzy-dashboard *::before,
        html body .ezzy-dashboard *::after,
        body.elementor-page .ezzy-dashboard,
        body.elementor-page .ezzy-dashboard *,
        .elementor .ezzy-dashboard *,
        .elementor-widget-container .ezzy-dashboard * {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            letter-spacing: normal !important;
            text-transform: none !important;
            font-style: normal !important;
        }

        /* Force headings - smaller, modern fintech style */
        html body .ezzy-dashboard h1,
        .elementor .ezzy-dashboard h1,
        body.elementor-page .ezzy-dashboard h1 {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            letter-spacing: -0.01em !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            color: var(--ezzy-dark) !important;
        }

        html body .ezzy-dashboard h2,
        .elementor .ezzy-dashboard h2,
        body.elementor-page .ezzy-dashboard h2 {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            letter-spacing: -0.01em !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            color: var(--ezzy-dark) !important;
        }

        html body .ezzy-dashboard h3,
        .elementor .ezzy-dashboard h3,
        body.elementor-page .ezzy-dashboard h3 {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            letter-spacing: -0.01em !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            color: var(--ezzy-dark) !important;
        }

        html body .ezzy-dashboard h4,
        html body .ezzy-dashboard h5,
        html body .ezzy-dashboard h6,
        .elementor .ezzy-dashboard h4,
        .elementor .ezzy-dashboard h5,
        .elementor .ezzy-dashboard h6 {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            letter-spacing: -0.01em !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            color: var(--ezzy-dark) !important;
        }

        /* Force specific title classes */
        html body .ezzy-dashboard .ezzy-transactions-title,
        html body .ezzy-dashboard .ezzy-panel-title,
        html body .ezzy-dashboard .ezzy-section-title,
        .elementor .ezzy-dashboard .ezzy-transactions-title,
        .elementor .ezzy-dashboard .ezzy-panel-title,
        body.elementor-page .ezzy-dashboard .ezzy-transactions-title,
        body.elementor-page .ezzy-dashboard .ezzy-panel-title {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            letter-spacing: -0.01em !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            color: var(--ezzy-dark) !important;
        }

        /* Force buttons - critical for action buttons */
        html body .ezzy-dashboard button,
        html body .ezzy-dashboard .ezzy-btn,
        html body .ezzy-dashboard .ezzy-action-btn,
        html body .ezzy-dashboard input[type="submit"],
        html body .ezzy-dashboard input[type="button"],
        .elementor .ezzy-dashboard button,
        .elementor .ezzy-dashboard .ezzy-btn,
        body.elementor-page .ezzy-dashboard button,
        body.elementor-page .ezzy-dashboard .ezzy-btn {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            letter-spacing: normal !important;
            text-transform: none !important;
            line-height: 1.5 !important;
        }

        /* Force primary buttons background color */
        html body .ezzy-dashboard .ezzy-btn-primary,
        html body .ezzy-dashboard .ezzy-action-btn.ezzy-action-deposit,
        html body .ezzy-dashboard .ezzy-action-btn.ezzy-action-withdraw,
        html body .ezzy-dashboard .ezzy-action-btn.ezzy-action-transfer,
        .elementor .ezzy-dashboard .ezzy-btn-primary,
        .elementor .ezzy-dashboard .ezzy-action-btn,
        body.elementor-page .ezzy-dashboard .ezzy-btn-primary,
        body.elementor-page .ezzy-dashboard .ezzy-action-btn {
            background: var(--ezzy-primary) !important;
            background-color: var(--ezzy-primary) !important;
            color: #ffffff !important;
            border: none !important;
        }

        html body .ezzy-dashboard .ezzy-btn-primary:hover,
        html body .ezzy-dashboard .ezzy-action-btn:hover,
        .elementor .ezzy-dashboard .ezzy-btn-primary:hover,
        .elementor .ezzy-dashboard .ezzy-action-btn:hover {
            background: var(--ezzy-secondary) !important;
            background-color: var(--ezzy-secondary) !important;
        }

        /* Force secondary/outline buttons */
        html body .ezzy-dashboard .ezzy-btn-secondary,
        html body .ezzy-dashboard .ezzy-btn-outline,
        .elementor .ezzy-dashboard .ezzy-btn-secondary,
        .elementor .ezzy-dashboard .ezzy-btn-outline {
            background: transparent !important;
            background-color: transparent !important;
            color: var(--ezzy-primary) !important;
            border: 1px solid var(--ezzy-primary) !important;
        }

        /* Force the balance toggle button specifically */
        html body .ezzy-dashboard .ezzy-balance-toggle,
        html body .ezzy-dashboard #ezzy-balance-toggle,
        .elementor .ezzy-dashboard .ezzy-balance-toggle {
            background: rgba(var(--ezzy-primary-rgb), 0.1) !important;
            background-color: rgba(var(--ezzy-primary-rgb), 0.1) !important;
            border: none !important;
            color: var(--ezzy-primary) !important;
        }

        /* Force inputs */
        html body .ezzy-dashboard input,
        html body .ezzy-dashboard textarea,
        html body .ezzy-dashboard select,
        .elementor .ezzy-dashboard input,
        .elementor .ezzy-dashboard textarea,
        .elementor .ezzy-dashboard select {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 14px !important;
            font-weight: 400 !important;
            letter-spacing: normal !important;
            color: var(--ezzy-dark) !important;
            background-color: var(--ezzy-form-bg, #f8fafc) !important;
            border: 1px solid var(--ezzy-input-border, #e2e8f0) !important;
        }

        /* Force links */
        html body .ezzy-dashboard a,
        .elementor .ezzy-dashboard a {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            text-decoration: none !important;
        }

        /* Force navigation items */
        html body .ezzy-dashboard .ezzy-nav-list a,
        html body .ezzy-dashboard .ezzy-footer-item,
        .elementor .ezzy-dashboard .ezzy-nav-list a,
        .elementor .ezzy-dashboard .ezzy-footer-item {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-weight: 500 !important;
            font-size: 14px !important;
            letter-spacing: normal !important;
            text-transform: none !important;
        }

        /* Force sidebar background */
        html body .ezzy-dashboard .ezzy-sidebar,
        .elementor .ezzy-dashboard .ezzy-sidebar {
            background: var(--ezzy-white) !important;
            background-color: var(--ezzy-white) !important;
        }

        /* Force header background */
        html body .ezzy-dashboard .ezzy-header,
        .elementor .ezzy-dashboard .ezzy-header {
            background: var(--ezzy-white) !important;
            background-color: var(--ezzy-white) !important;
        }

        /* Force card backgrounds */
        html body .ezzy-dashboard .ezzy-card,
        html body .ezzy-dashboard .ezzy-panel,
        .elementor .ezzy-dashboard .ezzy-card,
        .elementor .ezzy-dashboard .ezzy-panel {
            background: var(--ezzy-white) !important;
            background-color: var(--ezzy-white) !important;
        }

        /* Force table styles */
        html body .ezzy-dashboard table,
        html body .ezzy-dashboard th,
        html body .ezzy-dashboard td,
        .elementor .ezzy-dashboard table,
        .elementor .ezzy-dashboard th,
        .elementor .ezzy-dashboard td {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 14px !important;
            letter-spacing: normal !important;
        }

        /* Force modal backgrounds */
        html body .ezzy-dashboard .ezzy-modal-content,
        html body .ezzy-dashboard .ezzy-action-modal,
        .elementor .ezzy-dashboard .ezzy-modal-content,
        .elementor .ezzy-dashboard .ezzy-action-modal {
            background: var(--ezzy-white) !important;
            background-color: var(--ezzy-white) !important;
        }

        /* Force crypto bar styling */
        html body .ezzy-dashboard .ezzy-crypto-bar,
        .elementor .ezzy-dashboard .ezzy-crypto-bar {
            background: transparent !important;
        }

        html body .ezzy-dashboard .ezzy-crypto-item,
        .elementor .ezzy-dashboard .ezzy-crypto-item {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 13px !important;
        }

        /* Force status badges */
        html body .ezzy-dashboard .ezzy-status-pending,
        html body .ezzy-dashboard .ezzy-status-approved,
        html body .ezzy-dashboard .ezzy-status-completed,
        html body .ezzy-dashboard .ezzy-status-rejected {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }

        /* Fix currency switcher */
        html body .ezzy-dashboard .ezzy-currency-switcher,
        html body .ezzy-dashboard .ezzy-switch-btn,
        .elementor .ezzy-dashboard .ezzy-currency-switcher,
        .elementor .ezzy-dashboard .ezzy-switch-btn {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            background: var(--ezzy-primary) !important;
            background-color: var(--ezzy-primary) !important;
        }

        /* Override any elementor button styles inside our dashboard */
        html body .ezzy-dashboard .elementor-button,
        html body .ezzy-dashboard [class*="elementor-"],
        .elementor .ezzy-dashboard .elementor-button {
            all: unset !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        ';

        return $css;
    }

    /**
     * Get available skin themes.
     *
     * @return array Skin themes with their color definitions.
     */
    public static function get_skin_themes() {
        return array(
            'classic_blue' => array(
                'name'        => __( 'Classic Blue', 'ezzy-wallet' ),
                'primary'     => '#2F6BFF',
                'secondary'   => '#1E4ED8',
                'accent'      => '#F4B400',
                'background'  => '#F9FAFB',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#F8FAFC',
                'input_border'=> '#E2E8F0',
                'text'        => '#111827',
                'hover'       => '#1D4ED8',
            ),
            'blue_ocean' => array(
                'name'        => __( 'Blue Ocean', 'ezzy-wallet' ),
                'primary'     => '#0EA5E9',
                'secondary'   => '#0369A1',
                'accent'      => '#06B6D4',
                'background'  => '#F0F9FF',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#F0F9FF',
                'input_border'=> '#BAE6FD',
                'text'        => '#0C4A6E',
                'hover'       => '#0284C7',
            ),
            'emerald_finance' => array(
                'name'        => __( 'Emerald Finance', 'ezzy-wallet' ),
                'primary'     => '#10B981',
                'secondary'   => '#047857',
                'accent'      => '#F59E0B',
                'background'  => '#F0FDF4',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#ECFDF5',
                'input_border'=> '#A7F3D0',
                'text'        => '#064E3B',
                'hover'       => '#059669',
            ),
            'midnight_dark' => array(
                'name'        => __( 'Midnight Dark', 'ezzy-wallet' ),
                'primary'     => '#6366F1',
                'secondary'   => '#1E1B4B',
                'accent'      => '#22D3EE',
                'background'  => '#0F172A',
                'card_bg'     => '#1E293B',
                'form_bg'     => '#1E293B',
                'input_border'=> '#334155',
                'text'        => '#E2E8F0',
                'hover'       => '#818CF8',
            ),
            'royal_purple' => array(
                'name'        => __( 'Royal Purple', 'ezzy-wallet' ),
                'primary'     => '#8B5CF6',
                'secondary'   => '#5B21B6',
                'accent'      => '#F59E0B',
                'background'  => '#FAF5FF',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#F5F3FF',
                'input_border'=> '#DDD6FE',
                'text'        => '#4C1D95',
                'hover'       => '#7C3AED',
            ),
            'sunset_orange' => array(
                'name'        => __( 'Sunset Orange', 'ezzy-wallet' ),
                'primary'     => '#F97316',
                'secondary'   => '#C2410C',
                'accent'      => '#FBBF24',
                'background'  => '#FFF7ED',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#FFEDD5',
                'input_border'=> '#FED7AA',
                'text'        => '#7C2D12',
                'hover'       => '#EA580C',
            ),
            'rose_gold' => array(
                'name'        => __( 'Rose Gold', 'ezzy-wallet' ),
                'primary'     => '#F43F5E',
                'secondary'   => '#BE123C',
                'accent'      => '#FCD34D',
                'background'  => '#FFF1F2',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#FFE4E6',
                'input_border'=> '#FECDD3',
                'text'        => '#881337',
                'hover'       => '#E11D48',
            ),
            'forest_green' => array(
                'name'        => __( 'Forest Green', 'ezzy-wallet' ),
                'primary'     => '#22C55E',
                'secondary'   => '#15803D',
                'accent'      => '#84CC16',
                'background'  => '#F0FDF4',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#DCFCE7',
                'input_border'=> '#BBF7D0',
                'text'        => '#14532D',
                'hover'       => '#16A34A',
            ),
            'slate_professional' => array(
                'name'        => __( 'Slate Professional', 'ezzy-wallet' ),
                'primary'     => '#475569',
                'secondary'   => '#1E293B',
                'accent'      => '#3B82F6',
                'background'  => '#F8FAFC',
                'card_bg'     => '#FFFFFF',
                'form_bg'     => '#F1F5F9',
                'input_border'=> '#CBD5E1',
                'text'        => '#0F172A',
                'hover'       => '#334155',
            ),
            'cyber_neon' => array(
                'name'        => __( 'Cyber Neon', 'ezzy-wallet' ),
                'primary'     => '#14B8A6',
                'secondary'   => '#0D9488',
                'accent'      => '#F472B6',
                'background'  => '#042F2E',
                'card_bg'     => '#134E4A',
                'form_bg'     => '#115E59',
                'input_border'=> '#2DD4BF',
                'text'        => '#CCFBF1',
                'hover'       => '#5EEAD4',
            ),
        );
    }

    /**
     * Convert hex color to RGB values.
     *
     * @param string $hex Hex color code.
     * @return string RGB values as comma-separated string.
     */
    private function hex_to_rgb( $hex ) {
        $hex = ltrim( $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        return "{$r}, {$g}, {$b}";
    }

    /**
     * Handle form submissions.
     *
     * @return void
     */
    public function handle_form_submissions() {
        // Handle signup.
        if ( isset( $_POST['ezzy_wallet_signup'] ) ) {
            $this->process_signup();
        }

        // Handle login.
        if ( isset( $_POST['ezzy_wallet_login'] ) ) {
            $this->process_login();
        }
    }

    /**
     * Render the wallet shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function render_wallet_shortcode( $atts ) {
        ob_start();

        // Check maintenance mode.
        if ( $this->is_maintenance_mode() && ! $this->can_bypass_maintenance() ) {
            $this->render_maintenance_message();
            return ob_get_clean();
        }

        // Route based on login status.
        if ( is_user_logged_in() ) {
            $this->render_dashboard();
        } else {
            $this->render_landing_page();
        }

        return ob_get_clean();
    }

    /**
     * Check if maintenance mode is enabled.
     *
     * @return bool True if maintenance mode is enabled.
     */
    private function is_maintenance_mode() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        return ! empty( $settings['maintenance_mode'] );
    }

    /**
     * Check if current user can bypass maintenance.
     *
     * @return bool True if user can bypass.
     */
    private function can_bypass_maintenance() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Render maintenance message.
     *
     * @return void
     */
    private function render_maintenance_message() {
        ?>
        <div class="ezzy-wallet-maintenance">
            <div class="ezzy-maintenance-container">
                <div class="ezzy-maintenance-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2><?php esc_html_e( 'Under Maintenance', 'ezzy-wallet' ); ?></h2>
                <p><?php esc_html_e( 'Wallet Under Maintenance. Please check back later.', 'ezzy-wallet' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the landing page.
     *
     * @return void
     */
    private function render_landing_page() {
        include EZZY_WALLET_PLUGIN_DIR . 'public/partials/landing-page.php';
    }

    /**
     * Render dashboard.
     *
     * @return void
     */
    private function render_dashboard() {
        include EZZY_WALLET_PLUGIN_DIR . 'public/partials/dashboard.php';
    }

    /**
     * Get wallet page URL.
     *
     * @return string Wallet page URL.
     */
    public function get_wallet_page_url() {
        global $post;

        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ezzy_wallet' ) ) {
            return get_permalink( $post->ID );
        }

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
     * Process signup form.
     *
     * @return void
     */
    private function process_signup() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_signup_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_signup_nonce'] ) ), 'ezzy_wallet_signup' ) ) {
            $this->messages['signup_error'] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Check maintenance mode.
        if ( $this->is_maintenance_mode() && ! $this->can_bypass_maintenance() ) {
            $this->messages['signup_error'] = __( 'Signups are disabled during maintenance.', 'ezzy-wallet' );
            return;
        }

        // Sanitize inputs.
        $username   = isset( $_POST['ezzy_username'] ) ? sanitize_user( wp_unslash( $_POST['ezzy_username'] ) ) : '';
        $first_name = isset( $_POST['ezzy_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_first_name'] ) ) : '';
        $last_name  = isset( $_POST['ezzy_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_last_name'] ) ) : '';
        $email      = isset( $_POST['ezzy_email'] ) ? sanitize_email( wp_unslash( $_POST['ezzy_email'] ) ) : '';
        $phone      = isset( $_POST['ezzy_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_phone'] ) ) : '';
        $dob        = isset( $_POST['ezzy_dob'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_dob'] ) ) : '';
        $country    = isset( $_POST['ezzy_country'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_country'] ) ) : '';
        $password   = isset( $_POST['ezzy_password'] ) ? $_POST['ezzy_password'] : '';
        $confirm    = isset( $_POST['ezzy_confirm_password'] ) ? $_POST['ezzy_confirm_password'] : '';
        $terms      = isset( $_POST['ezzy_terms'] ) ? true : false;

        // Validate username.
        if ( empty( $username ) || strlen( $username ) < 3 ) {
            $this->messages['signup_error'] = __( 'Username must be at least 3 characters.', 'ezzy-wallet' );
            return;
        }

        if ( username_exists( $username ) ) {
            $this->messages['signup_error'] = __( 'Username already exists. Please choose another.', 'ezzy-wallet' );
            return;
        }

        // Validate first name.
        if ( empty( $first_name ) ) {
            $this->messages['signup_error'] = __( 'First name is required.', 'ezzy-wallet' );
            return;
        }

        // Validate email.
        if ( empty( $email ) || ! is_email( $email ) ) {
            $this->messages['signup_error'] = __( 'Please enter a valid email address.', 'ezzy-wallet' );
            return;
        }

        if ( email_exists( $email ) ) {
            $this->messages['signup_error'] = __( 'Email already registered. Please login or use another email.', 'ezzy-wallet' );
            return;
        }

        // Validate phone.
        if ( empty( $phone ) ) {
            $this->messages['signup_error'] = __( 'Phone number is required.', 'ezzy-wallet' );
            return;
        }

        // Validate date of birth.
        if ( empty( $dob ) ) {
            $this->messages['signup_error'] = __( 'Date of birth is required.', 'ezzy-wallet' );
            return;
        }

        // Validate age (must be 18+).
        $dob_timestamp = strtotime( $dob );
        $min_age_timestamp = strtotime( '-18 years' );
        if ( $dob_timestamp > $min_age_timestamp ) {
            $this->messages['signup_error'] = __( 'You must be at least 18 years old to create an account.', 'ezzy-wallet' );
            return;
        }

        // Validate country.
        if ( empty( $country ) ) {
            $this->messages['signup_error'] = __( 'Please select your country.', 'ezzy-wallet' );
            return;
        }

        // Validate password.
        if ( empty( $password ) || strlen( $password ) < 8 ) {
            $this->messages['signup_error'] = __( 'Password must be at least 8 characters.', 'ezzy-wallet' );
            return;
        }

        // Strong password check.
        if ( ! $this->is_strong_password( $password ) ) {
            $this->messages['signup_error'] = __( 'Password must include uppercase, lowercase, number, and special character.', 'ezzy-wallet' );
            return;
        }

        // Confirm password match.
        if ( $password !== $confirm ) {
            $this->messages['signup_error'] = __( 'Passwords do not match.', 'ezzy-wallet' );
            return;
        }

        // Validate terms acceptance.
        if ( ! $terms ) {
            $this->messages['signup_error'] = __( 'You must agree to the Terms of Service and Privacy Policy.', 'ezzy-wallet' );
            return;
        }

        // Create user.
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $this->messages['signup_error'] = $user_id->get_error_message();
            return;
        }

        // Build display name.
        $display_name = $first_name;
        if ( ! empty( $last_name ) ) {
            $display_name .= ' ' . $last_name;
        }

        // Update user core data.
        wp_update_user(
            array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
            )
        );

        // Save additional user meta (eZZY Wallet specific).
        update_user_meta( $user_id, 'ezzy_wallet_phone', $phone );
        update_user_meta( $user_id, 'ezzy_wallet_dob', $dob );
        update_user_meta( $user_id, 'ezzy_wallet_country', $country );
        update_user_meta( $user_id, 'ezzy_wallet_terms_accepted', current_time( 'mysql' ) );

        // Create wallet account.
        $this->create_wallet_account( $user_id );

        // Auto login.
        wp_set_auth_cookie( $user_id, true );
        wp_set_current_user( $user_id );

        // Redirect.
        wp_safe_redirect( $this->get_wallet_page_url() );
        exit;
    }

    /**
     * Check if password is strong.
     *
     * @param string $password Password to check.
     * @return bool True if strong.
     */
    private function is_strong_password( $password ) {
        // At least one uppercase.
        if ( ! preg_match( '/[A-Z]/', $password ) ) {
            return false;
        }

        // At least one lowercase.
        if ( ! preg_match( '/[a-z]/', $password ) ) {
            return false;
        }

        // At least one number.
        if ( ! preg_match( '/[0-9]/', $password ) ) {
            return false;
        }

        // At least one special character.
        if ( ! preg_match( '/[^A-Za-z0-9]/', $password ) ) {
            return false;
        }

        return true;
    }

    /**
     * Create wallet account for user.
     *
     * @param int $user_id User ID.
     * @return bool True on success.
     */
    private function create_wallet_account( $user_id ) {
        global $wpdb;

        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();

        // Check if account already exists.
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$accounts_table} WHERE user_id = %d",
                $user_id
            )
        );

        if ( $exists ) {
            return true;
        }

        // Create wallet account.
        $result = $wpdb->insert(
            $accounts_table,
            array(
                'user_id'              => $user_id,
                'balance'              => '0.00000000',
                'total_deposit'        => '0.00000000',
                'total_transfer'       => '0.00000000',
                'total_withdrawal'     => '0.00000000',
                'withdrawals_disabled' => 0,
                'created_at'           => current_time( 'mysql' ),
                'updated_at'           => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        // Log transaction.
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id'     => $user_id,
                'type'        => 'account_created',
                'amount'      => '0.00000000',
                'description' => __( 'Wallet account created', 'ezzy-wallet' ),
                'status'      => 'system',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        return true;
    }

    /**
     * Process login form.
     *
     * @return void
     */
    private function process_login() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_login_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_login_nonce'] ) ), 'ezzy_wallet_login' ) ) {
            $this->messages['login_error'] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Check maintenance mode.
        if ( $this->is_maintenance_mode() && ! $this->can_bypass_maintenance() ) {
            $this->messages['login_error'] = __( 'Login is disabled during maintenance.', 'ezzy-wallet' );
            return;
        }

        // Check rate limiting.
        if ( $this->is_login_locked() ) {
            $this->messages['login_error'] = __( 'Too many failed attempts. Please try again later.', 'ezzy-wallet' );
            return;
        }

        // Sanitize inputs.
        $username_email = isset( $_POST['ezzy_login_username'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_login_username'] ) ) : '';
        $password       = isset( $_POST['ezzy_login_password'] ) ? $_POST['ezzy_login_password'] : '';

        // Validate inputs.
        if ( empty( $username_email ) || empty( $password ) ) {
            $this->messages['login_error'] = __( 'Please enter your username/email and password.', 'ezzy-wallet' );
            return;
        }

        // Determine if email or username.
        if ( is_email( $username_email ) ) {
            $user = get_user_by( 'email', $username_email );
            if ( $user ) {
                $username_email = $user->user_login;
            }
        }

        // Attempt login.
        $credentials = array(
            'user_login'    => $username_email,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon( $credentials, is_ssl() );

        if ( is_wp_error( $user ) ) {
            $this->increment_login_attempts();
            $this->messages['login_error'] = __( 'Invalid username/email or password.', 'ezzy-wallet' );
            return;
        }

        // Reset login attempts.
        $this->reset_login_attempts();

        // Ensure user has wallet account.
        $this->create_wallet_account( $user->ID );

        // Redirect.
        wp_safe_redirect( $this->get_wallet_page_url() );
        exit;
    }

    /**
     * Check if login is locked due to too many attempts.
     *
     * @return bool True if locked.
     */
    private function is_login_locked() {
        if ( ! isset( $_SESSION['ezzy_login_attempts'] ) ) {
            return false;
        }

        $attempts = $_SESSION['ezzy_login_attempts'];

        if ( $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS ) {
            $lockout_expires = $attempts['time'] + self::LOCKOUT_DURATION;

            if ( time() < $lockout_expires ) {
                return true;
            }

            // Lockout expired, reset.
            $this->reset_login_attempts();
        }

        return false;
    }

    /**
     * Increment login attempts counter.
     *
     * @return void
     */
    private function increment_login_attempts() {
        if ( ! isset( $_SESSION['ezzy_login_attempts'] ) ) {
            $_SESSION['ezzy_login_attempts'] = array(
                'count' => 0,
                'time'  => time(),
            );
        }

        $_SESSION['ezzy_login_attempts']['count']++;

        if ( $_SESSION['ezzy_login_attempts']['count'] >= self::MAX_LOGIN_ATTEMPTS ) {
            $_SESSION['ezzy_login_attempts']['time'] = time();
        }
    }

    /**
     * Reset login attempts counter.
     *
     * @return void
     */
    private function reset_login_attempts() {
        if ( isset( $_SESSION['ezzy_login_attempts'] ) ) {
            unset( $_SESSION['ezzy_login_attempts'] );
        }
    }

    /**
     * Get signup error message.
     *
     * @return string Error message or empty string.
     */
    public function get_signup_error() {
        return isset( $this->messages['signup_error'] ) ? $this->messages['signup_error'] : '';
    }

    /**
     * Get login error message.
     *
     * @return string Error message or empty string.
     */
    public function get_login_error() {
        return isset( $this->messages['login_error'] ) ? $this->messages['login_error'] : '';
    }

    /**
     * Check if withdrawals are enabled globally.
     *
     * @return bool True if withdrawals are enabled globally.
     */
    public static function is_withdrawals_enabled_globally() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        // Default to true if not set
        return ! isset( $settings['enable_withdrawals'] ) || ! empty( $settings['enable_withdrawals'] );
    }

    /**
     * Check if withdrawals are disabled for a specific user.
     *
     * @param int $user_id User ID to check.
     * @return bool True if withdrawals are disabled for user.
     */
    public static function is_user_withdrawals_disabled( $user_id ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $disabled = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT withdrawals_disabled FROM {$table_name} WHERE user_id = %d",
                $user_id
            )
        );

        return (bool) $disabled;
    }

    /**
     * Check if user can withdraw.
     * 
     * Logic:
     * - If global is OFF and individual is OFF (0) -> Cannot withdraw
     * - If global is OFF and individual is ON (enabled override) -> CAN withdraw
     * - If global is ON and individual is OFF (0) -> CAN withdraw
     * - If global is ON and individual is ON (disabled) -> Cannot withdraw
     *
     * @param int $user_id User ID to check.
     * @return bool True if user can withdraw.
     */
    public static function can_user_withdraw( $user_id ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_accounts_table_name();
        $settings = get_option( 'ezzy_wallet_settings', array() );

        // Get global setting (default true if not set)
        $global_enabled = ! isset( $settings['enable_withdrawals'] ) || ! empty( $settings['enable_withdrawals'] );

        // Get user's withdrawal setting from database
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $user_setting = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT withdrawals_disabled FROM {$table_name} WHERE user_id = %d",
                $user_id
            )
        );

        // If no account found, default to following global setting
        if ( null === $user_setting ) {
            return $global_enabled;
        }

        $user_setting = (int) $user_setting;

        // Logic:
        // withdrawals_disabled = 0 -> follows global setting
        // withdrawals_disabled = 1 -> individually disabled (overrides global ON)
        // withdrawals_disabled = 2 -> individually enabled (overrides global OFF)

        if ( $user_setting === 2 ) {
            // Individual override: enabled regardless of global
            return true;
        }

        if ( $user_setting === 1 ) {
            // Individual override: disabled regardless of global
            return false;
        }

        // Default (0): follow global setting
        return $global_enabled;
    }

    /**
     * AJAX handler: Switch currency.
     *
     * @return void
     */
    public function ajax_switch_currency() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id  = get_current_user_id();
        $currency = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : '';

        // Validate currency.
        $supported = Ezzy_Wallet_Data_Service::get_supported_currencies();
        if ( ! in_array( $currency, $supported, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid currency.', 'ezzy-wallet' ) ) );
        }

        // Save preference.
        Ezzy_Wallet_Data_Service::set_user_currency_preference( $user_id, $currency );

        // Get updated data.
        $data = $this->get_dashboard_data_for_user( $user_id );

        wp_send_json_success( $data );
    }

    /**
     * AJAX handler: Update profile.
     *
     * @return void
     */
    public function ajax_update_profile() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Rate limit check
        $rate_check = Ezzy_Wallet_Security::check_profile_rate_limit( $user_id );
        if ( ! $rate_check['allowed'] ) {
            wp_send_json_error( array( 'message' => $rate_check['message'] ) );
        }

        $user    = get_user_by( 'id', $user_id );
        $field   = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';

        // Handle password change separately
        if ( $field === 'password' ) {
            $current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
            $new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
            $confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

            // Verify current password
            if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
                wp_send_json_error( array( 'message' => __( 'Current password is incorrect.', 'ezzy-wallet' ) ) );
            }

            // Validate new password
            if ( empty( $new_password ) || strlen( $new_password ) < 8 ) {
                wp_send_json_error( array( 'message' => __( 'New password must be at least 8 characters.', 'ezzy-wallet' ) ) );
            }

            // Check password strength
            if ( ! $this->is_strong_password( $new_password ) ) {
                wp_send_json_error( array( 'message' => __( 'Password must include uppercase, lowercase, number, and special character.', 'ezzy-wallet' ) ) );
            }

            // Confirm passwords match
            if ( $new_password !== $confirm_password ) {
                wp_send_json_error( array( 'message' => __( 'New passwords do not match.', 'ezzy-wallet' ) ) );
            }

            // Update password
            wp_set_password( $new_password, $user_id );

            // Re-login the user
            wp_set_auth_cookie( $user_id, true );

            // Log password change
            $this->log_profile_update( $user_id, 'Password changed' );

            wp_send_json_success( array(
                'message' => __( 'Password updated successfully.', 'ezzy-wallet' ),
                'field'   => 'password',
            ) );
        }

        // Handle other field updates
        $value = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';

        $allowed_fields = array( 
            'first_name', 
            'last_name', 
            'display_name', 
            'email', 
            'phone', 
            'dob', 
            'country', 
            'address' 
        );

        if ( ! in_array( $field, $allowed_fields, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid field.', 'ezzy-wallet' ) ) );
        }

        // Sanitize based on field type
        switch ( $field ) {
            case 'email':
                $value = sanitize_email( $value );
                if ( ! is_email( $value ) ) {
                    wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ezzy-wallet' ) ) );
                }
                // Check if email exists for another user
                $existing_user = email_exists( $value );
                if ( $existing_user && $existing_user !== $user_id ) {
                    wp_send_json_error( array( 'message' => __( 'This email is already registered to another account.', 'ezzy-wallet' ) ) );
                }
                break;
            case 'address':
                $value = sanitize_textarea_field( $value );
                break;
            case 'dob':
                $value = sanitize_text_field( $value );
                // Validate age (must be 18+)
                if ( ! empty( $value ) ) {
                    $dob_timestamp = strtotime( $value );
                    $min_age_timestamp = strtotime( '-18 years' );
                    if ( $dob_timestamp > $min_age_timestamp ) {
                        wp_send_json_error( array( 'message' => __( 'You must be at least 18 years old.', 'ezzy-wallet' ) ) );
                    }
                }
                break;
            default:
                $value = sanitize_text_field( $value );
        }

        // Track what changed
        $old_value = '';
        $field_label = '';

        // Update the field
        switch ( $field ) {
            case 'first_name':
                $old_value = $user->first_name;
                $field_label = 'First Name';
                wp_update_user( array( 'ID' => $user_id, 'first_name' => $value ) );
                break;

            case 'last_name':
                $old_value = $user->last_name;
                $field_label = 'Last Name';
                wp_update_user( array( 'ID' => $user_id, 'last_name' => $value ) );
                break;

            case 'display_name':
                $old_value = $user->display_name;
                $field_label = 'Display Name';
                wp_update_user( array( 'ID' => $user_id, 'display_name' => $value ) );
                break;

            case 'email':
                $old_value = $user->user_email;
                $field_label = 'Email';
                wp_update_user( array( 'ID' => $user_id, 'user_email' => $value ) );
                break;

            case 'phone':
                $old_value = get_user_meta( $user_id, 'ezzy_wallet_phone', true );
                $field_label = 'Phone';
                update_user_meta( $user_id, 'ezzy_wallet_phone', $value );
                break;

            case 'dob':
                $old_value = get_user_meta( $user_id, 'ezzy_wallet_dob', true );
                $field_label = 'Date of Birth';
                update_user_meta( $user_id, 'ezzy_wallet_dob', $value );
                break;

            case 'country':
                $old_value = get_user_meta( $user_id, 'ezzy_wallet_country', true );
                $field_label = 'Country';
                update_user_meta( $user_id, 'ezzy_wallet_country', $value );
                break;

            case 'address':
                $old_value = get_user_meta( $user_id, 'ezzy_wallet_address', true );
                $field_label = 'Address';
                update_user_meta( $user_id, 'ezzy_wallet_address', $value );
                break;
        }

        // Log the change if value actually changed
        if ( $old_value !== $value ) {
            // Create user-friendly notification message
            $notification_message = sprintf(
                /* translators: %s: Field name that was updated */
                __( 'You successfully updated your %s.', 'ezzy-wallet' ),
                $field_label
            );
            $this->log_profile_update( $user_id, $notification_message );
        }

        // Get the formatted display value for response
        $display_value = $value;
        
        if ( $field === 'dob' && ! empty( $value ) ) {
            $display_value = date_i18n( get_option( 'date_format' ), strtotime( $value ) );
        } elseif ( $field === 'country' && ! empty( $value ) ) {
            $display_value = Ezzy_Wallet_Countries::get_country_name( $value );
        }

        wp_send_json_success( array(
            'message'       => __( 'Profile updated successfully.', 'ezzy-wallet' ),
            'field'         => $field,
            'value'         => $value,
            'display_value' => $display_value ?: '—',
        ) );
    }

    /**
     * Log profile update to transactions (shows in Messages panel).
     *
     * @param int    $user_id     User ID.
     * @param string $description Description of the change.
     * @return void
     */
    private function log_profile_update( $user_id, $description ) {
        global $wpdb;
        $table = Ezzy_Wallet_DB::get_transactions_table_name();

        $wpdb->insert(
            $table,
            array(
                'user_id'     => $user_id,
                'type'        => 'profile_update',
                'amount'      => 0,
                'description' => $description,
                'status'      => 'completed',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%f', '%s', '%s', '%s' )
        );
    }

    /**
     * AJAX handler: Get dashboard data.
     *
     * @return void
     */
    public function ajax_get_dashboard_data() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $data    = $this->get_dashboard_data_for_user( $user_id );

        wp_send_json_success( $data );
    }

    /**
     * Get all dashboard data for a user.
     *
     * @param int $user_id User ID.
     * @return array Dashboard data.
     */
    public function get_dashboard_data_for_user( $user_id ) {
        // Get user's currency preference.
        $currency = Ezzy_Wallet_Data_Service::get_user_currency_preference( $user_id );
        $symbol   = Ezzy_Wallet_Data_Service::get_currency_symbol( $currency );

        // Get balance in USD (stored value).
        $balance_usd = Ezzy_Wallet_Data_Service::get_user_balance( $user_id );

        // Convert to user's preferred currency.
        $balance_display = Ezzy_Wallet_Data_Service::convert_from_usd( $balance_usd, $currency );

        // Get crypto conversions.
        $crypto_conversions = Ezzy_Wallet_Data_Service::get_balance_crypto_conversions( $user_id, $currency );

        // Get recent transactions.
        $transactions = Ezzy_Wallet_Data_Service::get_user_transactions( $user_id, 10 );

        // Format transactions for display.
        $formatted_transactions = array();
        foreach ( $transactions as $tx ) {
            $tx_amount = (float) $tx['amount'];
            $tx_amount_display = Ezzy_Wallet_Data_Service::convert_from_usd( abs( $tx_amount ), $currency );
            
            $formatted_transactions[] = array(
                'id'           => $tx['id'],
                'type'         => Ezzy_Wallet_Data_Service::format_transaction_type( $tx['type'] ),
                'type_raw'     => $tx['type'],
                'amount'       => Ezzy_Wallet_Data_Service::format_transaction_amount( $tx['type'], $tx_amount, $currency ),
                'amount_class' => Ezzy_Wallet_Data_Service::get_transaction_amount_class( $tx['type'], $tx_amount ),
                'status'       => $tx['status'],
                'date'         => date_i18n( 'M d, Y', strtotime( $tx['created_at'] ) ),
                'description'  => $tx['description'],
            );
        }

        return array(
            'currency'       => $currency,
            'symbol'         => $symbol,
            'balance'        => $balance_display,
            'balance_formatted' => Ezzy_Wallet_Data_Service::format_balance( $balance_display, $currency ),
            'crypto'         => array(
                'btc'  => Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['BTC'], 'BTC' ),
                'eth'  => Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['ETH'], 'ETH' ),
                'usdt' => Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['USDT'], 'USDT' ),
            ),
            'transactions'   => $formatted_transactions,
            'can_withdraw'   => self::can_user_withdraw( $user_id ),
        );
    }

    /**
     * AJAX handler: Get notifications.
     *
     * @return void
     */
    public function ajax_get_notifications() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Get ALL notifications (admin + system like profile updates).
        $notifications = Ezzy_Wallet_Data_Service::get_all_notifications_for_user( $user_id );
        $unread_count = Ezzy_Wallet_Data_Service::get_unread_notification_count( $user_id );

        // Format notifications for display.
        $formatted = array();
        foreach ( $notifications as $notification ) {
            $formatted[] = array(
                'id'         => $notification['id'],
                'title'      => $notification['title'],
                'message'    => $notification['message'],
                'type'       => $notification['type'],
                'source'     => isset( $notification['source'] ) ? $notification['source'] : 'admin',
                'is_read'    => (bool) $notification['is_read'],
                'created_at' => $notification['created_at'],
                'date'       => date_i18n( 'M d, Y \a\t g:i A', strtotime( $notification['created_at'] ) ),
            );
        }

        wp_send_json_success( array(
            'notifications' => $formatted,
            'unread_count'  => $unread_count,
        ) );
    }

    /**
     * AJAX handler: Mark notifications as read.
     *
     * @return void
     */
    public function ajax_mark_notifications_read() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        // Don't use absint - notification_id can be "tx_123" format for system notifications
        $notification_id = isset( $_POST['notification_id'] ) ? sanitize_text_field( $_POST['notification_id'] ) : '';

        if ( ! empty( $notification_id ) && $notification_id !== '0' ) {
            // Mark single notification as read.
            Ezzy_Wallet_Data_Service::mark_notification_read( $user_id, $notification_id );
        } else {
            // Mark all as read.
            Ezzy_Wallet_Data_Service::mark_all_notifications_read( $user_id );
        }

        // Get updated unread count.
        $unread_count = Ezzy_Wallet_Data_Service::get_unread_notification_count( $user_id );

        wp_send_json_success( array(
            'unread_count' => $unread_count,
        ) );
    }

    /**
     * AJAX handler: Get all transactions with pagination.
     *
     * @return void
     */
    public function ajax_get_all_transactions() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = 10;

        // Get paginated transactions.
        $result = Ezzy_Wallet_Data_Service::get_user_transactions_paginated( $user_id, $page, $per_page );

        // Get user currency for formatting.
        $currency = Ezzy_Wallet_Data_Service::get_user_currency_preference( $user_id );
        $symbol = Ezzy_Wallet_Data_Service::get_currency_symbol( $currency );

        // Format transactions for display.
        $formatted_transactions = array();
        foreach ( $result['transactions'] as $tx ) {
            $amount = (float) $tx['amount'];
            $amount_usd = abs( $amount );
            $amount_display = Ezzy_Wallet_Data_Service::convert_from_usd( $amount_usd, $currency );

            // Normalize transaction type for display
            $tx_type = $tx['type'];
            $tx_type_display = $tx_type;
            
            if ( $tx_type === 'deposit_success' ) {
                $tx_type_display = 'deposit';
            } elseif ( $tx_type === 'withdraw_success' ) {
                $tx_type_display = 'withdrawal';
            } elseif ( $tx_type === 'transfer_success' ) {
                $tx_type_display = 'transfer';
            } elseif ( $tx_type === 'deposit_request' ) {
                $tx_type_display = 'deposit';
            } elseif ( $tx_type === 'withdrawal_request' ) {
                $tx_type_display = 'withdrawal';
            } elseif ( $tx_type === 'transfer_sent' ) {
                $tx_type_display = 'transfer';
            } elseif ( $tx_type === 'transfer_received' ) {
                $tx_type_display = 'transfer';
            }

            // Determine sign based on transaction type
            $positive_types = array( 'deposit', 'deposit_success', 'deposit_request', 'transfer_received' );
            $is_positive = in_array( $tx_type, $positive_types, true );
            
            $amount_class = $is_positive ? 'amount-positive' : 'amount-negative';
            $amount_prefix = $is_positive ? '+' : '-';
            $amount_formatted = $amount_prefix . $symbol . number_format( $amount_display, 2 );

            // Transaction type label
            $type_labels = array(
                'deposit'          => __( 'Deposit', 'ezzy-wallet' ),
                'withdrawal'       => __( 'Withdrawal', 'ezzy-wallet' ),
                'transfer'         => __( 'Transfer', 'ezzy-wallet' ),
            );
            $type_label = isset( $type_labels[ $tx_type_display ] ) ? $type_labels[ $tx_type_display ] : ucfirst( str_replace( '_', ' ', $tx_type_display ) );

            $formatted_transactions[] = array(
                'id'               => $tx['id'],
                'type'             => $tx_type_display,
                'type_label'       => $type_label,
                'description'      => ! empty( $tx['description'] ) ? $tx['description'] : '—',
                'amount'           => $amount,
                'amount_formatted' => $amount_formatted,
                'amount_class'     => $amount_class,
                'status'           => $tx['status'],
                'date'             => date_i18n( 'M d, Y', strtotime( $tx['created_at'] ) ),
                'time'             => date_i18n( 'g:i A', strtotime( $tx['created_at'] ) ),
                'datetime'         => date_i18n( 'M d, Y \a\t g:i A', strtotime( $tx['created_at'] ) ),
            );
        }

        wp_send_json_success( array(
            'transactions' => $formatted_transactions,
            'total'        => $result['total'],
            'total_pages'  => $result['total_pages'],
            'current_page' => $result['current_page'],
            'per_page'     => $result['per_page'],
        ) );
    }

    /**
     * AJAX handler: Get notifications with pagination.
     *
     * @return void
     */
    public function ajax_get_notifications_paginated() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = 5; // 5 messages per page

        // Get paginated notifications.
        $result = Ezzy_Wallet_Data_Service::get_notifications_paginated( $user_id, $page, $per_page );

        // Format notifications for display.
        $formatted = array();
        foreach ( $result['notifications'] as $notification ) {
            $formatted[] = array(
                'id'         => $notification['id'],
                'title'      => $notification['title'],
                'message'    => $notification['message'],
                'type'       => $notification['type'],
                'source'     => isset( $notification['source'] ) ? $notification['source'] : 'admin',
                'is_read'    => (bool) $notification['is_read'],
                'created_at' => $notification['created_at'],
                'date'       => date_i18n( 'M d, Y', strtotime( $notification['created_at'] ) ),
                'time'       => date_i18n( 'g:i A', strtotime( $notification['created_at'] ) ),
            );
        }

        wp_send_json_success( array(
            'notifications' => $formatted,
            'total'         => $result['total'],
            'total_pages'   => $result['total_pages'],
            'current_page'  => $result['current_page'],
            'per_page'      => $result['per_page'],
            'unread_count'  => Ezzy_Wallet_Data_Service::get_unread_notification_count( $user_id ),
        ) );
    }

    /**
     * AJAX handler: Delete all messages/notifications for user.
     *
     * @return void
     */
    public function ajax_delete_all_messages() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Delete all notifications for this user.
        $deleted = Ezzy_Wallet_Data_Service::delete_all_user_notifications( $user_id );

        wp_send_json_success( array(
            'message' => __( 'All messages have been deleted.', 'ezzy-wallet' ),
            'deleted' => $deleted,
        ) );
    }

    /**
     * AJAX handler: Delete all transactions for user.
     *
     * @return void
     */
    public function ajax_delete_all_transactions() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        // Check user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Delete all transactions for this user.
        $deleted = Ezzy_Wallet_Data_Service::delete_all_user_transactions( $user_id );

        wp_send_json_success( array(
            'message' => __( 'All transactions have been deleted.', 'ezzy-wallet' ),
            'deleted' => $deleted,
        ) );
    }

    /**
     * AJAX handler: Setup 2FA - Generate secret and QR code.
     *
     * @return void
     */
    public function ajax_setup_2fa() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Generate a new secret key (Base32 encoded, 16 characters)
        $secret = $this->generate_2fa_secret();

        // Store temporarily (will be confirmed after verification)
        update_user_meta( $user_id, 'ezzy_wallet_2fa_temp_secret', $secret );

        // Generate QR code URL for Google Authenticator
        $site_name = get_bloginfo( 'name' );
        $account_name = $user->user_email;
        $qr_url = $this->get_2fa_qr_url( $secret, $account_name, $site_name );

        wp_send_json_success( array(
            'secret'  => $secret,
            'qr_url'  => $qr_url,
            'message' => __( '2FA setup initiated.', 'ezzy-wallet' ),
        ) );
    }

    /**
     * Generate a random Base32 secret for 2FA.
     *
     * @return string Base32 encoded secret.
     */
    private function generate_2fa_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ( $i = 0; $i < 16; $i++ ) {
            $secret .= $chars[ wp_rand( 0, 31 ) ];
        }
        return $secret;
    }

    /**
     * Get QR code URL for 2FA.
     *
     * @param string $secret       The secret key.
     * @param string $account_name User email/account.
     * @param string $issuer       Site name.
     * @return string QR code image URL.
     */
    private function get_2fa_qr_url( $secret, $account_name, $issuer ) {
        $otpauth = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode( $issuer ),
            rawurlencode( $account_name ),
            $secret,
            rawurlencode( $issuer )
        );
        
        // Use Google Charts API to generate QR code
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . rawurlencode( $otpauth );
    }

    /**
     * AJAX handler: Verify 2FA code and enable.
     *
     * @return void
     */
    public function ajax_verify_2fa() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';

        if ( strlen( $code ) !== 6 || ! ctype_digit( $code ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid 6-digit code.', 'ezzy-wallet' ) ) );
        }

        // Get temporary secret
        $secret = get_user_meta( $user_id, 'ezzy_wallet_2fa_temp_secret', true );

        if ( empty( $secret ) ) {
            wp_send_json_error( array( 'message' => __( 'Please start the 2FA setup process again.', 'ezzy-wallet' ) ) );
        }

        // Verify the code
        if ( ! $this->verify_2fa_code( $secret, $code ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid verification code. Please try again.', 'ezzy-wallet' ) ) );
        }

        // Code is valid - enable 2FA
        update_user_meta( $user_id, 'ezzy_wallet_2fa_secret', $secret );
        update_user_meta( $user_id, 'ezzy_wallet_2fa_enabled', '1' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_temp_secret' );

        wp_send_json_success( array(
            'message' => __( 'Two-factor authentication has been enabled!', 'ezzy-wallet' ),
        ) );
    }

    /**
     * Verify a TOTP code against a secret.
     *
     * @param string $secret The secret key.
     * @param string $code   The 6-digit code to verify.
     * @return bool True if valid.
     */
    private function verify_2fa_code( $secret, $code ) {
        $time_slice = floor( time() / 30 );
        
        // Check current and adjacent time slices (allows for clock drift)
        for ( $i = -1; $i <= 1; $i++ ) {
            $calculated = $this->get_totp_code( $secret, $time_slice + $i );
            if ( $calculated === $code ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code for a given time slice.
     *
     * @param string $secret     The secret key (Base32).
     * @param int    $time_slice The time slice.
     * @return string 6-digit code.
     */
    private function get_totp_code( $secret, $time_slice ) {
        // Decode Base32 secret
        $secret_key = $this->base32_decode( $secret );
        
        // Pack time slice as 8-byte big-endian
        $time = pack( 'N*', 0 ) . pack( 'N*', $time_slice );
        
        // Generate HMAC-SHA1
        $hmac = hash_hmac( 'sha1', $time, $secret_key, true );
        
        // Get offset from last nibble
        $offset = ord( substr( $hmac, -1 ) ) & 0x0F;
        
        // Get 4 bytes starting at offset
        $code = (
            ( ( ord( $hmac[ $offset ] ) & 0x7F ) << 24 ) |
            ( ( ord( $hmac[ $offset + 1 ] ) & 0xFF ) << 16 ) |
            ( ( ord( $hmac[ $offset + 2 ] ) & 0xFF ) << 8 ) |
            ( ord( $hmac[ $offset + 3 ] ) & 0xFF )
        ) % 1000000;
        
        return str_pad( $code, 6, '0', STR_PAD_LEFT );
    }

    /**
     * Decode Base32 string.
     *
     * @param string $input Base32 encoded string.
     * @return string Decoded binary string.
     */
    private function base32_decode( $input ) {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper( $input );
        $input = str_replace( '=', '', $input );
        
        $buffer = 0;
        $bits_left = 0;
        $output = '';
        
        for ( $i = 0; $i < strlen( $input ); $i++ ) {
            $val = strpos( $map, $input[ $i ] );
            if ( $val === false ) {
                continue;
            }
            
            $buffer = ( $buffer << 5 ) | $val;
            $bits_left += 5;
            
            if ( $bits_left >= 8 ) {
                $bits_left -= 8;
                $output .= chr( ( $buffer >> $bits_left ) & 0xFF );
            }
        }
        
        return $output;
    }

    /**
     * AJAX handler: Disable 2FA.
     *
     * @return void
     */
    public function ajax_disable_2fa() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Remove 2FA data
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_secret' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_enabled' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_temp_secret' );

        wp_send_json_success( array(
            'message' => __( 'Two-factor authentication has been disabled.', 'ezzy-wallet' ),
        ) );
    }

    /**
     * AJAX handler: Change password.
     *
     * @return void
     */
    public function ajax_change_password() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        $current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
        $new_password = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
        $confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

        // Validate current password
        if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Current password is incorrect.', 'ezzy-wallet' ) ) );
        }

        // Validate new password matches confirmation
        if ( $new_password !== $confirm_password ) {
            wp_send_json_error( array( 'message' => __( 'New passwords do not match.', 'ezzy-wallet' ) ) );
        }

        // Validate password strength
        if ( strlen( $new_password ) < 8 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'ezzy-wallet' ) ) );
        }
        if ( ! preg_match( '/[A-Z]/', $new_password ) ) {
            wp_send_json_error( array( 'message' => __( 'Password must contain an uppercase letter.', 'ezzy-wallet' ) ) );
        }
        if ( ! preg_match( '/[a-z]/', $new_password ) ) {
            wp_send_json_error( array( 'message' => __( 'Password must contain a lowercase letter.', 'ezzy-wallet' ) ) );
        }
        if ( ! preg_match( '/[0-9]/', $new_password ) ) {
            wp_send_json_error( array( 'message' => __( 'Password must contain a number.', 'ezzy-wallet' ) ) );
        }
        if ( ! preg_match( '/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password ) ) {
            wp_send_json_error( array( 'message' => __( 'Password must contain a special character.', 'ezzy-wallet' ) ) );
        }

        // Update password
        wp_set_password( $new_password, $user_id );

        // Re-authenticate user so they don't get logged out
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        wp_send_json_success( array(
            'message' => __( 'Password updated successfully!', 'ezzy-wallet' ),
        ) );
    }

    /**
     * AJAX handler: Delete account.
     *
     * @return void
     */
    public function ajax_delete_account() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( $_POST['confirm'] ) : '';

        // Require confirmation
        if ( strtoupper( $confirm ) !== 'DELETE' ) {
            wp_send_json_error( array( 'message' => __( 'Please type DELETE to confirm.', 'ezzy-wallet' ) ) );
        }

        // Don't allow deleting admin accounts
        if ( user_can( $user_id, 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Administrator accounts cannot be deleted this way.', 'ezzy-wallet' ) ) );
        }

        // Delete wallet data first
        global $wpdb;
        
        // Delete from wallet accounts table
        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();
        $wpdb->delete( $accounts_table, array( 'user_id' => $user_id ), array( '%d' ) );

        // Delete from transactions table
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $wpdb->delete( $transactions_table, array( 'user_id' => $user_id ), array( '%d' ) );

        // Delete from notification reads table
        $reads_table = Ezzy_Wallet_DB::get_notification_reads_table_name();
        $wpdb->delete( $reads_table, array( 'user_id' => $user_id ), array( '%d' ) );

        // Delete all user meta related to ezzy wallet
        delete_user_meta( $user_id, 'ezzy_wallet_phone' );
        delete_user_meta( $user_id, 'ezzy_wallet_dob' );
        delete_user_meta( $user_id, 'ezzy_wallet_country' );
        delete_user_meta( $user_id, 'ezzy_wallet_address' );
        delete_user_meta( $user_id, 'ezzy_wallet_terms_accepted' );
        delete_user_meta( $user_id, 'ezzy_wallet_currency_preference' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_enabled' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_secret' );
        delete_user_meta( $user_id, 'ezzy_wallet_2fa_temp_secret' );

        // Log out the user
        wp_logout();

        // Delete the WordPress user
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $user_id );

        wp_send_json_success( array(
            'message'  => __( 'Your account has been deleted.', 'ezzy-wallet' ),
            'redirect' => home_url(),
        ) );
    }

    /**
     * AJAX handler: Send support email.
     *
     * @return void
     */
    public function ajax_send_support_email() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Get form data
        $username = sanitize_text_field( isset( $_POST['username'] ) ? $_POST['username'] : $user->user_login );
        $email = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : $user->user_email );
        $phone = sanitize_text_field( isset( $_POST['phone'] ) ? $_POST['phone'] : '' );
        $subject_type = sanitize_text_field( isset( $_POST['subject'] ) ? $_POST['subject'] : 'general' );
        $message = sanitize_textarea_field( isset( $_POST['message'] ) ? $_POST['message'] : '' );

        // Validate message
        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a message.', 'ezzy-wallet' ) ) );
        }

        if ( strlen( $message ) > 1000 ) {
            wp_send_json_error( array( 'message' => __( 'Message is too long. Maximum 1000 characters.', 'ezzy-wallet' ) ) );
        }

        // Get admin notification email from settings
        $admin_email = get_option( 'ezzy_wallet_admin_email', get_option( 'admin_email' ) );

        if ( empty( $admin_email ) ) {
            $admin_email = get_option( 'admin_email' );
        }

        // Map subject types to readable labels
        $subject_labels = array(
            'deposit'    => __( 'Deposit Issue', 'ezzy-wallet' ),
            'withdrawal' => __( 'Withdrawal Issue', 'ezzy-wallet' ),
            'account'    => __( 'Account Problem', 'ezzy-wallet' ),
            'security'   => __( 'Security Concern', 'ezzy-wallet' ),
            'feature'    => __( 'Feature Request', 'ezzy-wallet' ),
            'other'      => __( 'Other', 'ezzy-wallet' ),
        );

        $subject_label = isset( $subject_labels[ $subject_type ] ) ? $subject_labels[ $subject_type ] : __( 'Support Request', 'ezzy-wallet' );

        // Build email subject
        $site_name = get_bloginfo( 'name' );
        $email_subject = sprintf(
            /* translators: 1: Site name, 2: Subject type */
            __( '[%1$s] Support Request: %2$s', 'ezzy-wallet' ),
            $site_name,
            $subject_label
        );

        // Build email body
        $email_body = sprintf(
            __( 'You have received a new support request from eZZY Wallet.', 'ezzy-wallet' )
        ) . "\n\n";

        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $email_body .= __( 'USER DETAILS', 'ezzy-wallet' ) . "\n";
        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $email_body .= sprintf( __( 'Username: %s', 'ezzy-wallet' ), $username ) . "\n";
        $email_body .= sprintf( __( 'Email: %s', 'ezzy-wallet' ), $email ) . "\n";
        
        if ( ! empty( $phone ) ) {
            $email_body .= sprintf( __( 'Phone: %s', 'ezzy-wallet' ), $phone ) . "\n";
        }

        $email_body .= sprintf( __( 'User ID: %d', 'ezzy-wallet' ), $user_id ) . "\n";
        $email_body .= sprintf( __( 'Subject: %s', 'ezzy-wallet' ), $subject_label ) . "\n\n";

        // Generate unique ticket number
        $ticket_number = 'EZW-' . strtoupper( substr( md5( $user_id . time() . wp_rand() ), 0, 8 ) );

        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $email_body .= __( 'TICKET INFORMATION', 'ezzy-wallet' ) . "\n";
        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $email_body .= sprintf( __( 'Ticket Number: %s', 'ezzy-wallet' ), $ticket_number ) . "\n\n";

        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $email_body .= __( 'MESSAGE', 'ezzy-wallet' ) . "\n";
        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $email_body .= $message . "\n\n";

        $email_body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $email_body .= sprintf(
            __( 'Sent from: %s', 'ezzy-wallet' ),
            home_url()
        ) . "\n";

        $email_body .= sprintf(
            __( 'Date: %s', 'ezzy-wallet' ),
            current_time( 'F j, Y \a\t g:i A' )
        ) . "\n";

        // Email headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            sprintf( 'Reply-To: %s <%s>', $username, $email ),
        );

        // Send email
        $sent = wp_mail( $admin_email, $email_subject, $email_body, $headers );

        if ( $sent ) {
            wp_send_json_success( array(
                'message'       => __( 'Your message has been sent successfully!', 'ezzy-wallet' ),
                'ticket_number' => $ticket_number,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Failed to send message. Please try again later.', 'ezzy-wallet' ),
            ) );
        }
    }

    /* ==========================================================================
       WALLET ACTIONS - DEPOSIT, WITHDRAW, TRANSFER
       ========================================================================== */

    /**
     * AJAX handler: Deposit request.
     *
     * @return void
     */
    public function ajax_deposit_request() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Rate limit check
        $rate_check = Ezzy_Wallet_Security::check_deposit_rate_limit( $user_id );
        if ( ! $rate_check['allowed'] ) {
            wp_send_json_error( array( 'message' => $rate_check['message'] ) );
        }

        $amount = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
        $method = isset( $_POST['method'] ) ? sanitize_text_field( $_POST['method'] ) : '';
        $note = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

        // Validate amount with security class
        $amount_check = Ezzy_Wallet_Security::validate_amount( $amount );
        if ( ! $amount_check['valid'] ) {
            wp_send_json_error( array( 'message' => $amount_check['message'] ) );
        }
        $amount = $amount_check['amount'];

        if ( empty( $method ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a deposit method.', 'ezzy-wallet' ) ) );
        }

        // Create deposit request
        $result = Ezzy_Wallet_Data_Service::create_deposit_request( $user_id, $amount, $method, $note );

        if ( $result['success'] ) {
            // Log audit event
            Ezzy_Wallet_Security::log_audit_event( 'deposit_request', $user_id, array(
                'amount' => $amount,
                'method' => $method,
                'transaction_id' => $result['transaction_id'],
            ) );

            wp_send_json_success( array(
                'message'        => $result['message'],
                'transaction_id' => $result['transaction_id'],
            ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }

    /**
     * AJAX handler: Withdrawal request.
     *
     * @return void
     */
    public function ajax_withdraw_request() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Rate limit check
        $rate_check = Ezzy_Wallet_Security::check_withdrawal_rate_limit( $user_id );
        if ( ! $rate_check['allowed'] ) {
            wp_send_json_error( array( 'message' => $rate_check['message'] ) );
        }

        $amount = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
        $method = isset( $_POST['method'] ) ? sanitize_text_field( $_POST['method'] ) : '';
        $destination = isset( $_POST['destination'] ) ? sanitize_text_field( $_POST['destination'] ) : '';
        $note = isset( $_POST['note'] ) ? sanitize_textarea_field( $_POST['note'] ) : '';

        // Validate amount with security class
        $amount_check = Ezzy_Wallet_Security::validate_amount( $amount );
        if ( ! $amount_check['valid'] ) {
            wp_send_json_error( array( 'message' => $amount_check['message'] ) );
        }
        $amount = $amount_check['amount'];

        // Check sufficient balance before creating request
        $balance_check = Ezzy_Wallet_Security::verify_sufficient_balance( $user_id, $amount );
        if ( ! $balance_check['sufficient'] ) {
            wp_send_json_error( array( 'message' => $balance_check['message'] ) );
        }

        if ( empty( $method ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a withdrawal method.', 'ezzy-wallet' ) ) );
        }

        if ( empty( $destination ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter your withdrawal destination.', 'ezzy-wallet' ) ) );
        }

        // Create withdrawal request
        $result = Ezzy_Wallet_Data_Service::create_withdrawal_request( $user_id, $amount, $method, $destination, $note );

        if ( $result['success'] ) {
            // Log audit event
            Ezzy_Wallet_Security::log_audit_event( 'withdrawal_request', $user_id, array(
                'amount' => $amount,
                'method' => $method,
                'destination' => $destination,
                'transaction_id' => $result['transaction_id'],
            ) );

            wp_send_json_success( array(
                'message'        => $result['message'],
                'transaction_id' => $result['transaction_id'],
            ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }

    /**
     * AJAX handler: Transfer.
     *
     * @return void
     */
    public function ajax_transfer() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Rate limit check
        $rate_check = Ezzy_Wallet_Security::check_transfer_rate_limit( $user_id );
        if ( ! $rate_check['allowed'] ) {
            wp_send_json_error( array( 'message' => $rate_check['message'] ) );
        }

        $recipient = isset( $_POST['recipient'] ) ? sanitize_text_field( $_POST['recipient'] ) : '';
        $amount = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

        // Validate recipient
        if ( empty( $recipient ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a recipient username or email.', 'ezzy-wallet' ) ) );
        }

        // Validate amount with security class
        $amount_check = Ezzy_Wallet_Security::validate_amount( $amount );
        if ( ! $amount_check['valid'] ) {
            wp_send_json_error( array( 'message' => $amount_check['message'] ) );
        }
        $amount = $amount_check['amount'];

        // Execute transfer (additional checks in execute_transfer)
        $result = Ezzy_Wallet_Data_Service::execute_transfer( $user_id, $recipient, $amount, $message );

        if ( $result['success'] ) {
            // Log audit event
            Ezzy_Wallet_Security::log_audit_event( 'transfer_sent', $user_id, array(
                'amount' => $amount,
                'recipient' => $recipient,
                'new_balance' => $result['new_balance'],
            ) );

            // Get updated balance formatted
            $new_balance = $result['new_balance'];
            $currency = get_user_meta( $user_id, 'ezzy_wallet_currency_preference', true );
            if ( empty( $currency ) ) {
                $currency = Ezzy_Wallet_Data_Service::get_admin_default_currency();
            }
            $converted = Ezzy_Wallet_Data_Service::convert_balance( $new_balance, $currency );
            $symbol = Ezzy_Wallet_Data_Service::get_currency_symbol( $currency );

            wp_send_json_success( array(
                'message'           => $result['message'],
                'new_balance'       => $new_balance,
                'balance_formatted' => $symbol . number_format( $converted, 2 ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }

    /**
     * AJAX handler: Upload profile image.
     *
     * @return void
     */
    public function ajax_upload_profile_image() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'ezzy_wallet_frontend_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ezzy-wallet' ) ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'ezzy-wallet' ) ) );
        }

        $user_id = get_current_user_id();

        // Check if file was uploaded
        if ( empty( $_FILES['profile_image'] ) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( array( 'message' => __( 'No image file uploaded or upload error occurred.', 'ezzy-wallet' ) ) );
        }

        $file = $_FILES['profile_image'];

        // Validate file type
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        $file_type = wp_check_filetype( $file['name'] );
        $mime_type = $file['type'];

        if ( ! in_array( $mime_type, $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.', 'ezzy-wallet' ) ) );
        }

        // Validate file size (max 2MB)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ( $file['size'] > $max_size ) {
            wp_send_json_error( array( 'message' => __( 'Image file is too large. Maximum size is 2MB.', 'ezzy-wallet' ) ) );
        }

        // Validate image dimensions
        $image_info = getimagesize( $file['tmp_name'] );
        if ( ! $image_info ) {
            wp_send_json_error( array( 'message' => __( 'Invalid image file.', 'ezzy-wallet' ) ) );
        }

        $width = $image_info[0];
        $height = $image_info[1];

        // Min 100x100, Max 2000x2000
        if ( $width < 100 || $height < 100 ) {
            wp_send_json_error( array( 'message' => __( 'Image is too small. Minimum dimensions are 100x100 pixels.', 'ezzy-wallet' ) ) );
        }

        if ( $width > 2000 || $height > 2000 ) {
            wp_send_json_error( array( 'message' => __( 'Image is too large. Maximum dimensions are 2000x2000 pixels.', 'ezzy-wallet' ) ) );
        }

        // Include WordPress file handling functions
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Create upload directory for profile images
        $upload_dir = wp_upload_dir();
        $profile_dir = $upload_dir['basedir'] . '/ezzy-wallet-profiles';
        
        if ( ! file_exists( $profile_dir ) ) {
            wp_mkdir_p( $profile_dir );
            // Add index.php for security
            file_put_contents( $profile_dir . '/index.php', '<?php // Silence is golden.' );
        }

        // Delete old profile image if exists
        $old_image = get_user_meta( $user_id, 'ezzy_wallet_profile_image', true );
        $old_image_path = get_user_meta( $user_id, 'ezzy_wallet_profile_image_path', true );
        if ( ! empty( $old_image_path ) && file_exists( $old_image_path ) ) {
            @unlink( $old_image_path );
        }

        // Generate unique filename
        $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $filename = 'profile-' . $user_id . '-' . time() . '.' . $extension;
        $filepath = $profile_dir . '/' . $filename;

        // Move uploaded file
        if ( ! move_uploaded_file( $file['tmp_name'], $filepath ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save image. Please try again.', 'ezzy-wallet' ) ) );
        }

        // Resize image if larger than 400x400 (for performance)
        $editor = wp_get_image_editor( $filepath );
        if ( ! is_wp_error( $editor ) ) {
            $editor->resize( 400, 400, true );
            $editor->save( $filepath );
        }

        // Generate URL
        $image_url = $upload_dir['baseurl'] . '/ezzy-wallet-profiles/' . $filename;

        // Save to user meta
        update_user_meta( $user_id, 'ezzy_wallet_profile_image', $image_url );
        update_user_meta( $user_id, 'ezzy_wallet_profile_image_path', $filepath );

        // Log audit event
        Ezzy_Wallet_Security::log_audit_event( 'profile_image_updated', $user_id, array(
            'filename' => $filename,
        ) );

        wp_send_json_success( array(
            'message'   => __( 'Profile image updated successfully!', 'ezzy-wallet' ),
            'image_url' => $image_url,
        ) );
    }
}
