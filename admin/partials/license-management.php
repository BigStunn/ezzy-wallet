<?php
/**
 * License Management Page for eZZY Wallet.
 * Only accessible with master code.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only master can access this page.
if ( ! Ezzy_Wallet_License::is_master() ) {
    wp_die( esc_html__( 'Access denied. Master license required.', 'ezzy-wallet' ) );
}

$message = '';
$message_type = '';

// Handle generate code.
if ( isset( $_POST['ezzy_generate_code'] ) && isset( $_POST['ezzy_license_manage_nonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_license_manage_nonce'] ) ), 'ezzy_license_management' ) ) {
        $note = isset( $_POST['code_note'] ) ? sanitize_text_field( wp_unslash( $_POST['code_note'] ) ) : '';
        $result = Ezzy_Wallet_License::add_code( '', $note );

        if ( $result['success'] ) {
            $message = sprintf(
                /* translators: %s: The generated code */
                __( 'New activation code generated: %s', 'ezzy-wallet' ),
                '<strong>' . esc_html( $result['code'] ) . '</strong>'
            );
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'error';
        }
    }
}

// Handle delete code.
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_code' && isset( $_GET['code'] ) ) {
    if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_license_code' ) ) {
        $code_to_delete = sanitize_text_field( wp_unslash( $_GET['code'] ) );
        Ezzy_Wallet_License::delete_code( $code_to_delete );
        $message = __( 'Activation code deleted successfully.', 'ezzy-wallet' );
        $message_type = 'success';
    }
}

// Get all licenses.
$licenses = Ezzy_Wallet_License::get_all_licenses_info();
?>

<div class="wrap ezzy-wallet-admin-wrap">
    <h1 class="ezzy-wallet-admin-title">
        <span class="dashicons dashicons-admin-network"></span>
        <?php esc_html_e( 'License Management', 'ezzy-wallet' ); ?>
        <span class="ezzy-master-badge"><?php esc_html_e( 'Master Access', 'ezzy-wallet' ); ?></span>
    </h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
    <?php endif; ?>

    <div class="ezzy-license-grid">
        <!-- Generate New Code -->
        <div class="ezzy-license-card ezzy-generate-card">
            <h2>
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e( 'Generate New Code', 'ezzy-wallet' ); ?>
            </h2>
            <p><?php esc_html_e( 'Create a new activation code for a customer. Each code is valid for 1 year from first activation and can only be used on one website.', 'ezzy-wallet' ); ?></p>

            <form method="post">
                <?php wp_nonce_field( 'ezzy_license_management', 'ezzy_license_manage_nonce' ); ?>

                <div class="ezzy-form-field">
                    <label for="code_note"><?php esc_html_e( 'Customer Note (optional)', 'ezzy-wallet' ); ?></label>
                    <input type="text" id="code_note" name="code_note" placeholder="<?php esc_attr_e( 'e.g., John Doe - Invoice #123', 'ezzy-wallet' ); ?>" />
                </div>

                <button type="submit" name="ezzy_generate_code" class="button button-primary button-large">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php esc_html_e( 'Generate Activation Code', 'ezzy-wallet' ); ?>
                </button>
            </form>
        </div>

        <!-- License Stats -->
        <div class="ezzy-license-card ezzy-stats-card">
            <h2>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e( 'License Statistics', 'ezzy-wallet' ); ?>
            </h2>

            <?php
            $total = count( $licenses );
            $active = 0;
            $unused = 0;
            $expired = 0;

            foreach ( $licenses as $lic ) {
                if ( $lic['status'] === 'active' ) {
                    $active++;
                } elseif ( $lic['status'] === 'unused' ) {
                    $unused++;
                } else {
                    $expired++;
                }
            }
            ?>

            <div class="ezzy-stats-grid">
                <div class="ezzy-stat-item">
                    <span class="ezzy-stat-value"><?php echo esc_html( $total ); ?></span>
                    <span class="ezzy-stat-label"><?php esc_html_e( 'Total Codes', 'ezzy-wallet' ); ?></span>
                </div>
                <div class="ezzy-stat-item ezzy-stat-active">
                    <span class="ezzy-stat-value"><?php echo esc_html( $active ); ?></span>
                    <span class="ezzy-stat-label"><?php esc_html_e( 'Active', 'ezzy-wallet' ); ?></span>
                </div>
                <div class="ezzy-stat-item ezzy-stat-unused">
                    <span class="ezzy-stat-value"><?php echo esc_html( $unused ); ?></span>
                    <span class="ezzy-stat-label"><?php esc_html_e( 'Unused', 'ezzy-wallet' ); ?></span>
                </div>
                <div class="ezzy-stat-item ezzy-stat-expired">
                    <span class="ezzy-stat-value"><?php echo esc_html( $expired ); ?></span>
                    <span class="ezzy-stat-label"><?php esc_html_e( 'Expired', 'ezzy-wallet' ); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- All Licenses Table -->
    <div class="ezzy-license-card ezzy-licenses-table-card">
        <h2>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e( 'All Activation Codes', 'ezzy-wallet' ); ?>
        </h2>

        <?php if ( empty( $licenses ) ) : ?>
            <p class="ezzy-no-licenses"><?php esc_html_e( 'No activation codes created yet. Generate your first code above.', 'ezzy-wallet' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped ezzy-licenses-table">
                <thead>
                    <tr>
                        <th class="column-code"><?php esc_html_e( 'Activation Code', 'ezzy-wallet' ); ?></th>
                        <th class="column-note"><?php esc_html_e( 'Note', 'ezzy-wallet' ); ?></th>
                        <th class="column-status"><?php esc_html_e( 'Status', 'ezzy-wallet' ); ?></th>
                        <th class="column-domain"><?php esc_html_e( 'Domain', 'ezzy-wallet' ); ?></th>
                        <th class="column-created"><?php esc_html_e( 'Created', 'ezzy-wallet' ); ?></th>
                        <th class="column-expires"><?php esc_html_e( 'Expires', 'ezzy-wallet' ); ?></th>
                        <th class="column-remaining"><?php esc_html_e( 'Days Left', 'ezzy-wallet' ); ?></th>
                        <th class="column-actions"><?php esc_html_e( 'Actions', 'ezzy-wallet' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $licenses as $license ) : ?>
                        <tr>
                            <td class="column-code">
                                <code class="ezzy-code-display"><?php echo esc_html( $license['code'] ); ?></code>
                                <button type="button" class="ezzy-copy-btn" data-code="<?php echo esc_attr( $license['code'] ); ?>" title="<?php esc_attr_e( 'Copy code', 'ezzy-wallet' ); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </td>
                            <td class="column-note">
                                <?php echo esc_html( $license['note'] ?: '—' ); ?>
                            </td>
                            <td class="column-status">
                                <?php
                                $status_class = 'ezzy-status-' . $license['status'];
                                $status_labels = array(
                                    'active'  => __( 'Active', 'ezzy-wallet' ),
                                    'unused'  => __( 'Unused', 'ezzy-wallet' ),
                                    'expired' => __( 'Expired', 'ezzy-wallet' ),
                                );
                                ?>
                                <span class="ezzy-status-badge <?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( $status_labels[ $license['status'] ] ); ?>
                                </span>
                            </td>
                            <td class="column-domain">
                                <?php echo esc_html( $license['domain'] ?: '—' ); ?>
                            </td>
                            <td class="column-created">
                                <?php
                                if ( $license['created_at'] ) {
                                    echo esc_html( date_i18n( 'M j, Y', $license['created_at'] ) );
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="column-expires">
                                <?php
                                if ( $license['expires_at'] ) {
                                    echo esc_html( date_i18n( 'M j, Y', $license['expires_at'] ) );
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="column-remaining">
                                <?php
                                if ( $license['status'] === 'active' ) {
                                    echo '<strong>' . esc_html( $license['days_left'] ) . '</strong> ' . esc_html__( 'days', 'ezzy-wallet' );
                                } elseif ( $license['status'] === 'expired' ) {
                                    echo '<span class="ezzy-expired-text">' . esc_html__( 'Expired', 'ezzy-wallet' ) . '</span>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="column-actions">
                                <?php
                                $delete_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'page'   => 'ezzy-wallet-licenses',
                                            'action' => 'delete_code',
                                            'code'   => $license['code'],
                                        ),
                                        admin_url( 'admin.php' )
                                    ),
                                    'delete_license_code'
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" class="ezzy-delete-code" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this activation code?', 'ezzy-wallet' ); ?>');">
                                    <?php esc_html_e( 'Delete', 'ezzy-wallet' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.ezzy-master-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    margin-left: 12px;
    vertical-align: middle;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ezzy-license-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.ezzy-license-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
}

.ezzy-license-card h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 16px 0;
    padding: 0;
}

.ezzy-license-card h2 .dashicons {
    color: #2563eb;
}

.ezzy-license-card p {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.ezzy-form-field {
    margin-bottom: 16px;
}

.ezzy-form-field label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    margin-bottom: 6px;
}

.ezzy-form-field input[type="text"] {
    width: 100%;
    padding: 10px 14px;
    font-size: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.ezzy-form-field input[type="text"]:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.ezzy-license-card .button-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ezzy-license-card .button-primary .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.ezzy-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.ezzy-stat-item {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
}

.ezzy-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
}

.ezzy-stat-label {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.ezzy-stat-active .ezzy-stat-value { color: #16a34a; }
.ezzy-stat-unused .ezzy-stat-value { color: #2563eb; }
.ezzy-stat-expired .ezzy-stat-value { color: #dc2626; }

.ezzy-licenses-table-card {
    margin-top: 24px;
}

.ezzy-licenses-table {
    margin-top: 16px;
}

.ezzy-code-display {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 4px;
}

.ezzy-copy-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    margin-left: 8px;
    color: #64748b;
    vertical-align: middle;
}

.ezzy-copy-btn:hover {
    color: #2563eb;
}

.ezzy-copy-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.ezzy-status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.ezzy-status-active {
    background: #dcfce7;
    color: #16a34a;
}

.ezzy-status-unused {
    background: #dbeafe;
    color: #2563eb;
}

.ezzy-status-expired {
    background: #fee2e2;
    color: #dc2626;
}

.ezzy-expired-text {
    color: #dc2626;
}

.ezzy-delete-code {
    color: #dc2626;
    text-decoration: none;
}

.ezzy-delete-code:hover {
    text-decoration: underline;
}

.ezzy-no-licenses {
    text-align: center;
    color: #94a3b8;
    padding: 40px 20px;
}

.column-code { width: 180px; }
.column-status { width: 90px; }
.column-created, .column-expires { width: 100px; }
.column-remaining { width: 90px; }
.column-actions { width: 80px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy code functionality
    document.querySelectorAll('.ezzy-copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(function() {
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                setTimeout(function() {
                    btn.innerHTML = '<span class="dashicons dashicons-clipboard"></span>';
                }, 2000);
            });
        });
    });
});
</script>
