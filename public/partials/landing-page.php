<?php
/**
 * Wallet Landing Page template.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$signup_error = $this->get_signup_error();
$login_error  = $this->get_login_error();
$show_signup  = ! empty( $signup_error );
$show_login   = ! empty( $login_error );
?>

<div class="ezzy-wallet-landing">
    <!-- Hero Section -->
    <section class="ezzy-hero-section">
        <!-- Gradient Background Shapes -->
        <div class="ezzy-bg-shape ezzy-bg-shape-1"></div>
        <div class="ezzy-bg-shape ezzy-bg-shape-2"></div>
        <div class="ezzy-bg-shape ezzy-bg-shape-3"></div>
        
        <div class="ezzy-hero-container">
            <div class="ezzy-hero-content">
                <!-- Logo -->
                <div class="ezzy-logo">
                    <div class="ezzy-logo-main">
                        <span class="ezzy-logo-icon">
                            <svg viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="ezzyGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#3b82f6"/>
                                        <stop offset="100%" style="stop-color:#1d4ed8"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="25" cy="25" r="23" fill="url(#ezzyGradient)"/>
                                <text x="25" y="33" text-anchor="middle" fill="white" font-size="26" font-weight="bold" font-family="Arial, sans-serif" font-style="italic">e</text>
                            </svg>
                        </span>
                        <span class="ezzy-logo-text">
                            <span class="ezzy-logo-zzy">ZZY</span>
                            <span class="ezzy-logo-wallet">WaLLet</span>
                        </span>
                    </div>
                    <span class="ezzy-logo-tagline"><?php esc_html_e( 'by Big Stunnz', 'ezzy-wallet' ); ?></span>
                </div>

                <h1 class="ezzy-hero-title"><?php esc_html_e( 'Digital Wallet', 'ezzy-wallet' ); ?></h1>
                <p class="ezzy-hero-subtitle">
                    <?php esc_html_e( 'Experience seamless digital transactions with our secure and modern wallet platform. Send, receive, and manage your funds with ease.', 'ezzy-wallet' ); ?>
                </p>
                
                <div class="ezzy-hero-buttons">
                    <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-show-signup">
                        <?php esc_html_e( 'Create Account', 'ezzy-wallet' ); ?>
                    </button>
                    <button type="button" class="ezzy-btn ezzy-btn-secondary" id="ezzy-show-login">
                        <?php esc_html_e( 'Sign In', 'ezzy-wallet' ); ?>
                    </button>
                </div>

                <p class="ezzy-hero-footer"><?php esc_html_e( 'All rights reserved', 'ezzy-wallet' ); ?></p>
            </div>

            <div class="ezzy-hero-visual">
                <!-- 3D Wallet Illustration -->
                <div class="ezzy-wallet-illustration">
                    <!-- Phone -->
                    <div class="ezzy-phone">
                        <div class="ezzy-phone-screen">
                            <div class="ezzy-phone-header">
                                <div class="ezzy-phone-notch"></div>
                            </div>
                            <div class="ezzy-phone-content">
                                <div class="ezzy-phone-coin">
                                    <svg viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="coinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#fbbf24"/>
                                                <stop offset="50%" style="stop-color:#f59e0b"/>
                                                <stop offset="100%" style="stop-color:#d97706"/>
                                            </linearGradient>
                                        </defs>
                                        <circle cx="30" cy="30" r="28" fill="url(#coinGrad)"/>
                                        <circle cx="30" cy="30" r="22" fill="none" stroke="#fcd34d" stroke-width="2"/>
                                        <text x="30" y="38" text-anchor="middle" fill="#92400e" font-size="24" font-weight="bold">$</text>
                                    </svg>
                                </div>
                                <div class="ezzy-phone-waves">
                                    <div class="wave wave-1"></div>
                                    <div class="wave wave-2"></div>
                                    <div class="wave wave-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wallet -->
                    <div class="ezzy-physical-wallet">
                        <div class="ezzy-wallet-flap"></div>
                        <div class="ezzy-wallet-body">
                            <div class="ezzy-credit-card">
                                <div class="ezzy-card-chip"></div>
                                <div class="ezzy-card-lines">
                                    <div class="card-line"></div>
                                    <div class="card-line"></div>
                                    <div class="card-line"></div>
                                </div>
                            </div>
                        </div>
                        <div class="ezzy-wallet-button"></div>
                    </div>

                    <!-- Floating Coins -->
                    <div class="ezzy-floating-coins">
                        <div class="ezzy-coin ezzy-coin-1">
                            <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <linearGradient id="coinGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#fbbf24"/>
                                        <stop offset="100%" style="stop-color:#d97706"/>
                                    </linearGradient>
                                </defs>
                                <circle cx="20" cy="20" r="18" fill="url(#coinGrad1)"/>
                                <text x="20" y="26" text-anchor="middle" fill="#92400e" font-size="16" font-weight="bold">$</text>
                            </svg>
                        </div>
                        <div class="ezzy-coin ezzy-coin-2">
                            <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="20" cy="20" r="18" fill="url(#coinGrad1)"/>
                                <text x="20" y="26" text-anchor="middle" fill="#92400e" font-size="16" font-weight="bold">$</text>
                            </svg>
                        </div>
                        <div class="ezzy-coin ezzy-coin-3">
                            <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="20" cy="20" r="18" fill="url(#coinGrad1)"/>
                                <text x="20" y="26" text-anchor="middle" fill="#92400e" font-size="16" font-weight="bold">$</text>
                            </svg>
                        </div>
                    </div>

                    <!-- Receipt -->
                    <div class="ezzy-receipt">
                        <div class="receipt-line"></div>
                        <div class="receipt-line short"></div>
                        <div class="receipt-qr">
                            <div class="qr-square"></div>
                            <div class="qr-square"></div>
                            <div class="qr-square"></div>
                            <div class="qr-square"></div>
                        </div>
                        <p class="receipt-text"><?php esc_html_e( 'THANK YOU!', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Forms Container -->
    <section class="ezzy-forms-section">
        <div class="ezzy-forms-container">
            <!-- Signup Form -->
            <div class="ezzy-form-wrapper" id="ezzy-signup-form-wrapper" <?php echo $show_signup ? 'style="display: block;"' : ''; ?>>
                <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/signup-form.php'; ?>
            </div>

            <!-- Login Form -->
            <div class="ezzy-form-wrapper" id="ezzy-login-form-wrapper" <?php echo $show_login ? 'style="display: block;"' : ''; ?>>
                <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/login-form.php'; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="ezzy-features-section">
        <div class="ezzy-features-container">
            <div class="ezzy-feature">
                <div class="ezzy-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Secure', 'ezzy-wallet' ); ?></h3>
                <p><?php esc_html_e( 'Bank-level security for your digital assets', 'ezzy-wallet' ); ?></p>
            </div>
            <div class="ezzy-feature">
                <div class="ezzy-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Fast', 'ezzy-wallet' ); ?></h3>
                <p><?php esc_html_e( 'Instant transfers and transactions', 'ezzy-wallet' ); ?></p>
            </div>
            <div class="ezzy-feature">
                <div class="ezzy-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3><?php esc_html_e( 'Reliable', 'ezzy-wallet' ); ?></h3>
                <p><?php esc_html_e( 'Trusted by thousands of users', 'ezzy-wallet' ); ?></p>
            </div>
        </div>
    </section>
</div>
