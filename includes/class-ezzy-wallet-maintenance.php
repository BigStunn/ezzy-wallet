<?php
/**
 * Maintenance mode handler for eZZY Wallet.
 *
 * Handles maintenance mode display on frontend wallet pages.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Maintenance
 *
 * Handles maintenance mode functionality for frontend.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Maintenance {

    /**
     * Constructor.
     *
     * Initialize maintenance hooks.
     */
    public function __construct() {
        // Placeholder hook for future wallet page detection.
        add_action( 'template_redirect', array( $this, 'maybe_show_maintenance' ) );
    }

    /**
     * Check and display maintenance mode if enabled.
     *
     * This is a placeholder function that will be fully implemented
     * when wallet frontend pages are built in future phases.
     *
     * @return void
     */
    public function maybe_show_maintenance() {
        // Skip if not in maintenance mode.
        if ( ! $this->is_maintenance_mode() ) {
            return;
        }

        // Skip if user can bypass maintenance.
        if ( $this->can_bypass_maintenance() ) {
            return;
        }

        // Skip if not a wallet page (placeholder - will be implemented later).
        if ( ! $this->is_wallet_page() ) {
            return;
        }

        // Display maintenance message.
        $this->display_maintenance_message();
    }

    /**
     * Check if maintenance mode is enabled.
     *
     * @return bool True if maintenance mode is enabled.
     */
    public function is_maintenance_mode() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        return ! empty( $settings['maintenance_mode'] );
    }

    /**
     * Check if current user can bypass maintenance mode.
     *
     * @return bool True if user can bypass.
     */
    public function can_bypass_maintenance() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if current page is a wallet page.
     *
     * Placeholder function - will be implemented when frontend pages are built.
     *
     * @return bool True if current page is a wallet page.
     */
    public function is_wallet_page() {
        /**
         * Filter to determine if current page is a wallet page.
         *
         * @since 1.0.0
         * @param bool $is_wallet_page Whether current page is a wallet page.
         */
        return apply_filters( 'ezzy_wallet_is_wallet_page', false );
    }

    /**
     * Display maintenance message.
     *
     * @return void
     */
    public function display_maintenance_message() {
        // Get the maintenance message.
        $message = $this->get_maintenance_message();

        /**
         * Filter the maintenance message HTML.
         *
         * @since 1.0.0
         * @param string $html The maintenance message HTML.
         * @param string $message The maintenance message text.
         */
        $html = apply_filters(
            'ezzy_wallet_maintenance_html',
            $this->get_maintenance_html( $message ),
            $message
        );

        // Output the maintenance message.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $html;

        // Stop further execution for this request.
        exit;
    }

    /**
     * Get the maintenance message text.
     *
     * @return string Maintenance message.
     */
    public function get_maintenance_message() {
        /**
         * Filter the maintenance message text.
         *
         * @since 1.0.0
         * @param string $message The maintenance message.
         */
        return apply_filters(
            'ezzy_wallet_maintenance_message',
            __( 'Wallet Under Maintenance. Please check back later.', 'ezzy-wallet' )
        );
    }

    /**
     * Get maintenance page HTML.
     *
     * @param string $message Maintenance message.
     * @return string HTML content.
     */
    private function get_maintenance_html( $message ) {
        $site_name = get_bloginfo( 'name' );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( $site_name ); ?> - <?php esc_html_e( 'Maintenance', 'ezzy-wallet' ); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .maintenance-container {
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 50px 40px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
                }
                .maintenance-icon {
                    width: 80px;
                    height: 80px;
                    background: #fef2f2;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 25px;
                }
                .maintenance-icon svg {
                    width: 40px;
                    height: 40px;
                    color: #dc2626;
                }
                h1 {
                    font-size: 24px;
                    color: #1f2937;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                p {
                    font-size: 16px;
                    color: #6b7280;
                    line-height: 1.6;
                }
                .site-name {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #e5e7eb;
                    font-size: 14px;
                    color: #9ca3af;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <div class="maintenance-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1><?php esc_html_e( 'Under Maintenance', 'ezzy-wallet' ); ?></h1>
                <p><?php echo esc_html( $message ); ?></p>
                <div class="site-name"><?php echo esc_html( $site_name ); ?></div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Static method to check maintenance status.
     *
     * @return bool True if maintenance mode is enabled.
     */
    public static function is_enabled() {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        return ! empty( $settings['maintenance_mode'] );
    }
}
