<?php
/**
 * Support Panel Template
 *
 * Provides support information and contact options.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="ezzy-card ezzy-support-panel" id="ezzy-support-panel">
    <div class="ezzy-panel-header">
        <h2 class="ezzy-panel-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <?php esc_html_e( 'Help & Support', 'ezzy-wallet' ); ?>
        </h2>
        <span class="ezzy-panel-subtitle"><?php esc_html_e( 'Get help with your eZZY Wallet account', 'ezzy-wallet' ); ?></span>
    </div>

    <div class="ezzy-support-content">
        <!-- FAQ Section -->
        <div class="ezzy-support-section">
            <h3 class="ezzy-support-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                </svg>
                <?php esc_html_e( 'Frequently Asked Questions', 'ezzy-wallet' ); ?>
            </h3>
            
            <div class="ezzy-faq-list">
                <div class="ezzy-faq-item">
                    <div class="ezzy-faq-question">
                        <span><?php esc_html_e( 'How do I deposit funds into my wallet?', 'ezzy-wallet' ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="ezzy-faq-answer">
                        <p><?php esc_html_e( 'To deposit funds, go to your Dashboard and click the "Deposit" button. Follow the instructions provided to complete your deposit. Deposits are processed and credited to your account once confirmed by an administrator.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
                
                <div class="ezzy-faq-item">
                    <div class="ezzy-faq-question">
                        <span><?php esc_html_e( 'How long do withdrawals take?', 'ezzy-wallet' ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="ezzy-faq-answer">
                        <p><?php esc_html_e( 'Withdrawal processing times vary depending on the method and amount. Typically, withdrawals are reviewed and processed within 1-3 business days. You will receive a notification once your withdrawal has been processed.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
                
                <div class="ezzy-faq-item">
                    <div class="ezzy-faq-question">
                        <span><?php esc_html_e( 'How do I enable two-factor authentication?', 'ezzy-wallet' ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="ezzy-faq-answer">
                        <p><?php esc_html_e( 'Go to Account Settings and toggle on "Two-Factor Authentication". You will need to scan a QR code with an authenticator app (like Google Authenticator or Authy) and verify with a 6-digit code.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
                
                <div class="ezzy-faq-item">
                    <div class="ezzy-faq-question">
                        <span><?php esc_html_e( 'Can I change my display currency?', 'ezzy-wallet' ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="ezzy-faq-answer">
                        <p><?php esc_html_e( 'Yes! You can switch your display currency using the currency switcher in your Dashboard header. This changes how your balance is displayed but does not affect the underlying USD value of your wallet.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
                
                <div class="ezzy-faq-item">
                    <div class="ezzy-faq-question">
                        <span><?php esc_html_e( 'Is my account secure?', 'ezzy-wallet' ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                    <div class="ezzy-faq-answer">
                        <p><?php esc_html_e( 'We take security seriously. Your password is encrypted, and we recommend enabling two-factor authentication for additional protection. Never share your login credentials with anyone.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support Section -->
        <div class="ezzy-support-section">
            <h3 class="ezzy-support-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <?php esc_html_e( 'Contact Support', 'ezzy-wallet' ); ?>
            </h3>
            
            <div class="ezzy-contact-options">
                <div class="ezzy-contact-card">
                    <div class="ezzy-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <h4><?php esc_html_e( 'Email Support', 'ezzy-wallet' ); ?></h4>
                    <p><?php esc_html_e( 'Send us a message and we\'ll respond within 24 hours.', 'ezzy-wallet' ); ?></p>
                    <button type="button" class="ezzy-btn ezzy-btn-outline" id="ezzy-email-support-btn">
                        <?php esc_html_e( 'Send Message', 'ezzy-wallet' ); ?>
                    </button>
                </div>
                
                <div class="ezzy-contact-card">
                    <div class="ezzy-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
                        </svg>
                    </div>
                    <h4><?php esc_html_e( 'Live Chat', 'ezzy-wallet' ); ?></h4>
                    <p><?php esc_html_e( 'Chat with our support team in real-time.', 'ezzy-wallet' ); ?></p>
                    <button type="button" class="ezzy-btn ezzy-btn-outline" id="ezzy-start-chat">
                        <?php esc_html_e( 'Start Chat', 'ezzy-wallet' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Account Info Section -->
        <div class="ezzy-support-section ezzy-account-info-section">
            <h3 class="ezzy-support-section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <?php esc_html_e( 'Your Account Information', 'ezzy-wallet' ); ?>
            </h3>
            <div class="ezzy-account-info-card">
                <div class="ezzy-info-row">
                    <span class="ezzy-info-label"><?php esc_html_e( 'Username:', 'ezzy-wallet' ); ?></span>
                    <span class="ezzy-info-value"><?php echo esc_html( $current_user->user_login ); ?></span>
                </div>
                <div class="ezzy-info-row">
                    <span class="ezzy-info-label"><?php esc_html_e( 'Email:', 'ezzy-wallet' ); ?></span>
                    <span class="ezzy-info-value"><?php echo esc_html( $current_user->user_email ); ?></span>
                </div>
                <div class="ezzy-info-row">
                    <span class="ezzy-info-label"><?php esc_html_e( 'Member Since:', 'ezzy-wallet' ); ?></span>
                    <span class="ezzy-info-value"><?php echo esc_html( date_i18n( 'F j, Y', strtotime( $current_user->user_registered ) ) ); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Support Modal -->
<div class="ezzy-modal-overlay" id="ezzy-email-support-modal" style="display:none;">
    <div class="ezzy-modal ezzy-support-modal">
        <div class="ezzy-modal-header">
            <div class="ezzy-modal-icon ezzy-modal-icon-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'Contact Support', 'ezzy-wallet' ); ?></h3>
            <button type="button" class="ezzy-modal-close" id="ezzy-close-support-modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="ezzy-modal-body">
            <form id="ezzy-support-form" class="ezzy-support-form-compact">
                <div class="ezzy-form-grid">
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Username', 'ezzy-wallet' ); ?></label>
                        <input type="text" id="ezzy-support-username" class="ezzy-input ezzy-input-sm" value="<?php echo esc_attr( $current_user->user_login ); ?>" readonly>
                    </div>
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Email', 'ezzy-wallet' ); ?></label>
                        <input type="email" id="ezzy-support-email" class="ezzy-input ezzy-input-sm" value="<?php echo esc_attr( $current_user->user_email ); ?>" readonly>
                    </div>
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Phone', 'ezzy-wallet' ); ?></label>
                        <input type="tel" id="ezzy-support-phone" class="ezzy-input ezzy-input-sm" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'ezzy_wallet_phone', true ) ); ?>" placeholder="<?php esc_attr_e( 'Optional', 'ezzy-wallet' ); ?>">
                    </div>
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Topic', 'ezzy-wallet' ); ?></label>
                        <select id="ezzy-support-subject" class="ezzy-input ezzy-input-sm ezzy-select">
                            <option value=""><?php esc_html_e( 'Select...', 'ezzy-wallet' ); ?></option>
                            <option value="deposit"><?php esc_html_e( 'Deposit', 'ezzy-wallet' ); ?></option>
                            <option value="withdrawal"><?php esc_html_e( 'Withdrawal', 'ezzy-wallet' ); ?></option>
                            <option value="account"><?php esc_html_e( 'Account', 'ezzy-wallet' ); ?></option>
                            <option value="security"><?php esc_html_e( 'Security', 'ezzy-wallet' ); ?></option>
                            <option value="other"><?php esc_html_e( 'Other', 'ezzy-wallet' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="ezzy-form-group ezzy-form-message">
                    <label><?php esc_html_e( 'Message', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <textarea id="ezzy-support-message" class="ezzy-input ezzy-textarea-sm" placeholder="<?php esc_attr_e( 'Describe your issue...', 'ezzy-wallet' ); ?>" required></textarea>
                    <span class="ezzy-char-count"><span id="ezzy-message-chars">0</span>/1000</span>
                </div>
            </form>
            <div class="ezzy-support-message-result" id="ezzy-support-result" style="display:none;"></div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="ezzy-btn ezzy-btn-secondary" id="ezzy-cancel-support"><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-send-support">
                <svg class="ezzy-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                <?php esc_html_e( 'Send', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>
