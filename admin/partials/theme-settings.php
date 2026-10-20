<?php
/**
 * Theme Settings admin page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'ezzy_wallet_settings', array() );
$current_skin = isset( $settings['dashboard_skin_theme'] ) ? $settings['dashboard_skin_theme'] : 'classic_blue';
$themes = Ezzy_Wallet_Admin::get_skin_themes();
$current_theme = isset( $themes[ $current_skin ] ) ? $themes[ $current_skin ] : $themes['classic_blue'];
?>

<div class="wrap ezzy-wallet-admin-wrap">
    <h1 class="ezzy-wallet-admin-title">
        <span class="dashicons dashicons-money-alt"></span>
        <?php echo esc_html( get_admin_page_title() ); ?>
        <?php echo Ezzy_Wallet_License::render_countdown_timer(); ?>
    </h1>

    <div class="ezzy-wallet-admin-header">
        <p class="ezzy-wallet-version">
            <?php
            printf(
                /* translators: %s: Plugin version number */
                esc_html__( 'Version %s', 'ezzy-wallet' ),
                esc_html( EZZY_WALLET_VERSION )
            );
            ?>
        </p>
    </div>

    <nav class="nav-tab-wrapper ezzy-wallet-tabs">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'General Settings', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-theme' ) ); ?>" class="nav-tab nav-tab-active">
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e( 'Theme Settings', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-maintenance' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-hammer"></span>
            <?php esc_html_e( 'Maintenance', 'ezzy-wallet' ); ?>
        </a>
    </nav>

    <div class="ezzy-wallet-settings-content">
        <?php settings_errors(); ?>

        <div class="ezzy-wallet-theme-info">
            <div class="notice notice-info inline">
                <p>
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e( 'Select a skin theme to instantly change the entire dashboard appearance. The theme colors will be applied to all dashboard elements.', 'ezzy-wallet' ); ?>
                </p>
            </div>
        </div>

        <form method="post" action="options.php" class="ezzy-wallet-settings-form ezzy-wallet-theme-form">
            <?php
            settings_fields( Ezzy_Wallet_Admin::SETTINGS_GROUP );
            do_settings_sections( 'ezzy-wallet-theme' );
            ?>

            <div class="ezzy-wallet-theme-preview">
                <h3><?php esc_html_e( 'Live Preview', 'ezzy-wallet' ); ?></h3>
                <div class="preview-card" id="ezzy-wallet-preview-card" style="border: 2px solid <?php echo esc_attr( $current_theme['primary'] ); ?>;">
                    <div class="preview-header" style="background: linear-gradient(135deg, <?php echo esc_attr( $current_theme['primary'] ); ?> 0%, <?php echo esc_attr( $current_theme['secondary'] ); ?> 100%); color: #fff;">
                        <span><?php esc_html_e( 'Wallet Dashboard Preview', 'ezzy-wallet' ); ?></span>
                    </div>
                    <div class="preview-body" style="background: <?php echo esc_attr( $current_theme['card_bg'] ); ?>;">
                        <div class="preview-form" style="background: <?php echo esc_attr( $current_theme['form_bg'] ); ?>; padding: 15px; border-radius: 4px;">
                            <input type="text" placeholder="<?php esc_attr_e( 'Sample Input Field', 'ezzy-wallet' ); ?>" readonly style="border: 1px solid <?php echo esc_attr( $current_theme['input_border'] ); ?>; background: <?php echo esc_attr( $current_theme['card_bg'] ); ?>; color: <?php echo esc_attr( $current_theme['text'] ); ?>;" />
                            <button type="button" class="preview-button" style="background: <?php echo esc_attr( $current_theme['primary'] ); ?>; color: #fff;"><?php esc_html_e( 'Sample Button', 'ezzy-wallet' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <?php submit_button( __( 'Save Theme Settings', 'ezzy-wallet' ) ); ?>
        </form>
    </div>

    <div class="ezzy-wallet-admin-footer">
        <p>
            <?php
            printf(
                /* translators: %s: Author name */
                esc_html__( 'eZZY Wallet by %s', 'ezzy-wallet' ),
                '<strong>Big Stunnz</strong>'
            );
            ?>
        </p>
    </div>
</div>
