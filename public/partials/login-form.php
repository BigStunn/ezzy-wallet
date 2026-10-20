<?php
/**
 * Login Form template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$login_error = $this->get_login_error();
?>

<div class="ezzy-form-card">
    <div class="ezzy-form-header">
        <!-- Mini Logo in Form -->
        <div class="ezzy-form-logo">
            <span class="ezzy-form-logo-icon">
                <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="formLogoGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6"/>
                            <stop offset="100%" style="stop-color:#1d4ed8"/>
                        </linearGradient>
                    </defs>
                    <circle cx="20" cy="20" r="18" fill="url(#formLogoGrad2)"/>
                    <text x="20" y="26" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="Arial, sans-serif" font-style="italic">e</text>
                </svg>
            </span>
            <span class="ezzy-form-logo-text">ZZY WaLLet</span>
        </div>
        <h2><?php esc_html_e( 'Welcome Back', 'ezzy-wallet' ); ?></h2>
        <p><?php esc_html_e( 'Sign in to access your wallet', 'ezzy-wallet' ); ?></p>
    </div>

    <?php if ( ! empty( $login_error ) ) : ?>
        <div class="ezzy-form-error">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?php echo esc_html( $login_error ); ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="ezzy-form" id="ezzy-login-form">
        <?php wp_nonce_field( 'ezzy_wallet_login', 'ezzy_wallet_login_nonce' ); ?>

        <div class="ezzy-form-group">
            <label for="ezzy_login_username"><?php esc_html_e( 'Username or Email', 'ezzy-wallet' ); ?></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <input type="text" 
                       id="ezzy_login_username" 
                       name="ezzy_login_username" 
                       placeholder="<?php esc_attr_e( 'Enter username or email', 'ezzy-wallet' ); ?>"
                       value="<?php echo isset( $_POST['ezzy_login_username'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['ezzy_login_username'] ) ) ) : ''; ?>"
                       required
                       autocomplete="username" />
            </div>
        </div>

        <div class="ezzy-form-group">
            <label for="ezzy_login_password"><?php esc_html_e( 'Password', 'ezzy-wallet' ); ?></label>
            <div class="ezzy-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <input type="password" 
                       id="ezzy_login_password" 
                       name="ezzy_login_password" 
                       placeholder="<?php esc_attr_e( 'Enter your password', 'ezzy-wallet' ); ?>"
                       required
                       autocomplete="current-password" />
                <button type="button" class="ezzy-password-toggle" data-target="ezzy_login_password">
                    <svg class="ezzy-eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg class="ezzy-eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="ezzy-form-group">
            <button type="submit" name="ezzy_wallet_login" class="ezzy-btn ezzy-btn-submit">
                <?php esc_html_e( 'Sign In', 'ezzy-wallet' ); ?>
            </button>
        </div>

        <div class="ezzy-form-footer">
            <p>
                <?php esc_html_e( "Don't have an account?", 'ezzy-wallet' ); ?>
                <a href="#" class="ezzy-form-switch" data-show="signup"><?php esc_html_e( 'Create Account', 'ezzy-wallet' ); ?></a>
            </p>
        </div>
    </form>
</div>
