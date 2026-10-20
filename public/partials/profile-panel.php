<?php
/**
 * User Profile Panel template for eZZY Wallet Dashboard.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current user data.
$current_user = wp_get_current_user();

// Get user profile data.
$profile = Ezzy_Wallet_Data_Service::get_user_profile( $current_user->ID );

// Get additional meta.
$user_phone   = get_user_meta( $current_user->ID, 'ezzy_wallet_phone', true );
$user_dob     = get_user_meta( $current_user->ID, 'ezzy_wallet_dob', true );
$user_country = get_user_meta( $current_user->ID, 'ezzy_wallet_country', true );
$user_address = get_user_meta( $current_user->ID, 'ezzy_wallet_address', true );

// Get custom avatar or fallback to Gravatar
$custom_avatar = get_user_meta( $current_user->ID, 'ezzy_wallet_profile_image', true );
if ( ! empty( $custom_avatar ) ) {
    $profile_avatar_url = $custom_avatar;
} else {
    $profile_avatar_url = get_avatar_url( $current_user->ID, array( 'size' => 100 ) );
}

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

<div class="ezzy-profile-panel" id="ezzy-profile-panel">
    <div class="ezzy-profile-header">
        <div class="ezzy-profile-avatar ezzy-avatar-upload" id="ezzy-profile-avatar" title="<?php esc_attr_e( 'Click to change profile picture', 'ezzy-wallet' ); ?>">
            <img src="<?php echo esc_url( $profile_avatar_url ); ?>" alt="<?php echo esc_attr( $current_user->display_name ); ?>" id="ezzy-profile-avatar-img"/>
            <button type="button" class="ezzy-avatar-edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
            </button>
            <input type="file" id="ezzy-profile-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;"/>
        </div>
        <div class="ezzy-profile-name">
            <h2><?php echo esc_html( $current_user->display_name ); ?></h2>
            <p class="ezzy-profile-username">@<?php echo esc_html( $current_user->user_login ); ?></p>
        </div>
    </div>

    <div class="ezzy-profile-content">
        <!-- Success/Error Messages -->
        <div class="ezzy-profile-message ezzy-profile-success" id="ezzy-profile-success" style="display: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span></span>
        </div>
        <div class="ezzy-profile-message ezzy-profile-error" id="ezzy-profile-error" style="display: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span></span>
        </div>

        <form id="ezzy-profile-form" class="ezzy-profile-form">
            <?php wp_nonce_field( 'ezzy_wallet_frontend_nonce', 'ezzy_profile_nonce' ); ?>

            <!-- Username (read-only) -->
            <div class="ezzy-profile-field" data-field="username">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Username', 'ezzy-wallet' ); ?></label>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $current_user->user_login ); ?></span>
                        <span class="ezzy-field-locked" title="<?php esc_attr_e( 'Username cannot be changed', 'ezzy-wallet' ); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>

            <!-- First Name -->
            <div class="ezzy-profile-field" data-field="first_name">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'First Name', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="first_name">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $current_user->first_name ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="text" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" placeholder="<?php esc_attr_e( 'Enter first name', 'ezzy-wallet' ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="first_name"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Last Name -->
            <div class="ezzy-profile-field" data-field="last_name">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Last Name', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="last_name">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $current_user->last_name ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="text" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" placeholder="<?php esc_attr_e( 'Enter last name', 'ezzy-wallet' ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="last_name"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Display Name -->
            <div class="ezzy-profile-field" data-field="display_name">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Display Name', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="display_name">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $current_user->display_name ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="text" name="display_name" value="<?php echo esc_attr( $current_user->display_name ); ?>" placeholder="<?php esc_attr_e( 'Enter display name', 'ezzy-wallet' ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="display_name"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="ezzy-profile-field" data-field="email">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Email Address', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="email">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $current_user->user_email ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="email" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" placeholder="<?php esc_attr_e( 'Enter email address', 'ezzy-wallet' ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="email"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Phone -->
            <div class="ezzy-profile-field" data-field="phone">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Phone Number', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="phone">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $user_phone ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="tel" name="phone" value="<?php echo esc_attr( $user_phone ); ?>" placeholder="<?php esc_attr_e( 'Enter phone number', 'ezzy-wallet' ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="phone"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="ezzy-profile-field" data-field="dob">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Date of Birth', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="dob">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $dob_formatted ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <input type="date" name="dob" value="<?php echo esc_attr( $user_dob ); ?>" max="<?php echo esc_attr( date( 'Y-m-d', strtotime( '-18 years' ) ) ); ?>" />
                        <button type="button" class="ezzy-save-field-btn" data-field="dob"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Country -->
            <div class="ezzy-profile-field" data-field="country">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Country', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="country">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $country_name ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <select name="country">
                            <?php Ezzy_Wallet_Countries::render_options( $user_country ); ?>
                        </select>
                        <button type="button" class="ezzy-save-field-btn" data-field="country"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="ezzy-profile-field" data-field="address">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Address', 'ezzy-wallet' ); ?></label>
                    <button type="button" class="ezzy-edit-btn" data-field="address">
                        <span class="ezzy-edit-text"><?php esc_html_e( 'Edit', 'ezzy-wallet' ); ?></span>
                        <span class="ezzy-cancel-text" style="display:none;"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></span>
                    </button>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( $user_address ?: '—' ); ?></span>
                    </div>
                    <div class="ezzy-profile-edit" style="display:none;">
                        <textarea name="address" rows="2" placeholder="<?php esc_attr_e( 'Enter your address', 'ezzy-wallet' ); ?>"><?php echo esc_textarea( $user_address ); ?></textarea>
                        <button type="button" class="ezzy-save-field-btn" data-field="address"><?php esc_html_e( 'Save', 'ezzy-wallet' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Registered Date (read-only) -->
            <div class="ezzy-profile-field" data-field="registered">
                <div class="ezzy-profile-field-header">
                    <label><?php esc_html_e( 'Member Since', 'ezzy-wallet' ); ?></label>
                </div>
                <div class="ezzy-profile-field-content">
                    <div class="ezzy-profile-value">
                        <span class="ezzy-field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </span>
                        <span class="ezzy-field-text"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $current_user->user_registered ) ) ); ?></span>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>
