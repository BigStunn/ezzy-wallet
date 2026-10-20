<?php
/**
 * Notifications Settings page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get errors and success message from handler.
$errors          = $this->get_errors();
$success_message = $this->get_success_message();
?>

<div class="wrap ezzy-wallet-admin">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-megaphone" style="font-size: 30px; width: 30px; height: 30px; margin-right: 10px;"></span>
        <?php esc_html_e( 'Notifications', 'ezzy-wallet' ); ?>
    </h1>
    <p class="description"><?php esc_html_e( 'Send notifications and messages to all wallet users. These will appear in the Messages section of the user dashboard.', 'ezzy-wallet' ); ?></p>

    <hr class="wp-header-end">

    <?php if ( ! empty( $errors ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <?php foreach ( $errors as $error ) : ?>
                <p><?php echo esc_html( $error ); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $success_message ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $success_message ); ?></p>
        </div>
    <?php endif; ?>

    <div class="ezzy-wallet-notifications-wrapper" style="margin-top: 20px;">
        
        <!-- Add New Notification Form -->
        <div class="ezzy-wallet-add-notification" style="margin-bottom: 30px;">
            <div class="postbox" style="max-width: 100%;">
                <h2 class="hndle" style="padding: 12px 15px; margin: 0; border-bottom: 1px solid #ccd0d4; cursor: default;">
                    <span class="dashicons dashicons-plus-alt" style="margin-right: 8px;"></span>
                    <?php esc_html_e( 'Add New Notification', 'ezzy-wallet' ); ?>
                </h2>
                <div class="inside" style="padding: 20px;">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ezzy_wallet_add_notification', 'ezzy_wallet_notification_nonce' ); ?>
                        
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row" style="width: 150px;">
                                    <label for="notification_title"><?php esc_html_e( 'Title', 'ezzy-wallet' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="notification_title" name="notification_title" class="regular-text" style="width: 100%; max-width: 400px;" required placeholder="<?php esc_attr_e( 'Enter notification title...', 'ezzy-wallet' ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="notification_message"><?php esc_html_e( 'Message', 'ezzy-wallet' ); ?></label>
                                </th>
                                <td>
                                    <textarea id="notification_message" name="notification_message" rows="4" style="width: 100%; max-width: 500px;" required placeholder="<?php esc_attr_e( 'Enter notification message...', 'ezzy-wallet' ); ?>"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="notification_type"><?php esc_html_e( 'Type', 'ezzy-wallet' ); ?></label>
                                </th>
                                <td>
                                    <select id="notification_type" name="notification_type" style="min-width: 200px;">
                                        <option value="info"><?php esc_html_e( 'Info (Blue)', 'ezzy-wallet' ); ?></option>
                                        <option value="success"><?php esc_html_e( 'Success (Green)', 'ezzy-wallet' ); ?></option>
                                        <option value="warning"><?php esc_html_e( 'Warning (Yellow)', 'ezzy-wallet' ); ?></option>
                                        <option value="alert"><?php esc_html_e( 'Alert (Red)', 'ezzy-wallet' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose the notification style/color.', 'ezzy-wallet' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit" style="margin-top: 10px; padding-top: 0;">
                            <button type="submit" name="ezzy_wallet_add_notification" class="button button-primary button-large">
                                <span class="dashicons dashicons-bell" style="margin-top: 4px; margin-right: 5px;"></span>
                                <?php esc_html_e( 'Send Notification', 'ezzy-wallet' ); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Notifications List -->
        <div class="ezzy-wallet-notifications-list">
            <div class="postbox" style="max-width: 100%;">
                <h2 class="hndle" style="padding: 12px 15px; margin: 0; border-bottom: 1px solid #ccd0d4; cursor: default;">
                    <span class="dashicons dashicons-list-view" style="margin-right: 8px;"></span>
                    <?php esc_html_e( 'All Notifications', 'ezzy-wallet' ); ?>
                    <span style="font-weight: normal; font-size: 13px; color: #666; margin-left: 10px;">
                        (<?php echo count( $notifications ); ?> <?php echo count( $notifications ) === 1 ? esc_html__( 'notification', 'ezzy-wallet' ) : esc_html__( 'notifications', 'ezzy-wallet' ); ?>)
                    </span>
                </h2>
                <div class="inside" style="padding: 0; margin: 0;">
                    <?php if ( empty( $notifications ) ) : ?>
                        <div style="padding: 40px; text-align: center; color: #666;">
                            <span class="dashicons dashicons-info" style="font-size: 48px; width: 48px; height: 48px; color: #ccc;"></span>
                            <p style="margin-top: 15px; font-size: 14px;"><?php esc_html_e( 'No notifications yet. Create your first notification above.', 'ezzy-wallet' ); ?></p>
                        </div>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped" style="border: none;">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 20%;"><?php esc_html_e( 'Title', 'ezzy-wallet' ); ?></th>
                                    <th scope="col" style="width: 40%;"><?php esc_html_e( 'Message', 'ezzy-wallet' ); ?></th>
                                    <th scope="col" style="width: 10%;"><?php esc_html_e( 'Type', 'ezzy-wallet' ); ?></th>
                                    <th scope="col" style="width: 10%;"><?php esc_html_e( 'Status', 'ezzy-wallet' ); ?></th>
                                    <th scope="col" style="width: 20%;"><?php esc_html_e( 'Actions', 'ezzy-wallet' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $notifications as $notification ) : ?>
                                    <?php
                                    $type_colors = array(
                                        'info'    => '#2271b1',
                                        'success' => '#00a32a',
                                        'warning' => '#dba617',
                                        'alert'   => '#d63638',
                                    );
                                    $type_color = isset( $type_colors[ $notification['type'] ] ) ? $type_colors[ $notification['type'] ] : '#2271b1';
                                    $is_active  = (bool) $notification['is_active'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $notification['title'] ); ?></strong>
                                            <br>
                                            <small style="color: #666;">
                                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $notification['created_at'] ) ) ); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span style="display: block; max-height: 40px; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo esc_html( wp_trim_words( $notification['message'], 20, '...' ) ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #fff; background: <?php echo esc_attr( $type_color ); ?>;">
                                                <?php echo esc_html( ucfirst( $notification['type'] ) ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ( $is_active ) : ?>
                                                <span style="color: #00a32a; font-weight: 600;">
                                                    <span class="dashicons dashicons-yes-alt" style="font-size: 16px;"></span>
                                                    <?php esc_html_e( 'Active', 'ezzy-wallet' ); ?>
                                                </span>
                                            <?php else : ?>
                                                <span style="color: #666;">
                                                    <span class="dashicons dashicons-hidden" style="font-size: 16px;"></span>
                                                    <?php esc_html_e( 'Inactive', 'ezzy-wallet' ); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Toggle Status -->
                                            <form method="post" action="" style="display: inline-block; margin-right: 5px;">
                                                <?php wp_nonce_field( 'ezzy_wallet_toggle_notification', 'ezzy_wallet_notification_nonce' ); ?>
                                                <input type="hidden" name="notification_id" value="<?php echo esc_attr( $notification['id'] ); ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $is_active ? '0' : '1'; ?>">
                                                <button type="submit" name="ezzy_wallet_toggle_notification" class="button button-small" title="<?php echo $is_active ? esc_attr__( 'Deactivate', 'ezzy-wallet' ) : esc_attr__( 'Activate', 'ezzy-wallet' ); ?>">
                                                    <?php if ( $is_active ) : ?>
                                                        <span class="dashicons dashicons-hidden" style="font-size: 14px; line-height: 1.8;"></span>
                                                        <?php esc_html_e( 'Deactivate', 'ezzy-wallet' ); ?>
                                                    <?php else : ?>
                                                        <span class="dashicons dashicons-visibility" style="font-size: 14px; line-height: 1.8;"></span>
                                                        <?php esc_html_e( 'Activate', 'ezzy-wallet' ); ?>
                                                    <?php endif; ?>
                                                </button>
                                            </form>

                                            <!-- Delete -->
                                            <form method="post" action="" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this notification?', 'ezzy-wallet' ); ?>');">
                                                <?php wp_nonce_field( 'ezzy_wallet_delete_notification', 'ezzy_wallet_notification_nonce' ); ?>
                                                <input type="hidden" name="notification_id" value="<?php echo esc_attr( $notification['id'] ); ?>">
                                                <button type="submit" name="ezzy_wallet_delete_notification" class="button button-small" style="color: #d63638;" title="<?php esc_attr_e( 'Delete', 'ezzy-wallet' ); ?>">
                                                    <span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 1.8;"></span>
                                                    <?php esc_html_e( 'Delete', 'ezzy-wallet' ); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
