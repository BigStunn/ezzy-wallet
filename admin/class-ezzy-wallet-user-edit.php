<?php
/**
 * User Edit handler for eZZY Wallet.
 *
 * Handles editing individual wallet accounts.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_User_Edit
 *
 * Handles the wallet user edit functionality.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_User_Edit {

    /**
     * User ID being edited.
     *
     * @var int
     */
    private $user_id;

    /**
     * WordPress user object.
     *
     * @var WP_User
     */
    private $user;

    /**
     * Wallet account data.
     *
     * @var array
     */
    private $wallet_account;

    /**
     * Error messages.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Success message.
     *
     * @var string
     */
    private $success_message = '';

    /**
     * Constructor.
     */
    public function __construct() {
        // Process form submission.
        if ( isset( $_POST['ezzy_wallet_save_user'] ) ) {
            $this->process_form();
        }
    }

    /**
     * Render the edit page.
     *
     * @return void
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'ezzy-wallet' ) );
        }

        // Get and validate user ID.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $this->user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

        if ( empty( $this->user_id ) ) {
            wp_die( esc_html__( 'Invalid user ID.', 'ezzy-wallet' ) );
        }

        // Verify nonce for GET request.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'ezzy_wallet_edit_user_' . $this->user_id ) && ! isset( $_POST['ezzy_wallet_save_user'] ) ) {
            wp_die( esc_html__( 'Security check failed.', 'ezzy-wallet' ) );
        }

        // Get WordPress user.
        $this->user = get_user_by( 'id', $this->user_id );

        if ( ! $this->user ) {
            wp_die( esc_html__( 'User not found.', 'ezzy-wallet' ) );
        }

        // Get wallet account.
        $this->wallet_account = $this->get_wallet_account();

        if ( ! $this->wallet_account ) {
            wp_die( esc_html__( 'Wallet account not found for this user.', 'ezzy-wallet' ) );
        }

        // Include the edit form template.
        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/wallet-user-edit-form.php';
    }

    /**
     * Get wallet account data.
     *
     * @return array|null Wallet account data or null.
     */
    private function get_wallet_account() {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d",
                $this->user_id
            ),
            ARRAY_A
        );

        return $account;
    }

    /**
     * Process the edit form submission.
     *
     * @return void
     */
    private function process_form() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_user_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_user_nonce'] ) ), 'ezzy_wallet_save_user' ) ) {
            $this->errors[] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Verify capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->errors[] = __( 'You do not have permission to perform this action.', 'ezzy-wallet' );
            return;
        }

        // Get user ID from POST.
        $this->user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

        if ( empty( $this->user_id ) ) {
            $this->errors[] = __( 'Invalid user ID.', 'ezzy-wallet' );
            return;
        }

        // Get current wallet account.
        $this->wallet_account = $this->get_wallet_account();

        if ( ! $this->wallet_account ) {
            $this->errors[] = __( 'Wallet account not found.', 'ezzy-wallet' );
            return;
        }

        // Sanitize and validate inputs.
        $new_balance          = $this->sanitize_decimal( isset( $_POST['balance'] ) ? $_POST['balance'] : '0' );
        $new_total_deposit    = $this->sanitize_decimal( isset( $_POST['total_deposit'] ) ? $_POST['total_deposit'] : '0' );
        $new_total_transfer   = $this->sanitize_decimal( isset( $_POST['total_transfer'] ) ? $_POST['total_transfer'] : '0' );
        $new_total_withdrawal = $this->sanitize_decimal( isset( $_POST['total_withdrawal'] ) ? $_POST['total_withdrawal'] : '0' );
        
        // Get withdrawal setting (0=follow global, 1=disabled, 2=enabled override)
        $withdrawals_disabled = isset( $_POST['withdrawals_disabled'] ) ? absint( $_POST['withdrawals_disabled'] ) : 0;
        // Ensure value is 0, 1, or 2
        if ( $withdrawals_disabled > 2 ) {
            $withdrawals_disabled = 0;
        }

        // Validate non-negative values.
        if ( $new_balance < 0 || $new_total_deposit < 0 || $new_total_transfer < 0 || $new_total_withdrawal < 0 ) {
            $this->errors[] = __( 'Values cannot be negative.', 'ezzy-wallet' );
            return;
        }

        // Get old values for comparison.
        $old_balance              = (float) $this->wallet_account['balance'];
        $old_total_deposit        = (float) $this->wallet_account['total_deposit'];
        $old_total_transfer       = (float) $this->wallet_account['total_transfer'];
        $old_total_withdrawal     = (float) $this->wallet_account['total_withdrawal'];
        $old_withdrawals_disabled = (int) $this->wallet_account['withdrawals_disabled'];

        // Check if any values changed.
        $balance_changed     = abs( $new_balance - $old_balance ) > 0.00000001;
        $deposit_changed     = abs( $new_total_deposit - $old_total_deposit ) > 0.00000001;
        $transfer_changed    = abs( $new_total_transfer - $old_total_transfer ) > 0.00000001;
        $withdrawal_changed  = abs( $new_total_withdrawal - $old_total_withdrawal ) > 0.00000001;
        $permission_changed  = $withdrawals_disabled !== $old_withdrawals_disabled;

        // If nothing changed, just redirect.
        if ( ! $balance_changed && ! $deposit_changed && ! $transfer_changed && ! $withdrawal_changed && ! $permission_changed ) {
            $this->success_message = __( 'No changes were made.', 'ezzy-wallet' );
            return;
        }

        // Update the wallet account.
        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_accounts_table_name();

        $updated = $wpdb->update(
            $table_name,
            array(
                'balance'              => number_format( $new_balance, 8, '.', '' ),
                'total_deposit'        => number_format( $new_total_deposit, 8, '.', '' ),
                'total_transfer'       => number_format( $new_total_transfer, 8, '.', '' ),
                'total_withdrawal'     => number_format( $new_total_withdrawal, 8, '.', '' ),
                'withdrawals_disabled' => $withdrawals_disabled,
                'updated_at'           => current_time( 'mysql' ),
            ),
            array( 'user_id' => $this->user_id ),
            array( '%s', '%s', '%s', '%s', '%d', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            $this->errors[] = __( 'Failed to update wallet account. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Log transactions for changes.
        // Note: We only log deposit, withdrawal, and transfer changes - NOT balance-only changes
        if ( $deposit_changed ) {
            $this->log_transaction_update(
                'deposit',
                $new_total_deposit - $old_total_deposit,
                $old_balance,
                $new_balance
            );
        }
        
        if ( $withdrawal_changed ) {
            $this->log_transaction_update(
                'withdrawal',
                $new_total_withdrawal - $old_total_withdrawal,
                $old_balance,
                $new_balance
            );
        }
        
        if ( $transfer_changed ) {
            $this->log_transaction_update(
                'transfer',
                $new_total_transfer - $old_total_transfer,
                $old_balance,
                $new_balance
            );
        }
        
        // Balance-only changes are NOT logged (stays anonymous per requirements)

        if ( $permission_changed ) {
            $this->log_permission_change( $withdrawals_disabled );
        }

        // Refresh wallet account data.
        $this->wallet_account = $this->get_wallet_account();

        $this->success_message = __( 'Wallet account updated successfully.', 'ezzy-wallet' );
    }

    /**
     * Sanitize decimal value.
     *
     * @param mixed $value Value to sanitize.
     * @return float Sanitized decimal value.
     */
    private function sanitize_decimal( $value ) {
        // Remove any non-numeric characters except decimal point.
        $value = preg_replace( '/[^0-9.\-]/', '', $value );
        return (float) $value;
    }

    /**
     * Log transaction update.
     *
     * @param string $type        Transaction type (deposit, withdrawal, transfer).
     * @param float  $difference  The difference amount.
     * @param float  $old_balance The old balance.
     * @param float  $new_balance The new balance.
     * @return void
     */
    private function log_transaction_update( $type, $difference, $old_balance, $new_balance ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        // Create user-friendly description based on transaction type
        switch ( $type ) {
            case 'deposit':
                $description = __( 'Deposit successful and your total balance updated accordingly.', 'ezzy-wallet' );
                break;
            case 'withdrawal':
                $description = __( 'Withdrawal successful and your total balance updated accordingly.', 'ezzy-wallet' );
                break;
            case 'transfer':
                $description = __( 'Transfer successful and your total balance updated accordingly.', 'ezzy-wallet' );
                break;
            default:
                $description = __( 'Transaction completed successfully.', 'ezzy-wallet' );
                break;
        }

        $wpdb->insert(
            $table_name,
            array(
                'user_id'     => $this->user_id,
                'type'        => $type,
                'amount'      => number_format( abs( $difference ), 8, '.', '' ),
                'description' => $description,
                'status'      => 'completed',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        // Log security event.
        Ezzy_Wallet_Security::log_event(
            $type,
            'Admin updated wallet',
            array(
                'user_id'     => $this->user_id,
                'old_balance' => $old_balance,
                'new_balance' => $new_balance,
                'difference'  => $difference,
                'admin_id'    => get_current_user_id(),
            )
        );
    }

    /**
     * Log permission change transaction.
     *
     * @param int $withdrawals_disabled New withdrawal permission state.
     * @return void
     */
    private function log_permission_change( $withdrawals_disabled ) {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_transactions_table_name();

        $current_user = wp_get_current_user();
        $admin_name   = $current_user->user_login;

        $status_text = $withdrawals_disabled ? __( 'disabled', 'ezzy-wallet' ) : __( 'enabled', 'ezzy-wallet' );

        $description = sprintf(
            /* translators: 1: Admin username, 2: New permission status */
            __( 'Admin changed withdrawal permission by %1$s. Withdrawals %2$s.', 'ezzy-wallet' ),
            $admin_name,
            $status_text
        );

        $wpdb->insert(
            $table_name,
            array(
                'user_id'     => $this->user_id,
                'type'        => 'admin_permission_change',
                'amount'      => '0.00000000',
                'description' => $description,
                'status'      => 'system',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        // Log security event.
        Ezzy_Wallet_Security::log_event(
            'permission_change',
            'Admin changed withdrawal permission',
            array(
                'user_id'              => $this->user_id,
                'withdrawals_disabled' => $withdrawals_disabled,
                'admin_id'             => get_current_user_id(),
            )
        );
    }

    /**
     * Get errors.
     *
     * @return array Error messages.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get success message.
     *
     * @return string Success message.
     */
    public function get_success_message() {
        return $this->success_message;
    }

    /**
     * Get user ID.
     *
     * @return int User ID.
     */
    public function get_user_id() {
        return $this->user_id;
    }

    /**
     * Get WordPress user.
     *
     * @return WP_User WordPress user object.
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Get wallet account.
     *
     * @return array Wallet account data.
     */
    public function get_wallet() {
        return $this->wallet_account;
    }
}
