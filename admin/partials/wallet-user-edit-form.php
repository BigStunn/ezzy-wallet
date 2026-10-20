<?php
/**
 * Wallet User Edit form admin page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get data from the edit handler.
$user    = $this->user;
$wallet  = $this->wallet_account;
$errors  = $this->errors;
$success = $this->success_message;

// Get currency symbol.
$settings = get_option( 'ezzy_wallet_settings', array() );
$currency = isset( $settings['default_currency'] ) ? $settings['default_currency'] : 'USD';
$symbols  = array(
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
);
$symbol   = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '$';

// Back URL.
$back_url = admin_url( 'admin.php?page=ezzy-wallet-users' );

// Get user meta fields.
$user_phone   = get_user_meta( $user->ID, 'ezzy_wallet_phone', true );
$user_dob     = get_user_meta( $user->ID, 'ezzy_wallet_dob', true );
$user_country = get_user_meta( $user->ID, 'ezzy_wallet_country', true );
$user_address = get_user_meta( $user->ID, 'ezzy_wallet_address', true );
$terms_accepted = get_user_meta( $user->ID, 'ezzy_wallet_terms_accepted', true );

// Get custom avatar or fallback to Gravatar
$custom_avatar = get_user_meta( $user->ID, 'ezzy_wallet_profile_image', true );

// Get country name.
$country_name = '';
if ( ! empty( $user_country ) && class_exists( 'Ezzy_Wallet_Countries' ) ) {
    $country_name = Ezzy_Wallet_Countries::get_country_name( $user_country );
}

// Format date of birth.
$dob_formatted = '';
if ( ! empty( $user_dob ) ) {
    $dob_formatted = date_i18n( get_option( 'date_format' ), strtotime( $user_dob ) );
}
?>

<div class="wrap ezzy-wallet-admin-wrap">
    <h1 class="ezzy-wallet-admin-title">
        <span class="dashicons dashicons-money-alt"></span>
        <?php esc_html_e( 'Edit Wallet Account', 'ezzy-wallet' ); ?>
    </h1>

    <div class="ezzy-wallet-admin-header">
        <a href="<?php echo esc_url( $back_url ); ?>" class="ezzy-wallet-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php esc_html_e( 'Back to Wallet Users', 'ezzy-wallet' ); ?>
        </a>
    </div>

    <div class="ezzy-wallet-settings-content ezzy-wallet-edit-content">
        <?php if ( ! empty( $errors ) ) : ?>
            <div class="notice notice-error">
                <?php foreach ( $errors as $error ) : ?>
                    <p><?php echo esc_html( $error ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $success ) ) : ?>
            <div class="notice notice-success">
                <p><?php echo esc_html( $success ); ?></p>
            </div>
        <?php endif; ?>

        <div class="ezzy-wallet-edit-grid">
            <!-- User Info Section -->
            <div class="ezzy-wallet-edit-section user-info-section">
                <h2>
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e( 'User Information', 'ezzy-wallet' ); ?>
                </h2>

                <div class="user-info-card">
                    <div class="user-avatar">
                        <?php if ( ! empty( $custom_avatar ) ) : ?>
                            <img src="<?php echo esc_url( $custom_avatar ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" width="80" height="80" style="border-radius: 50%; object-fit: cover;"/>
                        <?php else : ?>
                            <?php echo get_avatar( $user->ID, 80 ); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <table class="user-info-table">
                            <tr>
                                <th><?php esc_html_e( 'User ID:', 'ezzy-wallet' ); ?></th>
                                <td><?php echo esc_html( $user->ID ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Username:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <strong><?php echo esc_html( $user->user_login ); ?></strong>
                                    <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" class="edit-user-link" target="_blank">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'First Name:', 'ezzy-wallet' ); ?></th>
                                <td><?php echo esc_html( $user->first_name ?: '—' ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Last Name:', 'ezzy-wallet' ); ?></th>
                                <td><?php echo esc_html( $user->last_name ?: '—' ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Display Name:', 'ezzy-wallet' ); ?></th>
                                <td><?php echo esc_html( $user->display_name ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Email:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <a href="mailto:<?php echo esc_attr( $user->user_email ); ?>">
                                        <?php echo esc_html( $user->user_email ); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Phone:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $user_phone ) ) : ?>
                                        <a href="tel:<?php echo esc_attr( $user_phone ); ?>">
                                            <?php echo esc_html( $user_phone ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="ezzy-not-set">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Date of Birth:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $user_dob ) ) : ?>
                                        <?php echo esc_html( $dob_formatted ); ?>
                                        <span class="ezzy-dob-age">
                                            (<?php 
                                            $age = floor( ( time() - strtotime( $user_dob ) ) / 31556926 );
                                            /* translators: %d: Age in years */
                                            printf( esc_html__( '%d years old', 'ezzy-wallet' ), $age );
                                            ?>)
                                        </span>
                                    <?php else : ?>
                                        <span class="ezzy-not-set">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Country:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $country_name ) ) : ?>
                                        <?php echo esc_html( $country_name ); ?>
                                        <span class="ezzy-country-code">(<?php echo esc_html( $user_country ); ?>)</span>
                                    <?php else : ?>
                                        <span class="ezzy-not-set">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Address:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <?php if ( ! empty( $user_address ) ) : ?>
                                        <?php echo esc_html( $user_address ); ?>
                                    <?php else : ?>
                                        <span class="ezzy-not-set">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Password:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <span class="ezzy-password-masked">••••••••••</span>
                                    <span class="ezzy-password-info"><?php esc_html_e( '(encrypted)', 'ezzy-wallet' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Registered:', 'ezzy-wallet' ); ?></th>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $user->user_registered ) ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Role:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <?php
                                    $roles = array_map(
                                        function ( $role ) {
                                            return ucfirst( $role );
                                        },
                                        $user->roles
                                    );
                                    echo esc_html( implode( ', ', $roles ) );
                                    ?>
                                </td>
                            </tr>
                            <?php if ( ! empty( $terms_accepted ) ) : ?>
                            <tr>
                                <th><?php esc_html_e( 'Terms Accepted:', 'ezzy-wallet' ); ?></th>
                                <td>
                                    <span class="ezzy-terms-accepted">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $terms_accepted ) ) ); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Account Timestamps -->
                <div class="account-timestamps">
                    <p>
                        <strong><?php esc_html_e( 'Wallet Created:', 'ezzy-wallet' ); ?></strong>
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $wallet['created_at'] ) ) ); ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Last Updated:', 'ezzy-wallet' ); ?></strong>
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $wallet['updated_at'] ) ) ); ?>
                    </p>
                </div>
            </div>

            <!-- Wallet Edit Section -->
            <div class="ezzy-wallet-edit-section wallet-edit-section">
                <h2>
                    <span class="dashicons dashicons-portfolio"></span>
                    <?php esc_html_e( 'Wallet Account', 'ezzy-wallet' ); ?>
                </h2>

                <form method="post" action="" class="ezzy-wallet-edit-form">
                    <?php wp_nonce_field( 'ezzy_wallet_save_user', 'ezzy_wallet_user_nonce' ); ?>
                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>" />

                    <table class="form-table ezzy-wallet-form-table">
                        <tr>
                            <th scope="row">
                                <label for="balance"><?php esc_html_e( 'Balance', 'ezzy-wallet' ); ?></label>
                            </th>
                            <td>
                                <div class="currency-input-wrapper">
                                    <span class="currency-symbol"><?php echo esc_html( $symbol ); ?></span>
                                    <input type="text"
                                           id="balance"
                                           name="balance"
                                           value="<?php echo esc_attr( number_format( (float) $wallet['balance'], 8, '.', '' ) ); ?>"
                                           class="regular-text currency-input"
                                           pattern="[0-9]*\.?[0-9]*"
                                           inputmode="decimal" />
                                </div>
                                <p class="description"><?php esc_html_e( 'Current wallet balance. Use decimal format (e.g., 100.50).', 'ezzy-wallet' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="total_deposit"><?php esc_html_e( 'Total Deposit', 'ezzy-wallet' ); ?></label>
                            </th>
                            <td>
                                <div class="currency-input-wrapper">
                                    <span class="currency-symbol"><?php echo esc_html( $symbol ); ?></span>
                                    <input type="text"
                                           id="total_deposit"
                                           name="total_deposit"
                                           value="<?php echo esc_attr( number_format( (float) $wallet['total_deposit'], 8, '.', '' ) ); ?>"
                                           class="regular-text currency-input"
                                           pattern="[0-9]*\.?[0-9]*"
                                           inputmode="decimal" />
                                </div>
                                <p class="description"><?php esc_html_e( 'Total amount deposited all-time.', 'ezzy-wallet' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="total_transfer"><?php esc_html_e( 'Total Transfer', 'ezzy-wallet' ); ?></label>
                            </th>
                            <td>
                                <div class="currency-input-wrapper">
                                    <span class="currency-symbol"><?php echo esc_html( $symbol ); ?></span>
                                    <input type="text"
                                           id="total_transfer"
                                           name="total_transfer"
                                           value="<?php echo esc_attr( number_format( (float) $wallet['total_transfer'], 8, '.', '' ) ); ?>"
                                           class="regular-text currency-input"
                                           pattern="[0-9]*\.?[0-9]*"
                                           inputmode="decimal" />
                                </div>
                                <p class="description"><?php esc_html_e( 'Total amount transferred all-time.', 'ezzy-wallet' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="total_withdrawal"><?php esc_html_e( 'Total Withdrawal', 'ezzy-wallet' ); ?></label>
                            </th>
                            <td>
                                <div class="currency-input-wrapper">
                                    <span class="currency-symbol"><?php echo esc_html( $symbol ); ?></span>
                                    <input type="text"
                                           id="total_withdrawal"
                                           name="total_withdrawal"
                                           value="<?php echo esc_attr( number_format( (float) $wallet['total_withdrawal'], 8, '.', '' ) ); ?>"
                                           class="regular-text currency-input"
                                           pattern="[0-9]*\.?[0-9]*"
                                           inputmode="decimal" />
                                </div>
                                <p class="description"><?php esc_html_e( 'Total amount withdrawn all-time.', 'ezzy-wallet' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Withdrawal Permission', 'ezzy-wallet' ); ?>
                            </th>
                            <td>
                                <?php
                                $withdrawal_setting = (int) $wallet['withdrawals_disabled'];
                                $global_enabled = Ezzy_Wallet_Admin::is_withdrawals_enabled_globally();
                                ?>
                                <select id="withdrawals_disabled" name="withdrawals_disabled" class="ezzy-wallet-select">
                                    <option value="0" <?php selected( $withdrawal_setting, 0 ); ?>>
                                        <?php 
                                        if ( $global_enabled ) {
                                            esc_html_e( 'Follow Global Setting (Currently: Enabled)', 'ezzy-wallet' );
                                        } else {
                                            esc_html_e( 'Follow Global Setting (Currently: Disabled)', 'ezzy-wallet' );
                                        }
                                        ?>
                                    </option>
                                    <option value="1" <?php selected( $withdrawal_setting, 1 ); ?>>
                                        <?php esc_html_e( 'Disable Withdrawals (Override)', 'ezzy-wallet' ); ?>
                                    </option>
                                    <option value="2" <?php selected( $withdrawal_setting, 2 ); ?>>
                                        <?php esc_html_e( 'Enable Withdrawals (Override)', 'ezzy-wallet' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Control withdrawal permission for this user. Override options ignore the global setting.', 'ezzy-wallet' ); ?>
                                </p>
                                <?php if ( $withdrawal_setting === 1 ) : ?>
                                    <p class="withdrawal-warning">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php esc_html_e( 'Withdrawals are individually DISABLED for this user.', 'ezzy-wallet' ); ?>
                                    </p>
                                <?php elseif ( $withdrawal_setting === 2 ) : ?>
                                    <p class="withdrawal-enabled">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php esc_html_e( 'Withdrawals are individually ENABLED for this user (overrides global).', 'ezzy-wallet' ); ?>
                                    </p>
                                <?php elseif ( ! $global_enabled ) : ?>
                                    <p class="withdrawal-warning">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php esc_html_e( 'Global withdrawals are disabled. This user cannot withdraw.', 'ezzy-wallet' ); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <div class="ezzy-wallet-edit-actions">
                        <button type="submit" name="ezzy_wallet_save_user" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e( 'Save Changes', 'ezzy-wallet' ); ?>
                        </button>
                        <a href="<?php echo esc_url( $back_url ); ?>" class="button button-secondary button-large">
                            <?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?>
                        </a>
                    </div>

                    <div class="ezzy-wallet-edit-notice">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'All balance adjustments are logged in the transactions table for audit purposes.', 'ezzy-wallet' ); ?>
                    </div>
                </form>
            </div>
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
