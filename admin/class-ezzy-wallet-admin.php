<?php
/**
 * Admin functionality for eZZY Wallet.
 *
 * Handles all admin menu pages, settings, and configuration.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Admin
 *
 * Manages the admin interface for eZZY Wallet plugin.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Admin {

    /**
     * Settings option name.
     *
     * @var string
     */
    const OPTION_NAME = 'ezzy_wallet_settings';

    /**
     * Settings group name.
     *
     * @var string
     */
    const SETTINGS_GROUP = 'ezzy_wallet_settings_group';

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Wallet Users handler.
     *
     * @var Ezzy_Wallet_Users
     */
    private $users_handler;

    /**
     * Transactions handler.
     *
     * @var Ezzy_Wallet_Transactions
     */
    private $transactions_handler;

    /**
     * Constructor.
     *
     * Initialize admin hooks.
     */
    public function __construct() {
        $this->settings = $this->get_settings();

        // Load additional admin classes.
        $this->load_dependencies();

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Add license timer to admin bar and footer.
        add_action( 'admin_bar_menu', array( $this, 'add_license_timer_to_admin_bar' ), 100 );
        add_action( 'admin_footer', array( $this, 'render_license_timer_script' ) );
    }

    /**
     * Load required dependencies.
     *
     * @return void
     */
    private function load_dependencies() {
        require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-users.php';
        require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-user-edit.php';
        require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-notifications.php';
        require_once EZZY_WALLET_PLUGIN_DIR . 'admin/class-ezzy-wallet-transactions.php';

        $this->users_handler = new Ezzy_Wallet_Users();
        $this->transactions_handler = new Ezzy_Wallet_Transactions();
    }

    /**
     * Get plugin settings with defaults.
     *
     * @return array Plugin settings.
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $settings = get_option( self::OPTION_NAME, array() );

        return wp_parse_args( $settings, $defaults );
    }

    /**
     * Get default settings.
     *
     * @return array Default settings.
     */
    public function get_default_settings() {
        return array(
            // General settings.
            'admin_email'             => get_option( 'admin_email' ),
            'default_currency'        => 'USD',
            'enable_email_notifications' => true,
            'enable_withdrawals'      => true,

            // Theme settings.
            'dashboard_skin_theme'    => 'classic_blue',

            // Maintenance settings.
            'maintenance_mode'        => false,
        );
    }

    /**
     * Add admin menu pages.
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page.
        add_menu_page(
            __( 'eZZY Wallet', 'ezzy-wallet' ),
            __( 'eZZY Wallet', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet',
            array( $this, 'render_general_settings_page' ),
            'dashicons-money-alt',
            30
        );

        // General Settings submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'General Settings', 'ezzy-wallet' ),
            __( 'General Settings', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet',
            array( $this, 'render_general_settings_page' )
        );

        // Theme Settings submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'Theme Settings', 'ezzy-wallet' ),
            __( 'Theme Settings', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet-theme',
            array( $this, 'render_theme_settings_page' )
        );

        // Maintenance Mode submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'Maintenance Mode', 'ezzy-wallet' ),
            __( 'Maintenance Mode', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet-maintenance',
            array( $this, 'render_maintenance_settings_page' )
        );

        // Wallet Users submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'Wallet Users', 'ezzy-wallet' ),
            __( 'Wallet Users', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet-users',
            array( $this, 'render_wallet_users_page' )
        );

        // Transactions submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'Transactions', 'ezzy-wallet' ),
            __( 'Transactions', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet-transactions',
            array( $this, 'render_transactions_page' )
        );

        // Notifications submenu.
        add_submenu_page(
            'ezzy-wallet',
            __( 'Notifications', 'ezzy-wallet' ),
            __( 'Notifications', 'ezzy-wallet' ),
            'manage_options',
            'ezzy-wallet-notifications',
            array( $this, 'render_notifications_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => $this->get_default_settings(),
            )
        );

        // General Settings Section.
        add_settings_section(
            'ezzy_wallet_general_section',
            __( 'General Configuration', 'ezzy-wallet' ),
            array( $this, 'render_general_section_description' ),
            'ezzy-wallet-general'
        );

        // Theme Settings Section.
        add_settings_section(
            'ezzy_wallet_theme_section',
            __( 'Theme Colors', 'ezzy-wallet' ),
            array( $this, 'render_theme_section_description' ),
            'ezzy-wallet-theme'
        );

        // Maintenance Settings Section.
        add_settings_section(
            'ezzy_wallet_maintenance_section',
            __( 'Maintenance Configuration', 'ezzy-wallet' ),
            array( $this, 'render_maintenance_section_description' ),
            'ezzy-wallet-maintenance'
        );

        // Register General Fields.
        $this->register_general_fields();

        // Register Theme Fields.
        $this->register_theme_fields();

        // Register Maintenance Fields.
        $this->register_maintenance_fields();
    }

    /**
     * Register general settings fields.
     *
     * @return void
     */
    private function register_general_fields() {
        add_settings_field(
            'admin_email',
            __( 'Admin Notification Email', 'ezzy-wallet' ),
            array( $this, 'render_email_field' ),
            'ezzy-wallet-general',
            'ezzy_wallet_general_section',
            array(
                'field' => 'admin_email',
                'description' => __( 'Email address for admin notifications.', 'ezzy-wallet' ),
            )
        );

        add_settings_field(
            'default_currency',
            __( 'Default Currency', 'ezzy-wallet' ),
            array( $this, 'render_currency_field' ),
            'ezzy-wallet-general',
            'ezzy_wallet_general_section',
            array(
                'field' => 'default_currency',
                'description' => __( 'Select the default currency for the wallet.', 'ezzy-wallet' ),
            )
        );

        add_settings_field(
            'enable_email_notifications',
            __( 'Enable Email Notifications', 'ezzy-wallet' ),
            array( $this, 'render_checkbox_field' ),
            'ezzy-wallet-general',
            'ezzy_wallet_general_section',
            array(
                'field' => 'enable_email_notifications',
                'description' => __( 'Send email notifications for wallet activities.', 'ezzy-wallet' ),
            )
        );

        add_settings_field(
            'enable_withdrawals',
            __( 'Enable Withdrawals Globally', 'ezzy-wallet' ),
            array( $this, 'render_checkbox_field' ),
            'ezzy-wallet-general',
            'ezzy_wallet_general_section',
            array(
                'field' => 'enable_withdrawals',
                'description' => __( 'Allow users to withdraw funds from their wallets.', 'ezzy-wallet' ),
            )
        );
    }

    /**
     * Register theme settings fields.
     *
     * @return void
     */
    private function register_theme_fields() {
        // Add Skin Theme Selector - the only theme setting needed
        add_settings_field(
            'dashboard_skin_theme',
            __( 'Dashboard Skin Theme', 'ezzy-wallet' ),
            array( $this, 'render_skin_theme_field' ),
            'ezzy-wallet-theme',
            'ezzy_wallet_theme_section',
            array(
                'field' => 'dashboard_skin_theme',
                'description' => __( 'Select a predefined color theme for the dashboard.', 'ezzy-wallet' ),
            )
        );
    }

    /**
     * Get available skin themes.
     * References the frontend class which has the canonical list.
     *
     * @return array Skin themes with their color definitions.
     */
    public static function get_skin_themes() {
        // If frontend class is available, use it
        if ( class_exists( 'Ezzy_Wallet_Frontend' ) ) {
            return Ezzy_Wallet_Frontend::get_skin_themes();
        }
        
        // Fallback - return basic themes if frontend not loaded yet
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
     * Render skin theme selector field.
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_skin_theme_field( $args ) {
        $field = $args['field'];
        $current = isset( $this->settings[ $field ] ) ? $this->settings[ $field ] : 'classic_blue';
        $name = self::OPTION_NAME . '[' . $field . ']';
        $themes = self::get_skin_themes();

        echo '<div class="ezzy-skin-theme-selector">';
        echo '<select id="' . esc_attr( $field ) . '" name="' . esc_attr( $name ) . '" class="ezzy-skin-select">';
        
        foreach ( $themes as $key => $theme ) {
            printf(
                '<option value="%s" %s data-primary="%s" data-secondary="%s" data-accent="%s" data-background="%s" data-card="%s" data-form="%s" data-border="%s" data-text="%s" data-hover="%s">%s</option>',
                esc_attr( $key ),
                selected( $current, $key, false ),
                esc_attr( $theme['primary'] ),
                esc_attr( $theme['secondary'] ),
                esc_attr( $theme['accent'] ),
                esc_attr( $theme['background'] ),
                esc_attr( $theme['card_bg'] ),
                esc_attr( $theme['form_bg'] ),
                esc_attr( $theme['input_border'] ),
                esc_attr( $theme['text'] ),
                esc_attr( $theme['hover'] ),
                esc_html( $theme['name'] )
            );
        }
        
        echo '</select>';

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }

        // Theme preview cards
        echo '<div class="ezzy-skin-previews">';
        foreach ( $themes as $key => $theme ) {
            $is_active = ( $current === $key ) ? ' active' : '';
            $is_dark = in_array( $key, array( 'midnight_dark', 'cyber_neon' ), true );
            printf(
                '<div class="ezzy-skin-preview-card%s" data-theme="%s" style="background: %s;">
                    <div class="preview-header" style="background: %s; color: %s;">%s</div>
                    <div class="preview-colors">
                        <span class="color-dot" style="background: %s;" title="Primary"></span>
                        <span class="color-dot" style="background: %s;" title="Secondary"></span>
                        <span class="color-dot" style="background: %s;" title="Accent"></span>
                    </div>
                </div>',
                esc_attr( $is_active ),
                esc_attr( $key ),
                esc_attr( $theme['card_bg'] ),
                esc_attr( $theme['primary'] ),
                $is_dark ? '#fff' : '#fff',
                esc_html( $theme['name'] ),
                esc_attr( $theme['primary'] ),
                esc_attr( $theme['secondary'] ),
                esc_attr( $theme['accent'] )
            );
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Register maintenance settings fields.
     *
     * @return void
     */
    private function register_maintenance_fields() {
        add_settings_field(
            'maintenance_mode',
            __( 'Enable Maintenance Mode', 'ezzy-wallet' ),
            array( $this, 'render_maintenance_toggle' ),
            'ezzy-wallet-maintenance',
            'ezzy_wallet_maintenance_section',
            array(
                'field' => 'maintenance_mode',
                'description' => __( 'When enabled, non-admin users will see a maintenance message on wallet pages.', 'ezzy-wallet' ),
            )
        );
    }

    /**
     * Sanitize settings on save.
     *
     * @param array $input Raw input data.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        $defaults  = $this->get_default_settings();

        // Sanitize admin email.
        if ( isset( $input['admin_email'] ) ) {
            $sanitized['admin_email'] = sanitize_email( $input['admin_email'] );
            if ( empty( $sanitized['admin_email'] ) ) {
                $sanitized['admin_email'] = $defaults['admin_email'];
                add_settings_error(
                    self::OPTION_NAME,
                    'invalid_email',
                    __( 'Invalid email address. Default email restored.', 'ezzy-wallet' ),
                    'error'
                );
            }
        }

        // Sanitize currency.
        $valid_currencies = array( 'USD', 'EUR', 'GBP' );
        if ( isset( $input['default_currency'] ) && in_array( $input['default_currency'], $valid_currencies, true ) ) {
            $sanitized['default_currency'] = $input['default_currency'];
        } else {
            $sanitized['default_currency'] = $defaults['default_currency'];
        }

        // Sanitize checkboxes.
        $sanitized['enable_email_notifications'] = ! empty( $input['enable_email_notifications'] );
        $sanitized['enable_withdrawals']         = ! empty( $input['enable_withdrawals'] );
        $sanitized['maintenance_mode']           = ! empty( $input['maintenance_mode'] );

        // Sanitize skin theme.
        $valid_themes = array_keys( self::get_skin_themes() );
        if ( isset( $input['dashboard_skin_theme'] ) && in_array( $input['dashboard_skin_theme'], $valid_themes, true ) ) {
            $sanitized['dashboard_skin_theme'] = $input['dashboard_skin_theme'];
        } else {
            $sanitized['dashboard_skin_theme'] = 'classic_blue';
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our admin pages.
        $allowed_pages = array(
            'toplevel_page_ezzy-wallet',
            'ezzy-wallet_page_ezzy-wallet-theme',
            'ezzy-wallet_page_ezzy-wallet-maintenance',
            'ezzy-wallet_page_ezzy-wallet-users',
        );

        if ( ! in_array( $hook, $allowed_pages, true ) ) {
            return;
        }

        // Enqueue WordPress color picker.
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Enqueue admin styles.
        wp_enqueue_style(
            'ezzy-wallet-admin',
            EZZY_WALLET_PLUGIN_URL . 'assets/css/admin.css',
            array( 'wp-color-picker' ),
            EZZY_WALLET_VERSION
        );

        // Enqueue admin scripts.
        wp_enqueue_script(
            'ezzy-wallet-admin',
            EZZY_WALLET_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-color-picker' ),
            EZZY_WALLET_VERSION,
            true
        );

        // Localize script.
        wp_localize_script(
            'ezzy-wallet-admin',
            'ezzyWalletAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ezzy_wallet_admin_nonce' ),
            )
        );

        // Localize strings.
        wp_localize_script(
            'ezzy-wallet-admin',
            'ezzyWalletAdminStrings',
            array(
                'maintenanceActive' => __( 'Maintenance Mode Active', 'ezzy-wallet' ),
                'walletOperational' => __( 'Wallet Operational', 'ezzy-wallet' ),
            )
        );
    }

    /**
     * Render general section description.
     *
     * @return void
     */
    public function render_general_section_description() {
        echo '<p>' . esc_html__( 'Configure general wallet settings below.', 'ezzy-wallet' ) . '</p>';
    }

    /**
     * Render theme section description.
     *
     * @return void
     */
    public function render_theme_section_description() {
        echo '<p>' . esc_html__( 'Customize the appearance of your wallet dashboard.', 'ezzy-wallet' ) . '</p>';
    }

    /**
     * Render maintenance section description.
     *
     * @return void
     */
    public function render_maintenance_section_description() {
        echo '<p>' . esc_html__( 'Control wallet availability for users.', 'ezzy-wallet' ) . '</p>';
    }

    /**
     * Render email field.
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_email_field( $args ) {
        $field = $args['field'];
        $value = isset( $this->settings[ $field ] ) ? $this->settings[ $field ] : '';
        $name  = self::OPTION_NAME . '[' . $field . ']';

        printf(
            '<input type="email" id="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
            esc_attr( $field ),
            esc_attr( $name ),
            esc_attr( $value )
        );

        if ( ! empty( $args['description'] ) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $args['description'] )
            );
        }
    }

    /**
     * Render currency dropdown field.
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_currency_field( $args ) {
        $field    = $args['field'];
        $value    = isset( $this->settings[ $field ] ) ? $this->settings[ $field ] : 'USD';
        $name     = self::OPTION_NAME . '[' . $field . ']';
        $currencies = array(
            'USD' => __( 'USD - US Dollar', 'ezzy-wallet' ),
            'EUR' => __( 'EUR - Euro', 'ezzy-wallet' ),
            'GBP' => __( 'GBP - British Pound', 'ezzy-wallet' ),
        );

        printf( '<select id="%1$s" name="%2$s">', esc_attr( $field ), esc_attr( $name ) );

        foreach ( $currencies as $code => $label ) {
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr( $code ),
                selected( $value, $code, false ),
                esc_html( $label )
            );
        }

        echo '</select>';

        if ( ! empty( $args['description'] ) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $args['description'] )
            );
        }
    }

    /**
     * Render checkbox field.
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_checkbox_field( $args ) {
        $field   = $args['field'];
        $checked = ! empty( $this->settings[ $field ] );
        $name    = self::OPTION_NAME . '[' . $field . ']';

        printf(
            '<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
            esc_attr( $field ),
            esc_attr( $name ),
            checked( $checked, true, false ),
            esc_html__( 'Enabled', 'ezzy-wallet' )
        );

        if ( ! empty( $args['description'] ) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $args['description'] )
            );
        }
    }

    /**
     * Render color picker field.
     *
     * @param array $args Field arguments.
     * @return void
     */
    /**
     * Render maintenance mode toggle.
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_maintenance_toggle( $args ) {
        $field   = $args['field'];
        $checked = ! empty( $this->settings[ $field ] );
        $name    = self::OPTION_NAME . '[' . $field . ']';

        echo '<div class="ezzy-wallet-maintenance-toggle">';

        printf(
            '<label class="ezzy-wallet-switch">
                <input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />
                <span class="ezzy-wallet-slider"></span>
            </label>',
            esc_attr( $field ),
            esc_attr( $name ),
            checked( $checked, true, false )
        );

        echo '<span class="ezzy-wallet-toggle-label">';
        if ( $checked ) {
            echo '<span class="status-active">' . esc_html__( 'Maintenance Mode Active', 'ezzy-wallet' ) . '</span>';
        } else {
            echo '<span class="status-inactive">' . esc_html__( 'Wallet Operational', 'ezzy-wallet' ) . '</span>';
        }
        echo '</span>';

        echo '</div>';

        if ( ! empty( $args['description'] ) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $args['description'] )
            );
        }

        // Display maintenance message preview.
        echo '<div class="ezzy-wallet-maintenance-preview">';
        echo '<h4>' . esc_html__( 'Maintenance Message Preview:', 'ezzy-wallet' ) . '</h4>';
        echo '<div class="maintenance-message-box">';
        echo '<span class="dashicons dashicons-warning"></span> ';
        echo esc_html__( 'Wallet Under Maintenance. Please check back later.', 'ezzy-wallet' );
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render General Settings page.
     *
     * @return void
     */
    public function render_general_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/general-settings.php';
    }

    /**
     * Render Theme Settings page.
     *
     * @return void
     */
    public function render_theme_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/theme-settings.php';
    }

    /**
     * Render Maintenance Settings page.
     *
     * @return void
     */
    public function render_maintenance_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/maintenance-settings.php';
    }

    /**
     * Render Wallet Users page.
     *
     * @return void
     */
    public function render_wallet_users_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->users_handler->render_page();
    }

    /**
     * Render Transactions page.
     *
     * @return void
     */
    public function render_transactions_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->transactions_handler->render_page();
    }

    /**
     * Render Notifications page.
     *
     * @return void
     */
    public function render_notifications_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $notifications_handler = new Ezzy_Wallet_Notifications();
        $notifications_handler->render_page();
    }

    /**
     * Check if maintenance mode is enabled.
     *
     * @return bool True if maintenance mode is enabled.
     */
    public static function is_maintenance_mode() {
        $settings = get_option( self::OPTION_NAME, array() );
        return ! empty( $settings['maintenance_mode'] );
    }

    /**
     * Check if withdrawals are enabled globally.
     *
     * @return bool True if withdrawals are enabled globally.
     */
    public static function is_withdrawals_enabled_globally() {
        $settings = get_option( self::OPTION_NAME, array() );
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
     * Check if user can withdraw (combines global + individual checks).
     *
     * @param int $user_id User ID to check.
     * @return bool True if user can withdraw.
     */
    public static function can_user_withdraw( $user_id ) {
        // First check global setting
        if ( ! self::is_withdrawals_enabled_globally() ) {
            return false;
        }

        // Then check individual user setting
        if ( self::is_user_withdrawals_disabled( $user_id ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get maintenance message.
     *
     * @return string Maintenance message.
     */
    public static function get_maintenance_message() {
        return __( 'Wallet Under Maintenance. Please check back later.', 'ezzy-wallet' );
    }

    /**
     * Check if current user can bypass maintenance mode.
     *
     * @return bool True if user can bypass.
     */
    public static function can_bypass_maintenance() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Add license timer to admin bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
     * @return void
     */
    public function add_license_timer_to_admin_bar( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $time_remaining = Ezzy_Wallet_License::get_time_remaining();

        // Check if master/unlimited.
        if ( ! empty( $time_remaining['unlimited'] ) ) {
            $wp_admin_bar->add_node(
                array(
                    'id'    => 'ezzy-wallet-license',
                    'title' => '<span class="ab-icon dashicons dashicons-money-alt" style="margin-top:2px;"></span><span class="ezzy-license-timer" style="color:#f59e0b;font-weight:600;">∞ Unlimited</span>',
                    'href'  => admin_url( 'admin.php?page=ezzy-wallet-licenses' ),
                    'meta'  => array(
                        'title' => __( 'eZZY Wallet - Master License', 'ezzy-wallet' ),
                    ),
                )
            );
            return;
        }

        // Check if expired.
        if ( ! empty( $time_remaining['expired'] ) ) {
            $wp_admin_bar->add_node(
                array(
                    'id'    => 'ezzy-wallet-license',
                    'title' => '<span class="ab-icon dashicons dashicons-money-alt" style="margin-top:2px;"></span><span class="ezzy-license-timer" style="color:#dc2626;font-weight:600;">License Expired</span>',
                    'href'  => admin_url( 'admin.php?page=ezzy-wallet' ),
                    'meta'  => array(
                        'title' => __( 'eZZY Wallet - License Expired', 'ezzy-wallet' ),
                    ),
                )
            );
            return;
        }

        // Show countdown.
        $license_data = Ezzy_Wallet_License::get_license_data();
        $expires_at = isset( $license_data['expires_at'] ) ? $license_data['expires_at'] : 0;
        
        $timer_html = sprintf(
            '<span class="ab-icon dashicons dashicons-money-alt" style="margin-top:2px;"></span><span class="ezzy-license-timer" id="ezzy-admin-bar-timer" data-expires="%d">%dd %dh %dm %ds</span>',
            $expires_at,
            $time_remaining['days'],
            $time_remaining['hours'],
            $time_remaining['minutes'],
            $time_remaining['seconds']
        );

        $wp_admin_bar->add_node(
            array(
                'id'    => 'ezzy-wallet-license',
                'title' => $timer_html,
                'href'  => admin_url( 'admin.php?page=ezzy-wallet' ),
                'meta'  => array(
                    'title' => __( 'eZZY Wallet License Time Remaining', 'ezzy-wallet' ),
                ),
            )
        );
    }

    /**
     * Render license timer JavaScript.
     *
     * @return void
     */
    public function render_license_timer_script() {
        $time_remaining = Ezzy_Wallet_License::get_time_remaining();

        // Don't render for master or expired.
        if ( ! empty( $time_remaining['unlimited'] ) || ! empty( $time_remaining['expired'] ) ) {
            return;
        }

        $license_data = Ezzy_Wallet_License::get_license_data();
        if ( empty( $license_data['expires_at'] ) ) {
            return;
        }
        ?>
        <style>
            #wp-admin-bar-ezzy-wallet-license .ab-item {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }
            .ezzy-license-timer {
                font-family: 'Courier New', monospace !important;
                font-size: 12px !important;
                color: #22c55e !important;
            }
            #wp-admin-bar-ezzy-wallet-license .dashicons {
                font-size: 18px !important;
            }
        </style>
        <script>
        (function() {
            var timerEl = document.getElementById('ezzy-admin-bar-timer');
            if (!timerEl) return;

            var expiresAt = parseInt(timerEl.getAttribute('data-expires'), 10) * 1000;

            function updateTimer() {
                var now = Date.now();
                var remaining = expiresAt - now;

                if (remaining <= 0) {
                    timerEl.innerHTML = 'Expired';
                    timerEl.style.color = '#dc2626';
                    return;
                }

                var days = Math.floor(remaining / 86400000);
                var hours = Math.floor((remaining % 86400000) / 3600000);
                var minutes = Math.floor((remaining % 3600000) / 60000);
                var seconds = Math.floor((remaining % 60000) / 1000);

                timerEl.innerHTML = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';

                // Change color based on time remaining.
                if (days < 7) {
                    timerEl.style.color = '#dc2626'; // Red - less than 7 days.
                } else if (days < 30) {
                    timerEl.style.color = '#f59e0b'; // Yellow - less than 30 days.
                } else {
                    timerEl.style.color = '#22c55e'; // Green - more than 30 days.
                }
            }

            // Update every second.
            setInterval(updateTimer, 1000);
            updateTimer();
        })();
        </script>
        <?php
    }
}
