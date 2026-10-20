<?php
/**
 * Maintenance Settings admin page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_maintenance = Ezzy_Wallet_Admin::is_maintenance_mode();
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
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-theme' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e( 'Theme Settings', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-maintenance' ) ); ?>" class="nav-tab nav-tab-active">
            <span class="dashicons dashicons-hammer"></span>
            <?php esc_html_e( 'Maintenance', 'ezzy-wallet' ); ?>
        </a>
    </nav>

    <div class="ezzy-wallet-settings-content">
        <?php settings_errors(); ?>

        <div class="ezzy-wallet-maintenance-status <?php echo $is_maintenance ? 'status-maintenance' : 'status-operational'; ?>">
            <div class="status-indicator">
                <?php if ( $is_maintenance ) : ?>
                    <span class="dashicons dashicons-warning"></span>
                    <span class="status-text"><?php esc_html_e( 'Wallet is currently in Maintenance Mode', 'ezzy-wallet' ); ?></span>
                <?php else : ?>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="status-text"><?php esc_html_e( 'Wallet is fully operational', 'ezzy-wallet' ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form method="post" action="options.php" class="ezzy-wallet-settings-form ezzy-wallet-maintenance-form">
            <?php
            settings_fields( Ezzy_Wallet_Admin::SETTINGS_GROUP );
            do_settings_sections( 'ezzy-wallet-maintenance' );
            submit_button( __( 'Save Maintenance Settings', 'ezzy-wallet' ) );
            ?>
        </form>

        <div class="ezzy-wallet-maintenance-info">
            <h3><?php esc_html_e( 'What happens during Maintenance Mode?', 'ezzy-wallet' ); ?></h3>
            <ul>
                <li>
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e( 'Non-admin users cannot access wallet pages', 'ezzy-wallet' ); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e( 'Users see a maintenance message instead of wallet content', 'ezzy-wallet' ); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e( 'Administrators can still access all wallet functions', 'ezzy-wallet' ); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e( 'WordPress admin area remains fully accessible', 'ezzy-wallet' ); ?>
                </li>
            </ul>
        </div>
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
