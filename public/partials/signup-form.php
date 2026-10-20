<?php
/**
 * Signup Form template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$signup_error = $this->get_signup_error();
?>

<div class="ezzy-form-card ezzy-form-card-signup">
    <div class="ezzy-form-header">
        <!-- Mini Logo in Form -->
        <div class="ezzy-form-logo">
            <span class="ezzy-form-logo-icon">
                <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="formLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6"/>
                            <stop offset="100%" style="stop-color:#1d4ed8"/>
                        </linearGradient>
                    </defs>
                    <circle cx="20" cy="20" r="18" fill="url(#formLogoGrad)"/>
                    <text x="20" y="26" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="Arial, sans-serif" font-style="italic">e</text>
                </svg>
            </span>
            <span class="ezzy-form-logo-text">ZZY WaLLet</span>
        </div>
        <h2><?php esc_html_e( 'Create Account', 'ezzy-wallet' ); ?></h2>
        <p><?php esc_html_e( 'Join eZZY Wallet and start your journey', 'ezzy-wallet' ); ?></p>
    </div>

    <?php if ( ! empty( $signup_error ) ) : ?>
        <div class="ezzy-form-error">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?php echo esc_html( $signup_error ); ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="ezzy-form" id="ezzy-signup-form">
        <?php wp_nonce_field( 'ezzy_wallet_signup', 'ezzy_wallet_signup_nonce' ); ?>

        <!-- Row: Username & First Name -->
        <div class="ezzy-form-row">
            <div class="ezzy-form-group">
                <label for="ezzy_username"><?php esc_html_e( 'Username', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
                <div class="ezzy-input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <input type="text" 
                           id="ezzy_username" 
                           name="ezzy_username" 
                           placeholder="<?php esc_attr_e( 'Choose a username', 'ezzy-wallet' ); ?>"
                           value="<?php echo isset( $_POST['ezzy_username'] ) ? esc_attr( sanitize_user( wp_unslash( $_POST['ezzy_username'] ) ) ) : ''; ?>"
                           required 
                           minlength="3"
                           autocomplete="username" />
                </div>
            </div>

            <div class="ezzy-form-group">
                <label for="ezzy_first_name"><?php esc_html_e( 'First Name', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
                <div class="ezzy-input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <input type="text" 
                           id="ezzy_first_name" 
                           name="ezzy_first_name" 
                           placeholder="<?php esc_attr_e( 'Your first name', 'ezzy-wallet' ); ?>"
                           value="<?php echo isset( $_POST['ezzy_first_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['ezzy_first_name'] ) ) ) : ''; ?>"
                           required
                           autocomplete="given-name" />
                </div>
            </div>
        </div>

        <!-- Last Name (optional) -->
        <div class="ezzy-form-group">
            <label for="ezzy_last_name"><?php esc_html_e( 'Last Name', 'ezzy-wallet' ); ?></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <input type="text" 
                       id="ezzy_last_name" 
                       name="ezzy_last_name" 
                       placeholder="<?php esc_attr_e( 'Your last name (optional)', 'ezzy-wallet' ); ?>"
                       value="<?php echo isset( $_POST['ezzy_last_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['ezzy_last_name'] ) ) ) : ''; ?>"
                       autocomplete="family-name" />
            </div>
        </div>

        <!-- Email -->
        <div class="ezzy-form-group">
            <label for="ezzy_email"><?php esc_html_e( 'Email Address', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <input type="email" 
                       id="ezzy_email" 
                       name="ezzy_email" 
                       placeholder="<?php esc_attr_e( 'your@email.com', 'ezzy-wallet' ); ?>"
                       value="<?php echo isset( $_POST['ezzy_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['ezzy_email'] ) ) ) : ''; ?>"
                       required
                       autocomplete="email" />
            </div>
        </div>

        <!-- Row: Phone & Date of Birth -->
        <div class="ezzy-form-row">
            <div class="ezzy-form-group">
                <label for="ezzy_phone"><?php esc_html_e( 'Phone Number', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
                <div class="ezzy-input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <input type="tel" 
                           id="ezzy_phone" 
                           name="ezzy_phone" 
                           placeholder="<?php esc_attr_e( '+1 234 567 8900', 'ezzy-wallet' ); ?>"
                           value="<?php echo isset( $_POST['ezzy_phone'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['ezzy_phone'] ) ) ) : ''; ?>"
                           required
                           autocomplete="tel" />
                </div>
            </div>

            <div class="ezzy-form-group">
                <label for="ezzy_dob"><?php esc_html_e( 'Date of Birth', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
                <div class="ezzy-input-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <input type="date" 
                           id="ezzy_dob" 
                           name="ezzy_dob" 
                           value="<?php echo isset( $_POST['ezzy_dob'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['ezzy_dob'] ) ) ) : ''; ?>"
                           required
                           max="<?php echo esc_attr( date( 'Y-m-d', strtotime( '-18 years' ) ) ); ?>"
                           autocomplete="bday" />
                </div>
                <p class="ezzy-input-hint"><?php esc_html_e( 'You must be at least 18 years old', 'ezzy-wallet' ); ?></p>
            </div>
        </div>

        <!-- Country -->
        <div class="ezzy-form-group">
            <label for="ezzy_country"><?php esc_html_e( 'Country', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
            <div class="ezzy-input-wrapper ezzy-select-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <select id="ezzy_country" name="ezzy_country" required>
                    <?php 
                    $selected_country = isset( $_POST['ezzy_country'] ) ? sanitize_text_field( wp_unslash( $_POST['ezzy_country'] ) ) : '';
                    Ezzy_Wallet_Countries::render_options( $selected_country ); 
                    ?>
                </select>
            </div>
        </div>

        <!-- Password -->
        <div class="ezzy-form-group">
            <label for="ezzy_password"><?php esc_html_e( 'Password', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input type="password" 
                       id="ezzy_password" 
                       name="ezzy_password" 
                       placeholder="<?php esc_attr_e( 'Create a strong password', 'ezzy-wallet' ); ?>"
                       required 
                       minlength="8"
                       autocomplete="new-password" />
                <button type="button" class="ezzy-password-toggle" data-target="ezzy_password">
                    <svg class="ezzy-eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg class="ezzy-eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <p class="ezzy-input-hint"><?php esc_html_e( 'Min 8 chars with uppercase, lowercase, number & special character', 'ezzy-wallet' ); ?></p>
        </div>

        <!-- Confirm Password -->
        <div class="ezzy-form-group">
            <label for="ezzy_confirm_password"><?php esc_html_e( 'Confirm Password', 'ezzy-wallet' ); ?> <span class="required">*</span></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <input type="password" 
                       id="ezzy_confirm_password" 
                       name="ezzy_confirm_password" 
                       placeholder="<?php esc_attr_e( 'Confirm your password', 'ezzy-wallet' ); ?>"
                       required
                       autocomplete="new-password" />
            </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="ezzy-form-group ezzy-form-checkbox">
            <label class="ezzy-checkbox-wrapper">
                <input type="checkbox" 
                       id="ezzy_terms" 
                       name="ezzy_terms" 
                       value="1"
                       required />
                <span class="ezzy-checkbox-custom"></span>
                <span class="ezzy-checkbox-label">
                    <?php 
                    printf(
                        /* translators: %1$s: Terms link, %2$s: Privacy link */
                        esc_html__( 'I agree to the %1$s and %2$s', 'ezzy-wallet' ),
                        '<a href="#" class="ezzy-terms-link" target="_blank">' . esc_html__( 'Terms of Service', 'ezzy-wallet' ) . '</a>',
                        '<a href="#" class="ezzy-privacy-link" target="_blank">' . esc_html__( 'Privacy Policy', 'ezzy-wallet' ) . '</a>'
                    );
                    ?>
                    <span class="required">*</span>
                </span>
            </label>
        </div>

        <div class="ezzy-form-group">
            <button type="submit" name="ezzy_wallet_signup" class="ezzy-btn ezzy-btn-submit">
                <?php esc_html_e( 'Create Account', 'ezzy-wallet' ); ?>
            </button>
        </div>

        <div class="ezzy-form-footer">
            <p>
                <?php esc_html_e( 'Already have an account?', 'ezzy-wallet' ); ?>
                <a href="#" class="ezzy-form-switch" data-show="login"><?php esc_html_e( 'Sign In', 'ezzy-wallet' ); ?></a>
            </p>
        </div>
    </form>
</div>
