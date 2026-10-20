<?php
/**
 * Wallet Users table admin page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap ezzy-wallet-admin-wrap">
    <h1 class="ezzy-wallet-admin-title">
        <span class="dashicons dashicons-money-alt"></span>
        <?php esc_html_e( 'Wallet Users', 'ezzy-wallet' ); ?>
        <?php echo Ezzy_Wallet_License::render_countdown_timer(); ?>
    </h1>

    <?php
    // Show deletion messages
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $deleted = isset( $_GET['deleted'] ) ? sanitize_text_field( wp_unslash( $_GET['deleted'] ) ) : '';
    if ( 'success' === $deleted ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Wallet user and all associated data have been deleted successfully.', 'ezzy-wallet' ); ?></p>
        </div>
    <?php elseif ( 'self_error' === $deleted ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'You cannot delete your own account.', 'ezzy-wallet' ); ?></p>
        </div>
    <?php endif; ?>

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
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-maintenance' ) ); ?>" class="nav-tab">
            <span class="dashicons dashicons-hammer"></span>
            <?php esc_html_e( 'Maintenance', 'ezzy-wallet' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-users' ) ); ?>" class="nav-tab nav-tab-active">
            <span class="dashicons dashicons-groups"></span>
            <?php esc_html_e( 'Wallet Users', 'ezzy-wallet' ); ?>
        </a>
    </nav>

    <div class="ezzy-wallet-settings-content ezzy-wallet-users-content">
        <div class="ezzy-wallet-users-summary">
            <?php
            global $wpdb;
            $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();

            // Get summary stats.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $stats = $wpdb->get_row(
                "SELECT 
                    COUNT(*) as total_accounts,
                    SUM(balance) as total_balance,
                    SUM(total_deposit) as total_deposits,
                    SUM(total_withdrawal) as total_withdrawals
                 FROM {$accounts_table}"
            );

            $settings = get_option( 'ezzy_wallet_settings', array() );
            $currency = isset( $settings['default_currency'] ) ? $settings['default_currency'] : 'USD';
            $symbols  = array(
                'USD' => '$',
                'EUR' => '€',
                'GBP' => '£',
            );
            $symbol   = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '$';
            ?>
            <div class="summary-cards">
                <div class="summary-card">
                    <span class="summary-icon dashicons dashicons-groups"></span>
                    <div class="summary-data">
                        <span class="summary-value"><?php echo esc_html( number_format( $stats->total_accounts ?? 0 ) ); ?></span>
                        <span class="summary-label"><?php esc_html_e( 'Total Accounts', 'ezzy-wallet' ); ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <span class="summary-icon dashicons dashicons-money-alt"></span>
                    <div class="summary-data">
                        <span class="summary-value"><?php echo esc_html( $symbol . number_format( $stats->total_balance ?? 0, 2 ) ); ?></span>
                        <span class="summary-label"><?php esc_html_e( 'Total Balance', 'ezzy-wallet' ); ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <span class="summary-icon dashicons dashicons-download"></span>
                    <div class="summary-data">
                        <span class="summary-value"><?php echo esc_html( $symbol . number_format( $stats->total_deposits ?? 0, 2 ) ); ?></span>
                        <span class="summary-label"><?php esc_html_e( 'Total Deposits', 'ezzy-wallet' ); ?></span>
                    </div>
                </div>
                <div class="summary-card">
                    <span class="summary-icon dashicons dashicons-upload"></span>
                    <div class="summary-data">
                        <span class="summary-value"><?php echo esc_html( $symbol . number_format( $stats->total_withdrawals ?? 0, 2 ) ); ?></span>
                        <span class="summary-label"><?php esc_html_e( 'Total Withdrawals', 'ezzy-wallet' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" action="">
            <input type="hidden" name="page" value="ezzy-wallet-users" />
            <?php $this->list_table->display(); ?>
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

<style>
/* Delete button styling */
.ezzy-delete-user-btn {
    color: #d63638 !important;
    text-decoration: none !important;
    margin-left: 8px !important;
    cursor: pointer !important;
    font-weight: 400 !important;
}
.ezzy-delete-user-btn:hover {
    color: #a00 !important;
    text-decoration: underline !important;
}

/* Delete confirmation modal */
.ezzy-delete-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ezzy-delete-modal {
    background: #fff;
    border-radius: 8px;
    padding: 24px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    text-align: center;
}
.ezzy-delete-modal-icon {
    width: 50px;
    height: 50px;
    background: #fef2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.ezzy-delete-modal-icon .dashicons {
    font-size: 24px;
    color: #d63638;
}
.ezzy-delete-modal h3 {
    font-size: 18px;
    margin: 0 0 8px;
    color: #1d2327;
}
.ezzy-delete-modal p {
    color: #646970;
    margin: 0 0 20px;
    font-size: 14px;
}
.ezzy-delete-modal-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}
.ezzy-delete-modal-buttons button {
    padding: 8px 20px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    border: none;
}
.ezzy-delete-modal-cancel {
    background: #f0f0f1;
    color: #1d2327;
}
.ezzy-delete-modal-cancel:hover {
    background: #e0e0e0;
}
.ezzy-delete-modal-confirm {
    background: #d63638;
    color: #fff;
}
.ezzy-delete-modal-confirm:hover {
    background: #b32d2e;
}
</style>

<script>
function ezzyConfirmDeleteUser(event, element) {
    event.preventDefault();
    
    var username = element.getAttribute('data-username');
    var deleteUrl = element.getAttribute('href');
    
    // Create modal overlay
    var overlay = document.createElement('div');
    overlay.className = 'ezzy-delete-modal-overlay';
    overlay.innerHTML = 
        '<div class="ezzy-delete-modal">' +
            '<div class="ezzy-delete-modal-icon">' +
                '<span class="dashicons dashicons-warning"></span>' +
            '</div>' +
            '<h3><?php echo esc_js( __( 'Delete Wallet User?', 'ezzy-wallet' ) ); ?></h3>' +
            '<p><?php echo esc_js( __( 'Are you sure you want to delete', 'ezzy-wallet' ) ); ?> <strong>' + username + '</strong>? <?php echo esc_js( __( 'This will permanently remove all their wallet data, transactions, and notifications. This action cannot be undone.', 'ezzy-wallet' ) ); ?></p>' +
            '<div class="ezzy-delete-modal-buttons">' +
                '<button type="button" class="ezzy-delete-modal-cancel"><?php echo esc_js( __( 'Cancel', 'ezzy-wallet' ) ); ?></button>' +
                '<button type="button" class="ezzy-delete-modal-confirm"><?php echo esc_js( __( 'Delete User', 'ezzy-wallet' ) ); ?></button>' +
            '</div>' +
        '</div>';
    
    document.body.appendChild(overlay);
    
    // Handle cancel
    overlay.querySelector('.ezzy-delete-modal-cancel').addEventListener('click', function() {
        document.body.removeChild(overlay);
    });
    
    // Handle click outside modal
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            document.body.removeChild(overlay);
        }
    });
    
    // Handle confirm
    overlay.querySelector('.ezzy-delete-modal-confirm').addEventListener('click', function() {
        window.location.href = deleteUrl;
    });
    
    return false;
}
</script>
