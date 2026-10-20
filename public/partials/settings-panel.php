<?php
/**
 * Account Settings Panel Template
 *
 * Provides 2FA, Password Change, and Account Deletion options.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if 2FA is enabled for this user
$two_fa_enabled = get_user_meta( $user_id, 'ezzy_wallet_2fa_enabled', true ) === '1';
$two_fa_secret = get_user_meta( $user_id, 'ezzy_wallet_2fa_secret', true );
?>

<div class="ezzy-card ezzy-settings-panel" id="ezzy-settings-panel">
    <div class="ezzy-panel-header">
        <h2 class="ezzy-panel-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            <?php esc_html_e( 'Account Settings', 'ezzy-wallet' ); ?>
        </h2>
        <span class="ezzy-panel-subtitle"><?php esc_html_e( 'Manage your security and account preferences', 'ezzy-wallet' ); ?></span>
    </div>

    <div class="ezzy-settings-content">
        <!-- Two-Factor Authentication Section -->
        <div class="ezzy-settings-section">
            <div class="ezzy-settings-item" id="ezzy-2fa-section">
                <div class="ezzy-settings-item-header">
                    <div class="ezzy-settings-item-icon ezzy-icon-security">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="ezzy-settings-item-info">
                        <h3><?php esc_html_e( 'Two-Factor Authentication', 'ezzy-wallet' ); ?></h3>
                        <p><?php esc_html_e( 'Add an extra layer of security to your account using an authenticator app.', 'ezzy-wallet' ); ?></p>
                    </div>
                    <div class="ezzy-settings-item-action">
                        <label class="ezzy-toggle-switch">
                            <input type="checkbox" id="ezzy-2fa-toggle" <?php checked( $two_fa_enabled ); ?>>
                            <span class="ezzy-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- 2FA Setup Panel (hidden by default) -->
                <div class="ezzy-2fa-setup" id="ezzy-2fa-setup" style="display:none;">
                    <div class="ezzy-2fa-steps">
                        <div class="ezzy-2fa-step">
                            <span class="ezzy-step-number">1</span>
                            <div class="ezzy-step-content">
                                <h4><?php esc_html_e( 'Download an Authenticator App', 'ezzy-wallet' ); ?></h4>
                                <p><?php esc_html_e( 'Download Google Authenticator, Authy, or any TOTP-compatible app on your phone.', 'ezzy-wallet' ); ?></p>
                            </div>
                        </div>
                        <div class="ezzy-2fa-step">
                            <span class="ezzy-step-number">2</span>
                            <div class="ezzy-step-content">
                                <h4><?php esc_html_e( 'Scan the QR Code', 'ezzy-wallet' ); ?></h4>
                                <p><?php esc_html_e( 'Open your authenticator app and scan this QR code:', 'ezzy-wallet' ); ?></p>
                                <div class="ezzy-qr-container" id="ezzy-qr-container">
                                    <div class="ezzy-qr-loading">
                                        <div class="ezzy-spinner"></div>
                                        <p><?php esc_html_e( 'Generating QR code...', 'ezzy-wallet' ); ?></p>
                                    </div>
                                </div>
                                <div class="ezzy-secret-key" id="ezzy-secret-key" style="display:none;">
                                    <p><?php esc_html_e( 'Or enter this code manually:', 'ezzy-wallet' ); ?></p>
                                    <code id="ezzy-secret-code"></code>
                                    <button type="button" class="ezzy-copy-btn" id="ezzy-copy-secret">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="ezzy-2fa-step">
                            <span class="ezzy-step-number">3</span>
                            <div class="ezzy-step-content">
                                <h4><?php esc_html_e( 'Enter Verification Code', 'ezzy-wallet' ); ?></h4>
                                <p><?php esc_html_e( 'Enter the 6-digit code from your authenticator app:', 'ezzy-wallet' ); ?></p>
                                <div class="ezzy-2fa-verify-form">
                                    <input type="text" id="ezzy-2fa-code" class="ezzy-2fa-input" maxlength="6" placeholder="000000" autocomplete="off">
                                    <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-verify-2fa">
                                        <?php esc_html_e( 'Verify & Enable', 'ezzy-wallet' ); ?>
                                    </button>
                                </div>
                                <p class="ezzy-2fa-error" id="ezzy-2fa-error" style="display:none;"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2FA Enabled Status -->
                <div class="ezzy-2fa-enabled" id="ezzy-2fa-enabled" style="<?php echo $two_fa_enabled ? '' : 'display:none;'; ?>">
                    <div class="ezzy-2fa-status">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span><?php esc_html_e( 'Two-factor authentication is enabled', 'ezzy-wallet' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Section -->
        <div class="ezzy-settings-section">
            <div class="ezzy-settings-item" id="ezzy-password-section">
                <div class="ezzy-settings-item-header" id="ezzy-password-toggle">
                    <div class="ezzy-settings-item-icon ezzy-icon-password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </div>
                    <div class="ezzy-settings-item-info">
                        <h3><?php esc_html_e( 'Change Password', 'ezzy-wallet' ); ?></h3>
                        <p><?php esc_html_e( 'Update your account password to keep your wallet secure.', 'ezzy-wallet' ); ?></p>
                    </div>
                    <div class="ezzy-settings-item-action">
                        <svg class="ezzy-settings-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Password Change Form (hidden by default) -->
                <div class="ezzy-password-form" id="ezzy-password-form" style="display:none;">
                    <div class="ezzy-form-group">
                        <label for="ezzy-current-password"><?php esc_html_e( 'Current Password', 'ezzy-wallet' ); ?></label>
                        <div class="ezzy-password-input-wrapper">
                            <input type="password" id="ezzy-current-password" class="ezzy-input" placeholder="<?php esc_attr_e( 'Enter current password', 'ezzy-wallet' ); ?>">
                            <button type="button" class="ezzy-password-toggle-btn" data-target="ezzy-current-password">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ezzy-form-group">
                        <label for="ezzy-new-password"><?php esc_html_e( 'New Password', 'ezzy-wallet' ); ?></label>
                        <div class="ezzy-password-input-wrapper">
                            <input type="password" id="ezzy-new-password" class="ezzy-input" placeholder="<?php esc_attr_e( 'Enter new password', 'ezzy-wallet' ); ?>">
                            <button type="button" class="ezzy-password-toggle-btn" data-target="ezzy-new-password">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="ezzy-password-requirements" id="ezzy-password-requirements">
                            <p class="ezzy-requirements-title"><?php esc_html_e( 'Password must contain:', 'ezzy-wallet' ); ?></p>
                            <ul class="ezzy-requirements-list">
                                <li id="req-length" class="ezzy-req-invalid">
                                    <svg class="ezzy-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <?php esc_html_e( 'At least 8 characters', 'ezzy-wallet' ); ?>
                                </li>
                                <li id="req-uppercase" class="ezzy-req-invalid">
                                    <svg class="ezzy-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <?php esc_html_e( 'One uppercase letter (A-Z)', 'ezzy-wallet' ); ?>
                                </li>
                                <li id="req-lowercase" class="ezzy-req-invalid">
                                    <svg class="ezzy-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <?php esc_html_e( 'One lowercase letter (a-z)', 'ezzy-wallet' ); ?>
                                </li>
                                <li id="req-number" class="ezzy-req-invalid">
                                    <svg class="ezzy-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <?php esc_html_e( 'One number (0-9)', 'ezzy-wallet' ); ?>
                                </li>
                                <li id="req-special" class="ezzy-req-invalid">
                                    <svg class="ezzy-req-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                    <?php esc_html_e( 'One special character (!@#$%^&*)', 'ezzy-wallet' ); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="ezzy-form-group">
                        <label for="ezzy-confirm-password"><?php esc_html_e( 'Confirm New Password', 'ezzy-wallet' ); ?></label>
                        <div class="ezzy-password-input-wrapper">
                            <input type="password" id="ezzy-confirm-password" class="ezzy-input" placeholder="<?php esc_attr_e( 'Confirm new password', 'ezzy-wallet' ); ?>">
                            <button type="button" class="ezzy-password-toggle-btn" data-target="ezzy-confirm-password">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <p class="ezzy-password-match" id="ezzy-password-match" style="display:none;"></p>
                    </div>
                    
                    <div class="ezzy-form-actions">
                        <button type="button" class="ezzy-btn ezzy-btn-secondary" id="ezzy-cancel-password">
                            <?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?>
                        </button>
                        <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-save-password" disabled>
                            <?php esc_html_e( 'Update Password', 'ezzy-wallet' ); ?>
                        </button>
                    </div>
                    <p class="ezzy-password-message" id="ezzy-password-message" style="display:none;"></p>
                </div>
            </div>
        </div>

        <!-- Delete Account Section -->
        <div class="ezzy-settings-section ezzy-settings-danger">
            <div class="ezzy-settings-item" id="ezzy-delete-section">
                <div class="ezzy-settings-item-header" id="ezzy-delete-toggle">
                    <div class="ezzy-settings-item-icon ezzy-icon-danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                            <line x1="10" y1="11" x2="10" y2="17"/>
                            <line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>
                    </div>
                    <div class="ezzy-settings-item-info">
                        <h3><?php esc_html_e( 'Delete Account', 'ezzy-wallet' ); ?></h3>
                        <p><?php esc_html_e( 'Permanently delete your account and all associated data. This action cannot be undone.', 'ezzy-wallet' ); ?></p>
                    </div>
                    <div class="ezzy-settings-item-action">
                        <button type="button" class="ezzy-btn ezzy-btn-danger-outline" id="ezzy-delete-account-btn">
                            <?php esc_html_e( 'Delete Account', 'ezzy-wallet' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div class="ezzy-modal-overlay" id="ezzy-delete-modal" style="display:none;">
    <div class="ezzy-modal">
        <div class="ezzy-modal-header">
            <div class="ezzy-modal-icon ezzy-modal-icon-danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'Delete Your Account?', 'ezzy-wallet' ); ?></h3>
        </div>
        <div class="ezzy-modal-body">
            <p><?php esc_html_e( 'Are you sure you want to delete your account? This will permanently remove:', 'ezzy-wallet' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Your wallet and all balance information', 'ezzy-wallet' ); ?></li>
                <li><?php esc_html_e( 'All transaction history', 'ezzy-wallet' ); ?></li>
                <li><?php esc_html_e( 'Your profile information', 'ezzy-wallet' ); ?></li>
                <li><?php esc_html_e( 'All notifications and messages', 'ezzy-wallet' ); ?></li>
            </ul>
            <p class="ezzy-modal-warning"><?php esc_html_e( 'This action cannot be undone!', 'ezzy-wallet' ); ?></p>
            
            <div class="ezzy-modal-confirm-input">
                <label><?php esc_html_e( 'Type DELETE to confirm:', 'ezzy-wallet' ); ?></label>
                <input type="text" id="ezzy-delete-confirm-input" placeholder="DELETE" autocomplete="off">
            </div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="ezzy-btn ezzy-btn-secondary" id="ezzy-cancel-delete">
                <?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?>
            </button>
            <button type="button" class="ezzy-btn ezzy-btn-danger" id="ezzy-confirm-delete" disabled>
                <?php esc_html_e( 'Delete My Account', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>
