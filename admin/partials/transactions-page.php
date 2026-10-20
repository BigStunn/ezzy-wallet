<?php
/**
 * Transactions admin page template.
 *
 * @package EzzyWallet
 * @since 1.5.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$data = $this->get_transactions( $current_page );
$counts = $this->get_tab_counts();
$transactions = $data['transactions'];
$total_pages = $data['total_pages'];

$tabs = array(
    'all'         => __( 'All', 'ezzy-wallet' ),
    'pending'     => __( 'Pending', 'ezzy-wallet' ),
    'deposits'    => __( 'Deposits', 'ezzy-wallet' ),
    'withdrawals' => __( 'Withdrawals', 'ezzy-wallet' ),
    'transfers'   => __( 'Transfers', 'ezzy-wallet' ),
);
?>

<div class="wrap ezzy-wallet-admin-wrap">
    <h1 class="ezzy-wallet-admin-title">
        <span class="dashicons dashicons-list-view"></span>
        <?php esc_html_e( 'Transaction Management', 'ezzy-wallet' ); ?>
    </h1>

    <!-- Tab Navigation -->
    <ul class="subsubsub ezzy-transactions-tabs">
        <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
            <li>
                <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, admin_url( 'admin.php?page=ezzy-wallet-transactions' ) ) ); ?>" 
                   class="<?php echo $this->current_tab === $tab_key ? 'current' : ''; ?>">
                    <?php echo esc_html( $tab_label ); ?>
                    <span class="count">(<?php echo esc_html( $counts[ $tab_key ] ); ?>)</span>
                </a>
                <?php if ( $tab_key !== 'transfers' ) : ?> | <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="clear"></div>

    <!-- Bulk Actions Bar -->
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <select id="ezzy-bulk-action">
                <option value=""><?php esc_html_e( 'Bulk Actions', 'ezzy-wallet' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete Selected', 'ezzy-wallet' ); ?></option>
            </select>
            <button type="button" class="button" id="ezzy-apply-bulk-action">
                <?php esc_html_e( 'Apply', 'ezzy-wallet' ); ?>
            </button>
        </div>
        <div class="alignright">
            <button type="button" class="button button-link-delete" id="ezzy-delete-all-transactions">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e( 'Delete All', 'ezzy-wallet' ); ?>
            </button>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Transactions Table -->
    <table class="wp-list-table widefat fixed striped ezzy-transactions-table">
        <thead>
            <tr>
                <th class="check-column" style="width: 30px;">
                    <input type="checkbox" id="ezzy-select-all" />
                </th>
                <th class="column-id" style="width: 60px;"><?php esc_html_e( 'ID', 'ezzy-wallet' ); ?></th>
                <th class="column-user"><?php esc_html_e( 'User', 'ezzy-wallet' ); ?></th>
                <th class="column-type"><?php esc_html_e( 'Type', 'ezzy-wallet' ); ?></th>
                <th class="column-amount"><?php esc_html_e( 'Amount', 'ezzy-wallet' ); ?></th>
                <th class="column-method"><?php esc_html_e( 'Method', 'ezzy-wallet' ); ?></th>
                <th class="column-status"><?php esc_html_e( 'Status', 'ezzy-wallet' ); ?></th>
                <th class="column-date"><?php esc_html_e( 'Date', 'ezzy-wallet' ); ?></th>
                <th class="column-actions" style="width: 200px;"><?php esc_html_e( 'Actions', 'ezzy-wallet' ); ?></th>
            </tr>
        </thead>
        <tbody id="ezzy-transactions-tbody">
            <?php if ( empty( $transactions ) ) : ?>
                <tr class="no-items-row">
                    <td colspan="9" class="no-items">
                        <?php esc_html_e( 'No transactions found.', 'ezzy-wallet' ); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $transactions as $tx ) : 
                    $user = get_userdata( $tx['user_id'] );
                    $username = $user ? $user->user_login : __( 'Unknown', 'ezzy-wallet' );
                    $user_email = $user ? $user->user_email : '';
                    
                    $metadata = ! empty( $tx['metadata'] ) ? json_decode( $tx['metadata'], true ) : array();
                    $method = isset( $metadata['method'] ) ? ucfirst( $metadata['method'] ) : '—';
                    
                    $is_pending = $tx['status'] === 'pending';
                    $is_approvable = in_array( $tx['type'], array( 'deposit_request', 'withdrawal_request' ), true );
                ?>
                    <tr data-transaction-id="<?php echo esc_attr( $tx['id'] ); ?>">
                        <td class="check-column">
                            <input type="checkbox" class="ezzy-tx-checkbox" value="<?php echo esc_attr( $tx['id'] ); ?>" />
                        </td>
                        <td class="column-id">
                            <strong>#<?php echo esc_html( $tx['id'] ); ?></strong>
                        </td>
                        <td class="column-user">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ezzy-wallet-users&action=edit&user_id=' . $tx['user_id'] ) ); ?>">
                                <?php echo esc_html( $username ); ?>
                            </a>
                            <?php if ( $user_email ) : ?>
                                <br><small><?php echo esc_html( $user_email ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-type">
                            <span class="ezzy-type-badge ezzy-type-<?php echo esc_attr( sanitize_title( $tx['type'] ) ); ?>">
                                <?php echo esc_html( Ezzy_Wallet_Transactions::get_type_label( $tx['type'] ) ); ?>
                            </span>
                        </td>
                        <td class="column-amount">
                            <strong>$<?php echo esc_html( number_format( (float) $tx['amount'], 2 ) ); ?></strong>
                        </td>
                        <td class="column-method">
                            <?php echo esc_html( $method ); ?>
                        </td>
                        <td class="column-status">
                            <?php echo Ezzy_Wallet_Transactions::get_status_badge( $tx['status'] ); ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html( date_i18n( 'M d, Y', strtotime( $tx['created_at'] ) ) ); ?>
                            <br><small><?php echo esc_html( date_i18n( 'g:i A', strtotime( $tx['created_at'] ) ) ); ?></small>
                        </td>
                        <td class="column-actions">
                            <?php if ( $is_pending && $is_approvable ) : ?>
                                <div class="ezzy-action-buttons">
                                    <button type="button" 
                                            class="button button-primary ezzy-approve-btn" 
                                            data-id="<?php echo esc_attr( $tx['id'] ); ?>"
                                            data-type="<?php echo esc_attr( $tx['type'] ); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e( 'Approve', 'ezzy-wallet' ); ?>
                                    </button>
                                    <button type="button" 
                                            class="button ezzy-reject-btn" 
                                            data-id="<?php echo esc_attr( $tx['id'] ); ?>"
                                            data-type="<?php echo esc_attr( $tx['type'] ); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                        <?php esc_html_e( 'Reject', 'ezzy-wallet' ); ?>
                                    </button>
                                </div>
                            <?php elseif ( ! empty( $tx['admin_note'] ) ) : ?>
                                <span class="ezzy-admin-note" title="<?php echo esc_attr( $tx['admin_note'] ); ?>">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    <?php esc_html_e( 'Note', 'ezzy-wallet' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="ezzy-processed">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    printf(
                        /* translators: %s: number of items */
                        _n( '%s item', '%s items', $data['total'], 'ezzy-wallet' ),
                        number_format_i18n( $data['total'] )
                    );
                    ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $base_url = add_query_arg( 'tab', $this->current_tab, admin_url( 'admin.php?page=ezzy-wallet-transactions' ) );
                    
                    // First page link
                    if ( $current_page > 1 ) {
                        printf(
                            '<a class="first-page button" href="%s" title="%s"><span class="screen-reader-text">%s</span>«</a>',
                            esc_url( add_query_arg( 'paged', 1, $base_url ) ),
                            esc_attr__( 'First page', 'ezzy-wallet' ),
                            esc_html__( 'First page', 'ezzy-wallet' )
                        );
                        printf(
                            '<a class="prev-page button" href="%s" title="%s"><span class="screen-reader-text">%s</span>‹</a>',
                            esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ),
                            esc_attr__( 'Previous page', 'ezzy-wallet' ),
                            esc_html__( 'Previous page', 'ezzy-wallet' )
                        );
                    } else {
                        echo '<span class="first-page button disabled">«</span>';
                        echo '<span class="prev-page button disabled">‹</span>';
                    }
                    
                    printf(
                        '<span class="paging-input"><span class="tablenav-paging-text">%d of <span class="total-pages">%d</span></span></span>',
                        $current_page,
                        $total_pages
                    );
                    
                    // Next and last page links
                    if ( $current_page < $total_pages ) {
                        printf(
                            '<a class="next-page button" href="%s" title="%s"><span class="screen-reader-text">%s</span>›</a>',
                            esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ),
                            esc_attr__( 'Next page', 'ezzy-wallet' ),
                            esc_html__( 'Next page', 'ezzy-wallet' )
                        );
                        printf(
                            '<a class="last-page button" href="%s" title="%s"><span class="screen-reader-text">%s</span>»</a>',
                            esc_url( add_query_arg( 'paged', $total_pages, $base_url ) ),
                            esc_attr__( 'Last page', 'ezzy-wallet' ),
                            esc_html__( 'Last page', 'ezzy-wallet' )
                        );
                    } else {
                        echo '<span class="next-page button disabled">›</span>';
                        echo '<span class="last-page button disabled">»</span>';
                    }
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Approve/Reject Modal -->
<div id="ezzy-transaction-modal" class="ezzy-modal-backdrop" style="display:none;">
    <div class="ezzy-modal-content">
        <div class="ezzy-modal-header">
            <h2 id="ezzy-modal-title"><?php esc_html_e( 'Process Transaction', 'ezzy-wallet' ); ?></h2>
            <button type="button" class="ezzy-modal-close">&times;</button>
        </div>
        <div class="ezzy-modal-body">
            <p id="ezzy-modal-message"></p>
            <div class="ezzy-form-group">
                <label for="ezzy-admin-note"><?php esc_html_e( 'Admin Note (Optional)', 'ezzy-wallet' ); ?></label>
                <textarea id="ezzy-admin-note" rows="3" placeholder="<?php esc_attr_e( 'Add a note for this action...', 'ezzy-wallet' ); ?>"></textarea>
            </div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="button" id="ezzy-modal-cancel"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="button button-primary" id="ezzy-modal-confirm">
                <?php esc_html_e( 'Confirm', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="ezzy-delete-modal" class="ezzy-modal-backdrop" style="display:none;">
    <div class="ezzy-modal-content">
        <div class="ezzy-modal-header">
            <h2 id="ezzy-delete-modal-title"><?php esc_html_e( 'Delete Transactions', 'ezzy-wallet' ); ?></h2>
            <button type="button" class="ezzy-modal-close">&times;</button>
        </div>
        <div class="ezzy-modal-body">
            <p id="ezzy-delete-modal-message"></p>
            <p class="ezzy-warning-text">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'This action cannot be undone.', 'ezzy-wallet' ); ?>
            </p>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="button" id="ezzy-delete-modal-cancel"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="button button-danger" id="ezzy-delete-modal-confirm">
                <?php esc_html_e( 'Delete', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentTransactionId = null;
    var currentAction = null;
    var deleteMode = null;
    var selectedIds = [];

    // Select all checkbox
    $('#ezzy-select-all').on('change', function() {
        $('.ezzy-tx-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Individual checkbox change
    $(document).on('change', '.ezzy-tx-checkbox', function() {
        var total = $('.ezzy-tx-checkbox').length;
        var checked = $('.ezzy-tx-checkbox:checked').length;
        $('#ezzy-select-all').prop('checked', total === checked && total > 0);
    });

    // Apply bulk action
    $('#ezzy-apply-bulk-action').on('click', function() {
        var action = $('#ezzy-bulk-action').val();
        if (action !== 'delete') {
            showNotice('error', '<?php echo esc_js( __( 'Please select an action.', 'ezzy-wallet' ) ); ?>');
            return;
        }

        selectedIds = [];
        $('.ezzy-tx-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            showNotice('error', '<?php echo esc_js( __( 'Please select at least one transaction.', 'ezzy-wallet' ) ); ?>');
            return;
        }

        deleteMode = 'selected';
        $('#ezzy-delete-modal-title').text('<?php echo esc_js( __( 'Delete Selected Transactions', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-delete-modal-message').text('<?php echo esc_js( __( 'Are you sure you want to delete', 'ezzy-wallet' ) ); ?> ' + selectedIds.length + ' <?php echo esc_js( __( 'transaction(s)?', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-delete-modal').fadeIn(200);
    });

    // Delete all button
    $('#ezzy-delete-all-transactions').on('click', function() {
        deleteMode = 'all';
        $('#ezzy-delete-modal-title').text('<?php echo esc_js( __( 'Delete All Transactions', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-delete-modal-message').text('<?php echo esc_js( __( 'Are you sure you want to delete ALL transactions? This will permanently remove all transaction history.', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-delete-modal').fadeIn(200);
    });

    // Close delete modal
    $('#ezzy-delete-modal .ezzy-modal-close, #ezzy-delete-modal-cancel').on('click', function() {
        $('#ezzy-delete-modal').fadeOut(200);
    });

    // Confirm delete
    $('#ezzy-delete-modal-confirm').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Deleting...', 'ezzy-wallet' ) ); ?>');

        var ajaxData = {
            nonce: '<?php echo wp_create_nonce( 'ezzy_wallet_admin_nonce' ); ?>'
        };

        if (deleteMode === 'all') {
            ajaxData.action = 'ezzy_wallet_delete_all_transactions';
        } else {
            ajaxData.action = 'ezzy_wallet_delete_transactions';
            ajaxData.transaction_ids = selectedIds;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', '<?php echo esc_js( __( 'An error occurred. Please try again.', 'ezzy-wallet' ) ); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Delete', 'ezzy-wallet' ) ); ?>');
                $('#ezzy-delete-modal').fadeOut(200);
            }
        });
    });

    // Approve button click
    $('.ezzy-approve-btn').on('click', function() {
        currentTransactionId = $(this).data('id');
        currentAction = 'approve';
        var type = $(this).data('type');
        var typeLabel = type === 'deposit_request' ? 'deposit' : 'withdrawal';
        
        $('#ezzy-modal-title').text('<?php echo esc_js( __( 'Approve Transaction', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-modal-message').text('<?php echo esc_js( __( 'Are you sure you want to approve this', 'ezzy-wallet' ) ); ?> ' + typeLabel + '? <?php echo esc_js( __( 'This will update the user\'s wallet balance.', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-modal-confirm').removeClass('button-danger').addClass('button-primary').text('<?php echo esc_js( __( 'Approve', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-admin-note').val('');
        $('#ezzy-transaction-modal').fadeIn(200);
    });

    // Reject button click
    $('.ezzy-reject-btn').on('click', function() {
        currentTransactionId = $(this).data('id');
        currentAction = 'reject';
        var type = $(this).data('type');
        var typeLabel = type === 'deposit_request' ? 'deposit' : 'withdrawal';
        
        $('#ezzy-modal-title').text('<?php echo esc_js( __( 'Reject Transaction', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-modal-message').text('<?php echo esc_js( __( 'Are you sure you want to reject this', 'ezzy-wallet' ) ); ?> ' + typeLabel + '?');
        $('#ezzy-modal-confirm').removeClass('button-primary').addClass('button-danger').text('<?php echo esc_js( __( 'Reject', 'ezzy-wallet' ) ); ?>');
        $('#ezzy-admin-note').val('');
        $('#ezzy-transaction-modal').fadeIn(200);
    });

    // Close modal
    $('#ezzy-transaction-modal .ezzy-modal-close, #ezzy-modal-cancel').on('click', function() {
        $('#ezzy-transaction-modal').fadeOut(200);
    });

    // Click outside to close
    $('.ezzy-modal-backdrop').on('click', function(e) {
        if ($(e.target).is(this)) {
            $(this).fadeOut(200);
        }
    });

    // Confirm action
    $('#ezzy-modal-confirm').on('click', function() {
        var $btn = $(this);
        var adminNote = $('#ezzy-admin-note').val();
        var action = currentAction === 'approve' ? 'ezzy_wallet_approve_transaction' : 'ezzy_wallet_reject_transaction';

        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'ezzy-wallet' ) ); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                nonce: '<?php echo wp_create_nonce( 'ezzy_wallet_admin_nonce' ); ?>',
                transaction_id: currentTransactionId,
                admin_note: adminNote
            },
            success: function(response) {
                if (response.success) {
                    var $row = $('tr[data-transaction-id="' + currentTransactionId + '"]');
                    var newStatus = response.data.new_status;
                    var statusClass = 'ezzy-status-' + newStatus;
                    
                    $row.find('.column-status').html('<span class="ezzy-status-badge ' + statusClass + '">' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + '</span>');
                    $row.find('.column-actions').html('<span class="ezzy-processed">—</span>');
                    
                    $('#ezzy-transaction-modal').fadeOut(200);
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', '<?php echo esc_js( __( 'An error occurred. Please try again.', 'ezzy-wallet' ) ); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(currentAction === 'approve' ? '<?php echo esc_js( __( 'Approve', 'ezzy-wallet' ) ); ?>' : '<?php echo esc_js( __( 'Reject', 'ezzy-wallet' ) ); ?>');
            }
        });
    });

    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        $('.ezzy-wallet-admin-title').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>

<style>
.ezzy-transactions-tabs {
    margin: 10px 0 20px;
}

.ezzy-transactions-table {
    margin-top: 0;
}

.ezzy-transactions-table .check-column {
    width: 30px;
    padding: 8px 10px;
}

.ezzy-transactions-table .column-id {
    width: 60px;
}

.tablenav.top {
    margin: 15px 0 10px;
    padding-top: 0;
}

.tablenav .alignright {
    margin-left: 10px;
}

#ezzy-delete-all-transactions {
    color: #b32d2e;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

#ezzy-delete-all-transactions:hover {
    color: #a00;
    border-color: #a00;
}

#ezzy-delete-all-transactions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.ezzy-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.ezzy-type-deposit_request,
.ezzy-type-deposit {
    background: #e8f5e9;
    color: #2e7d32;
}

.ezzy-type-withdrawal_request,
.ezzy-type-withdrawal {
    background: #fff3e0;
    color: #e65100;
}

.ezzy-type-transfer_sent,
.ezzy-type-transfer_received,
.ezzy-type-transfer {
    background: #e3f2fd;
    color: #1565c0;
}

.ezzy-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.ezzy-status-pending {
    background: #fff8e1;
    color: #f57c00;
}

.ezzy-status-approved,
.ezzy-status-completed {
    background: #e8f5e9;
    color: #388e3c;
}

.ezzy-status-rejected {
    background: #ffebee;
    color: #c62828;
}

.ezzy-status-system {
    background: #f5f5f5;
    color: #616161;
}

.ezzy-action-buttons {
    display: flex;
    gap: 5px;
}

.ezzy-action-buttons .button {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 0 8px;
    height: 28px;
    line-height: 26px;
}

.ezzy-action-buttons .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.ezzy-admin-note {
    color: #666;
    cursor: help;
}

.ezzy-admin-note .dashicons {
    vertical-align: middle;
}

/* Pagination Styles */
.tablenav-pages .button.disabled {
    opacity: 0.5;
    cursor: default;
    pointer-events: none;
}

.tablenav-pages .pagination-links {
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.tablenav-pages .paging-input {
    margin: 0 8px;
}

/* Modal Styles */
.ezzy-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ezzy-modal-content {
    background: #fff;
    border-radius: 8px;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}

.ezzy-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

.ezzy-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.ezzy-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    line-height: 1;
}

.ezzy-modal-close:hover {
    color: #000;
}

.ezzy-modal-body {
    padding: 20px;
}

.ezzy-modal-body p {
    margin-top: 0;
}

.ezzy-form-group {
    margin-top: 15px;
}

.ezzy-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.ezzy-form-group textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.ezzy-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.button-danger {
    background: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #fff !important;
}

.button-danger:hover {
    background: #c82333 !important;
    border-color: #bd2130 !important;
}

.ezzy-warning-text {
    color: #d63638;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 15px;
    padding: 10px;
    background: #fff5f5;
    border-radius: 4px;
}

.ezzy-warning-text .dashicons {
    color: #d63638;
}
</style>
