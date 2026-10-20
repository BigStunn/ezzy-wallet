<?php
/**
 * Notifications handler for eZZY Wallet.
 *
 * Handles admin notifications/messages to all users.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Notifications
 *
 * Manages the notifications functionality for eZZY Wallet.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Notifications {

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
        // Process form submissions.
        if ( isset( $_POST['ezzy_wallet_add_notification'] ) ) {
            $this->process_add_notification();
        }

        if ( isset( $_POST['ezzy_wallet_delete_notification'] ) ) {
            $this->process_delete_notification();
        }

        if ( isset( $_POST['ezzy_wallet_toggle_notification'] ) ) {
            $this->process_toggle_notification();
        }
    }

    /**
     * Render the notifications page.
     *
     * @return void
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'ezzy-wallet' ) );
        }

        $notifications = $this->get_all_notifications();

        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/notifications-settings.php';
    }

    /**
     * Get all notifications.
     *
     * @return array List of notifications.
     */
    public function get_all_notifications() {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_notifications_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $notifications = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC",
            ARRAY_A
        );

        return $notifications ? $notifications : array();
    }

    /**
     * Get active notifications for users.
     *
     * @return array List of active notifications.
     */
    public static function get_active_notifications() {
        global $wpdb;

        $table_name = Ezzy_Wallet_DB::get_notifications_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $notifications = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY created_at DESC",
            ARRAY_A
        );

        return $notifications ? $notifications : array();
    }

    /**
     * Process add notification form.
     *
     * @return void
     */
    private function process_add_notification() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_notification_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_notification_nonce'] ) ), 'ezzy_wallet_add_notification' ) ) {
            $this->errors[] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Verify capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->errors[] = __( 'You do not have permission to perform this action.', 'ezzy-wallet' );
            return;
        }

        // Sanitize inputs.
        $title   = isset( $_POST['notification_title'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_title'] ) ) : '';
        $message = isset( $_POST['notification_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notification_message'] ) ) : '';
        $type    = isset( $_POST['notification_type'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_type'] ) ) : 'info';

        // Validate inputs.
        if ( empty( $title ) ) {
            $this->errors[] = __( 'Notification title is required.', 'ezzy-wallet' );
            return;
        }

        if ( empty( $message ) ) {
            $this->errors[] = __( 'Notification message is required.', 'ezzy-wallet' );
            return;
        }

        // Validate type.
        $allowed_types = array( 'info', 'success', 'warning', 'alert' );
        if ( ! in_array( $type, $allowed_types, true ) ) {
            $type = 'info';
        }

        // Insert notification.
        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_notifications_table_name();

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'is_active'  => 1,
                'created_by' => get_current_user_id(),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            $this->errors[] = __( 'Failed to add notification. Please try again.', 'ezzy-wallet' );
            return;
        }

        $this->success_message = __( 'Notification added successfully.', 'ezzy-wallet' );
    }

    /**
     * Process delete notification.
     *
     * @return void
     */
    private function process_delete_notification() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_notification_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_notification_nonce'] ) ), 'ezzy_wallet_delete_notification' ) ) {
            $this->errors[] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Verify capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->errors[] = __( 'You do not have permission to perform this action.', 'ezzy-wallet' );
            return;
        }

        $notification_id = isset( $_POST['notification_id'] ) ? absint( $_POST['notification_id'] ) : 0;

        if ( empty( $notification_id ) ) {
            $this->errors[] = __( 'Invalid notification ID.', 'ezzy-wallet' );
            return;
        }

        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_notifications_table_name();

        $deleted = $wpdb->delete(
            $table_name,
            array( 'id' => $notification_id ),
            array( '%d' )
        );

        if ( false === $deleted ) {
            $this->errors[] = __( 'Failed to delete notification. Please try again.', 'ezzy-wallet' );
            return;
        }

        $this->success_message = __( 'Notification deleted successfully.', 'ezzy-wallet' );
    }

    /**
     * Process toggle notification status.
     *
     * @return void
     */
    private function process_toggle_notification() {
        // Verify nonce.
        if ( ! isset( $_POST['ezzy_wallet_notification_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_wallet_notification_nonce'] ) ), 'ezzy_wallet_toggle_notification' ) ) {
            $this->errors[] = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
            return;
        }

        // Verify capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            $this->errors[] = __( 'You do not have permission to perform this action.', 'ezzy-wallet' );
            return;
        }

        $notification_id = isset( $_POST['notification_id'] ) ? absint( $_POST['notification_id'] ) : 0;
        $new_status      = isset( $_POST['new_status'] ) ? absint( $_POST['new_status'] ) : 0;

        if ( empty( $notification_id ) ) {
            $this->errors[] = __( 'Invalid notification ID.', 'ezzy-wallet' );
            return;
        }

        global $wpdb;
        $table_name = Ezzy_Wallet_DB::get_notifications_table_name();

        $updated = $wpdb->update(
            $table_name,
            array( 'is_active' => $new_status ),
            array( 'id' => $notification_id ),
            array( '%d' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            $this->errors[] = __( 'Failed to update notification status. Please try again.', 'ezzy-wallet' );
            return;
        }

        $status_text = $new_status ? __( 'activated', 'ezzy-wallet' ) : __( 'deactivated', 'ezzy-wallet' );
        $this->success_message = sprintf( __( 'Notification %s successfully.', 'ezzy-wallet' ), $status_text );
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
}
