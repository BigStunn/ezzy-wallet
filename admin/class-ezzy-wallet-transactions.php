<?php
/**
 * Admin Transactions handler for eZZY Wallet.
 *
 * Manages transaction listing, approval, and rejection in admin.
 *
 * @package EzzyWallet
 * @since 1.5.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Transactions
 *
 * Handles admin transaction management.
 *
 * @since 1.5.0
 */
class Ezzy_Wallet_Transactions {

    /**
     * Current filter tab.
     *
     * @var string
     */
    private $current_tab = 'all';

    /**
     * Items per page.
     *
     * @var int
     */
    private $per_page = 10;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_ajax_ezzy_wallet_approve_transaction', array( $this, 'ajax_approve_transaction' ) );
        add_action( 'wp_ajax_ezzy_wallet_reject_transaction', array( $this, 'ajax_reject_transaction' ) );
        add_action( 'wp_ajax_ezzy_wallet_delete_transactions', array( $this, 'ajax_delete_transactions' ) );
        add_action( 'wp_ajax_ezzy_wallet_delete_all_transactions', array( $this, 'ajax_delete_all_transactions' ) );
    }

    /**
     * Render transactions page.
     *
     * @return void
     */
    public function render_page() {
        $this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'all';
        
        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/transactions-page.php';
    }

    /**
     * Get transactions based on current filters.
     *
     * @param int $page Current page number.
     * @return array Transactions data.
     */
    public function get_transactions( $page = 1 ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();
        $offset = ( $page - 1 ) * $this->per_page;

        // Build WHERE clause based on tab
        $where = '1=1';
        switch ( $this->current_tab ) {
            case 'deposits':
                $where = "type = 'deposit_request'";
                break;
            case 'withdrawals':
                $where = "type = 'withdrawal_request'";
                break;
            case 'transfers':
                $where = "type IN ('transfer_sent', 'transfer_received')";
                break;
            case 'pending':
                $where = "status = 'pending'";
                break;
        }

        // Get total count
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE {$where}"
        );

        // Get transactions
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} 
                 WHERE {$where}
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $this->per_page,
                $offset
            ),
            ARRAY_A
        );

        return array(
            'transactions' => $transactions ? $transactions : array(),
            'total'        => (int) $total,
            'total_pages'  => ceil( $total / $this->per_page ),
            'current_page' => $page,
        );
    }

    /**
     * Get tab counts.
     *
     * @return array Counts by tab.
     */
    public function get_tab_counts() {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        $counts = array(
            'all'         => 0,
            'deposits'    => 0,
            'withdrawals' => 0,
            'transfers'   => 0,
            'pending'     => 0,
        );

        // Total count
        $counts['all'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

        // Deposits
        $counts['deposits'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE type = 'deposit_request'"
        );

        // Withdrawals
        $counts['withdrawals'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE type = 'withdrawal_request'"
        );

        // Transfers
        $counts['transfers'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE type IN ('transfer_sent', 'transfer_received')"
        );

        // Pending
        $counts['pending'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'"
        );

        return $counts;
    }

    /**
     * AJAX: Approve transaction.
     *
     * @return void
     */
    public function ajax_approve_transaction() {
        check_ajax_referer( 'ezzy_wallet_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'ezzy-wallet' ) ) );
        }

        $transaction_id = isset( $_POST['transaction_id'] ) ? absint( $_POST['transaction_id'] ) : 0;
        $admin_note = isset( $_POST['admin_note'] ) ? sanitize_textarea_field( $_POST['admin_note'] ) : '';

        if ( ! $transaction_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid transaction ID.', 'ezzy-wallet' ) ) );
        }

        $result = $this->process_approval( $transaction_id, $admin_note );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX: Reject transaction.
     *
     * @return void
     */
    public function ajax_reject_transaction() {
        check_ajax_referer( 'ezzy_wallet_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'ezzy-wallet' ) ) );
        }

        $transaction_id = isset( $_POST['transaction_id'] ) ? absint( $_POST['transaction_id'] ) : 0;
        $admin_note = isset( $_POST['admin_note'] ) ? sanitize_textarea_field( $_POST['admin_note'] ) : '';

        if ( ! $transaction_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid transaction ID.', 'ezzy-wallet' ) ) );
        }

        $result = $this->process_rejection( $transaction_id, $admin_note );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Process transaction approval.
     *
     * @param int    $transaction_id Transaction ID.
     * @param string $admin_note     Admin note.
     * @return array Result.
     */
    private function process_approval( $transaction_id, $admin_note = '' ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();

        // Get transaction
        $transaction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$transactions_table} WHERE id = %d",
                $transaction_id
            ),
            ARRAY_A
        );

        if ( ! $transaction ) {
            return array(
                'success' => false,
                'message' => __( 'Transaction not found.', 'ezzy-wallet' ),
            );
        }

        // Check if already processed
        if ( $transaction['status'] !== 'pending' ) {
            return array(
                'success' => false,
                'message' => __( 'Transaction has already been processed.', 'ezzy-wallet' ),
            );
        }

        $user_id = $transaction['user_id'];
        $amount = (float) $transaction['amount'];
        $type = $transaction['type'];
        $admin_id = get_current_user_id();

        // Start transaction
        $wpdb->query( 'START TRANSACTION' );

        try {
            if ( $type === 'deposit_request' ) {
                // Add funds to user's balance
                $updated = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$accounts_table} 
                         SET balance = balance + %f,
                             total_deposit = total_deposit + %f,
                             updated_at = %s
                         WHERE user_id = %d",
                        $amount,
                        $amount,
                        current_time( 'mysql' ),
                        $user_id
                    )
                );

                if ( $updated === false ) {
                    throw new Exception( __( 'Failed to update balance.', 'ezzy-wallet' ) );
                }

                $new_status = 'approved';
                $description = sprintf(
                    __( 'Deposit approved. $%s has been added to your wallet.', 'ezzy-wallet' ),
                    number_format( $amount, 2 )
                );

            } elseif ( $type === 'withdrawal_request' ) {
                // Check balance first
                $current_balance = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT balance FROM {$accounts_table} WHERE user_id = %d",
                        $user_id
                    )
                );

                if ( (float) $current_balance < $amount ) {
                    throw new Exception( __( 'Insufficient balance for withdrawal.', 'ezzy-wallet' ) );
                }

                // Deduct funds from user's balance
                $updated = $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$accounts_table} 
                         SET balance = balance - %f,
                             total_withdrawal = total_withdrawal + %f,
                             updated_at = %s
                         WHERE user_id = %d AND balance >= %f",
                        $amount,
                        $amount,
                        current_time( 'mysql' ),
                        $user_id,
                        $amount
                    )
                );

                if ( ! $updated ) {
                    throw new Exception( __( 'Failed to update balance.', 'ezzy-wallet' ) );
                }

                $new_status = 'completed';
                $description = sprintf(
                    __( 'Withdrawal completed. $%s has been sent to your account.', 'ezzy-wallet' ),
                    number_format( $amount, 2 )
                );

            } else {
                throw new Exception( __( 'Invalid transaction type for approval.', 'ezzy-wallet' ) );
            }

            // Update transaction status
            $wpdb->update(
                $transactions_table,
                array(
                    'status'       => $new_status,
                    'description'  => $description,
                    'admin_id'     => $admin_id,
                    'admin_note'   => $admin_note,
                    'processed_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $transaction_id ),
                array( '%s', '%s', '%d', '%s', '%s' ),
                array( '%d' )
            );

            $wpdb->query( 'COMMIT' );

            // Send notification to user
            $this->send_user_notification( $user_id, $type, 'approved', $amount, $admin_note );

            // Log admin action
            $this->log_admin_action( $admin_id, 'approved', $transaction_id, $user_id, $type, $amount );

            return array(
                'success'    => true,
                'message'    => __( 'Transaction approved successfully.', 'ezzy-wallet' ),
                'new_status' => $new_status,
            );

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }

    /**
     * Process transaction rejection.
     *
     * @param int    $transaction_id Transaction ID.
     * @param string $admin_note     Admin note.
     * @return array Result.
     */
    private function process_rejection( $transaction_id, $admin_note = '' ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();

        // Get transaction
        $transaction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$transactions_table} WHERE id = %d",
                $transaction_id
            ),
            ARRAY_A
        );

        if ( ! $transaction ) {
            return array(
                'success' => false,
                'message' => __( 'Transaction not found.', 'ezzy-wallet' ),
            );
        }

        // Check if already processed
        if ( $transaction['status'] !== 'pending' ) {
            return array(
                'success' => false,
                'message' => __( 'Transaction has already been processed.', 'ezzy-wallet' ),
            );
        }

        $user_id = $transaction['user_id'];
        $amount = (float) $transaction['amount'];
        $type = $transaction['type'];
        $admin_id = get_current_user_id();

        $description = sprintf(
            __( '%s request rejected.%s', 'ezzy-wallet' ),
            $type === 'deposit_request' ? __( 'Deposit', 'ezzy-wallet' ) : __( 'Withdrawal', 'ezzy-wallet' ),
            $admin_note ? ' ' . __( 'Reason:', 'ezzy-wallet' ) . ' ' . $admin_note : ''
        );

        // Update transaction status (no balance change for rejection)
        $updated = $wpdb->update(
            $transactions_table,
            array(
                'status'       => 'rejected',
                'description'  => $description,
                'admin_id'     => $admin_id,
                'admin_note'   => $admin_note,
                'processed_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $transaction_id ),
            array( '%s', '%s', '%d', '%s', '%s' ),
            array( '%d' )
        );

        if ( $updated === false ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to update transaction.', 'ezzy-wallet' ),
            );
        }

        // Send notification to user
        $this->send_user_notification( $user_id, $type, 'rejected', $amount, $admin_note );

        // Log admin action
        $this->log_admin_action( $admin_id, 'rejected', $transaction_id, $user_id, $type, $amount );

        return array(
            'success'    => true,
            'message'    => __( 'Transaction rejected.', 'ezzy-wallet' ),
            'new_status' => 'rejected',
        );
    }

    /**
     * Send notification to user about transaction status.
     *
     * @param int    $user_id    User ID.
     * @param string $type       Transaction type.
     * @param string $action     Action taken (approved/rejected).
     * @param float  $amount     Transaction amount.
     * @param string $admin_note Admin note.
     * @return void
     */
    private function send_user_notification( $user_id, $type, $action, $amount, $admin_note = '' ) {
        global $wpdb;

        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        
        $type_label = $type === 'deposit_request' ? __( 'Deposit', 'ezzy-wallet' ) : __( 'Withdrawal', 'ezzy-wallet' );
        $action_label = $action === 'approved' ? __( 'approved', 'ezzy-wallet' ) : __( 'rejected', 'ezzy-wallet' );

        // Create system notification (as a transaction entry that shows in Messages)
        if ( $action === 'approved' ) {
            $message = sprintf(
                __( 'Your %s request for $%s has been approved.', 'ezzy-wallet' ),
                strtolower( $type_label ),
                number_format( $amount, 2 )
            );
        } else {
            $message = sprintf(
                __( 'Your %s request for $%s has been rejected.%s', 'ezzy-wallet' ),
                strtolower( $type_label ),
                number_format( $amount, 2 ),
                $admin_note ? ' ' . __( 'Reason:', 'ezzy-wallet' ) . ' ' . $admin_note : ''
            );
        }

        // Insert notification as transaction (shows in Messages panel)
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id'     => $user_id,
                'type'        => $action === 'approved' ? 'request_approved' : 'request_rejected',
                'amount'      => 0,
                'description' => $message,
                'status'      => 'completed',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        // Send email if enabled
        $this->maybe_send_email_notification( $user_id, $type, $action, $amount, $admin_note );
    }

    /**
     * Send email notification if enabled.
     *
     * @param int    $user_id    User ID.
     * @param string $type       Transaction type.
     * @param string $action     Action taken.
     * @param float  $amount     Amount.
     * @param string $admin_note Admin note.
     * @return void
     */
    private function maybe_send_email_notification( $user_id, $type, $action, $amount, $admin_note = '' ) {
        // Check if email notifications are enabled
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $email_enabled = isset( $settings['enable_email_notifications'] ) ? $settings['enable_email_notifications'] : true;

        if ( ! $email_enabled ) {
            return;
        }

        $user = get_userdata( $user_id );
        if ( ! $user || ! $user->user_email ) {
            return;
        }

        $type_label = $type === 'deposit_request' ? __( 'Deposit', 'ezzy-wallet' ) : __( 'Withdrawal', 'ezzy-wallet' );
        $action_label = $action === 'approved' ? __( 'Approved', 'ezzy-wallet' ) : __( 'Rejected', 'ezzy-wallet' );

        $subject = sprintf(
            __( '[%s] Your %s Request has been %s', 'ezzy-wallet' ),
            get_bloginfo( 'name' ),
            $type_label,
            $action_label
        );

        if ( $action === 'approved' ) {
            $body = sprintf(
                __( "Hello %s,\n\nYour %s request has been approved.\n\nAmount: $%s\n\nThe funds have been %s your wallet.\n\nThank you for using %s!", 'ezzy-wallet' ),
                $user->display_name,
                strtolower( $type_label ),
                number_format( $amount, 2 ),
                $type === 'deposit_request' ? __( 'added to', 'ezzy-wallet' ) : __( 'sent from', 'ezzy-wallet' ),
                get_bloginfo( 'name' )
            );
        } else {
            $body = sprintf(
                __( "Hello %s,\n\nYour %s request has been rejected.\n\nAmount: $%s\n%s\n\nIf you have questions, please contact support.\n\nThank you,\n%s", 'ezzy-wallet' ),
                $user->display_name,
                strtolower( $type_label ),
                number_format( $amount, 2 ),
                $admin_note ? __( 'Reason:', 'ezzy-wallet' ) . ' ' . $admin_note : '',
                get_bloginfo( 'name' )
            );
        }

        wp_mail( $user->user_email, $subject, $body );
    }

    /**
     * Log admin action for audit trail.
     *
     * @param int    $admin_id       Admin user ID.
     * @param string $action         Action taken.
     * @param int    $transaction_id Transaction ID.
     * @param int    $user_id        User ID.
     * @param string $type           Transaction type.
     * @param float  $amount         Amount.
     * @return void
     */
    private function log_admin_action( $admin_id, $action, $transaction_id, $user_id, $type, $amount ) {
        $log_entry = array(
            'admin_id'       => $admin_id,
            'action'         => $action,
            'transaction_id' => $transaction_id,
            'user_id'        => $user_id,
            'type'           => $type,
            'amount'         => $amount,
            'timestamp'      => current_time( 'mysql' ),
        );

        // Store in options as audit log (simple implementation)
        $audit_log = get_option( 'ezzy_wallet_audit_log', array() );
        array_unshift( $audit_log, $log_entry );
        
        // Keep last 1000 entries
        $audit_log = array_slice( $audit_log, 0, 1000 );
        
        update_option( 'ezzy_wallet_audit_log', $audit_log );

        // Also log to security audit system
        Ezzy_Wallet_Security::log_audit_event(
            'admin_' . $action . '_transaction',
            $user_id,
            array(
                'transaction_id' => $transaction_id,
                'type'           => $type,
                'amount'         => $amount,
            ),
            $admin_id
        );
    }

    /**
     * Get transaction type label.
     *
     * @param string $type Transaction type.
     * @return string Label.
     */
    public static function get_type_label( $type ) {
        $labels = array(
            'deposit_request'    => __( 'Deposit', 'ezzy-wallet' ),
            'withdrawal_request' => __( 'Withdrawal', 'ezzy-wallet' ),
            'transfer_sent'      => __( 'Transfer Sent', 'ezzy-wallet' ),
            'transfer_received'  => __( 'Transfer Received', 'ezzy-wallet' ),
            'deposit'            => __( 'Deposit', 'ezzy-wallet' ),
            'withdrawal'         => __( 'Withdrawal', 'ezzy-wallet' ),
            'transfer'           => __( 'Transfer', 'ezzy-wallet' ),
            'request_approved'   => __( 'Request Approved', 'ezzy-wallet' ),
            'request_rejected'   => __( 'Request Rejected', 'ezzy-wallet' ),
        );

        return isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( str_replace( '_', ' ', $type ) );
    }

    /**
     * Get status badge HTML.
     *
     * @param string $status Status.
     * @return string HTML.
     */
    public static function get_status_badge( $status ) {
        $classes = array(
            'pending'   => 'ezzy-status-pending',
            'approved'  => 'ezzy-status-approved',
            'rejected'  => 'ezzy-status-rejected',
            'completed' => 'ezzy-status-completed',
        );

        $class = isset( $classes[ $status ] ) ? $classes[ $status ] : 'ezzy-status-default';
        
        return sprintf(
            '<span class="ezzy-status-badge %s">%s</span>',
            esc_attr( $class ),
            esc_html( ucfirst( $status ) )
        );
    }

    /**
     * AJAX: Delete selected transactions.
     *
     * @return void
     */
    public function ajax_delete_transactions() {
        check_ajax_referer( 'ezzy_wallet_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'ezzy-wallet' ) ) );
        }

        $transaction_ids = isset( $_POST['transaction_ids'] ) ? array_map( 'absint', (array) $_POST['transaction_ids'] ) : array();

        if ( empty( $transaction_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No transactions selected.', 'ezzy-wallet' ) ) );
        }

        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Build placeholders for IN clause
        $placeholders = implode( ',', array_fill( 0, count( $transaction_ids ), '%d' ) );
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE id IN ({$placeholders})",
                $transaction_ids
            )
        );

        if ( $deleted === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to delete transactions.', 'ezzy-wallet' ) ) );
        }

        // Log admin action
        $this->log_admin_action(
            get_current_user_id(),
            'bulk_delete',
            0,
            0,
            'transactions',
            count( $transaction_ids )
        );

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: number of deleted transactions */
                _n(
                    '%d transaction deleted.',
                    '%d transactions deleted.',
                    $deleted,
                    'ezzy-wallet'
                ),
                $deleted
            ),
            'deleted_count' => $deleted,
        ) );
    }

    /**
     * AJAX: Delete all transactions.
     *
     * @return void
     */
    public function ajax_delete_all_transactions() {
        check_ajax_referer( 'ezzy_wallet_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'ezzy-wallet' ) ) );
        }

        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Get count before deletion
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

        // Delete all transactions
        $deleted = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

        if ( $deleted === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to delete transactions.', 'ezzy-wallet' ) ) );
        }

        // Log admin action
        $this->log_admin_action(
            get_current_user_id(),
            'delete_all',
            0,
            0,
            'transactions',
            $total
        );

        wp_send_json_success( array(
            'message' => __( 'All transactions have been deleted.', 'ezzy-wallet' ),
            'deleted_count' => $total,
        ) );
    }
}
