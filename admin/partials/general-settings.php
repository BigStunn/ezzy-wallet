<?php
/**
 * General Settings admin page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle license deactivation.
if ( isset( $_POST['ezzy_deactivate_license'] ) && isset( $_POST['ezzy_deactivate_nonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_deactivate_nonce'] ) ), 'ezzy_deactivate_license' ) ) {
        Ezzy_Wallet_License::deactivate();
        wp_safe_redirect( admin_url( 'admin.php?page=ezzy-wallet' ) );
        exit;
    }
}

$license_data = Ezzy_Wallet_License::get_license_data();
$is_master = Ezzy_Wallet_License::is_master();
$time_remaining = Ezzy_Wallet_License::get_time_remaining();
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
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet' ) ); ?>" class="nav-tab nav-tab-active">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'General Settings', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-theme' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e( 'Theme Settings', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-maintenance' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-hammer"></span>
            <?php esc_html_e( 'Maintenance', 'ezzy-wallet' ); ?>
        </a>
    </nav>

    <!-- License Information Box -->
    <div class="ezzy-license-info-box" style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin:20px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div class="ezzy-license-info-left">
            <h3 style="margin:0 0 8px 0;font-size:14px;color:#64748b;font-weight:500;">
                <span class="dashicons dashicons-admin-network" style="color:#2563eb;margin-right:6px;"></span>
                <?php esc_html_e( 'License Status', 'ezzy-wallet' ); ?>
            </h3>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <?php if ( $is_master ) : ?>
                    <span style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;">
                        <?php esc_html_e( 'Master License (Unlimited)', 'ezzy-wallet' ); ?>
                    </span>
                <?php else : ?>
                    <span style="color:#1e293b;font-size:14px;">
                        <strong><?php esc_html_e( 'Code:', 'ezzy-wallet' ); ?></strong> 
                        <code style="background:#f1f5f9;padding:4px 8px;border-radius:4px;font-family:monospace;"><?php echo esc_html( $license_data['code'] ?? '—' ); ?></code>
                    </span>
                    <?php if ( ! empty( $time_remaining['days'] ) || ! empty( $time_remaining['hours'] ) ) : ?>
                        <span style="color:#64748b;font-size:13px;">
                            <strong><?php esc_html_e( 'Expires:', 'ezzy-wallet' ); ?></strong> 
                            <?php 
                            if ( ! empty( $license_data['expires_at'] ) ) {
                                echo esc_html( date_i18n( 'F j, Y', $license_data['expires_at'] ) );
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="ezzy-license-info-right">
            <form method="post" style="margin:0;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to deactivate this license? You will need to enter an activation code to use the plugin again.', 'ezzy-wallet' ); ?>');">
                <?php wp_nonce_field( 'ezzy_deactivate_license', 'ezzy_deactivate_nonce' ); ?>
                <button type="submit" name="ezzy_deactivate_license" class="button" style="color:#dc2626;border-color:#fecaca;">
                    <span class="dashicons dashicons-unlock" style="margin-top:3px;"></span>
                    <?php esc_html_e( 'Deactivate License', 'ezzy-wallet' ); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="ezzy-wallet-settings-content">
        <?php settings_errors(); ?>

        <form method="post" action="options.php" class="ezzy-wallet-settings-form">
            <?php
            settings_fields( Ezzy_Wallet_Admin::SETTINGS_GROUP );
            do_settings_sections( 'ezzy-wallet-general' );
            submit_button( __( 'Save Settings', 'ezzy-wallet' ) );
            ?>
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
