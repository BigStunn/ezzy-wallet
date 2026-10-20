<?php
/**
 * License Activation Page for eZZY Wallet.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$error_message = '';
$success_message = '';

// Handle activation form submission.
if ( isset( $_POST['ezzy_activate_license'] ) && isset( $_POST['ezzy_license_nonce'] ) ) {
    if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ezzy_license_nonce'] ) ), 'ezzy_license_activation' ) ) {
        $code = isset( $_POST['activation_code'] ) ? sanitize_text_field( wp_unslash( $_POST['activation_code'] ) ) : '';
        $result = Ezzy_Wallet_License::activate( $code );

        if ( $result['success'] ) {
            $success_message = $result['message'];
            // Redirect to main admin page after successful activation.
            wp_safe_redirect( admin_url( 'admin.php?page=ezzy-wallet&activated=1' ) );
            exit;
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = __( 'Security check failed. Please try again.', 'ezzy-wallet' );
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e( 'Activate eZZY Wallet', 'ezzy-wallet' ); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .ezzy-activation-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            max-width: 440px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .ezzy-activation-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .ezzy-activation-logo-icon {
            width: 48px;
            height: 48px;
        }

        .ezzy-activation-logo-icon svg {
            width: 100%;
            height: 100%;
        }

        .ezzy-activation-logo-text {
            font-size: 28px;
            font-weight: 700;
            font-style: italic;
            color: #2563eb;
            letter-spacing: -0.02em;
        }

        .ezzy-activation-tagline {
            font-size: 12px;
            color: #94a3b8;
            font-style: italic;
            margin-bottom: 32px;
        }

        .ezzy-activation-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .ezzy-activation-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .ezzy-activation-form {
            text-align: left;
        }

        .ezzy-activation-field {
            margin-bottom: 20px;
        }

        .ezzy-activation-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }

        .ezzy-activation-input {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }

        .ezzy-activation-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .ezzy-activation-input::placeholder {
            color: #94a3b8;
            text-transform: none;
            letter-spacing: normal;
        }

        .ezzy-activation-button {
            width: 100%;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ezzy-activation-button:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .ezzy-activation-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ezzy-activation-error svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .ezzy-activation-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ezzy-activation-success svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .ezzy-activation-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
        }

        .ezzy-activation-footer a {
            color: #2563eb;
            text-decoration: none;
        }

        .ezzy-activation-footer a:hover {
            text-decoration: underline;
        }

        .ezzy-lock-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .ezzy-lock-icon svg {
            width: 28px;
            height: 28px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="ezzy-activation-container">
        <div class="ezzy-activation-logo">
            <span class="ezzy-activation-logo-icon">
                <svg viewBox="0 0 48 48">
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#2563eb"/>
                            <stop offset="100%" style="stop-color:#1d4ed8"/>
                        </linearGradient>
                    </defs>
                    <circle cx="24" cy="24" r="22" fill="url(#logoGrad)"/>
                    <text x="24" y="32" text-anchor="middle" fill="#fff" font-size="24" font-weight="700" font-style="italic" font-family="Arial">e</text>
                </svg>
            </span>
            <span class="ezzy-activation-logo-text">ZZY WaLLet</span>
        </div>
        <p class="ezzy-activation-tagline">by Big Stunnz</p>

        <div class="ezzy-lock-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
        </div>

        <h1 class="ezzy-activation-title"><?php esc_html_e( 'Activate Your License', 'ezzy-wallet' ); ?></h1>
        <p class="ezzy-activation-subtitle"><?php esc_html_e( 'Enter your activation code to unlock the full features of eZZY Wallet plugin.', 'ezzy-wallet' ); ?></p>

        <?php if ( ! empty( $error_message ) ) : ?>
            <div class="ezzy-activation-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span><?php echo esc_html( $error_message ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $success_message ) ) : ?>
            <div class="ezzy-activation-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span><?php echo esc_html( $success_message ); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="ezzy-activation-form">
            <?php wp_nonce_field( 'ezzy_license_activation', 'ezzy_license_nonce' ); ?>

            <div class="ezzy-activation-field">
                <label class="ezzy-activation-label" for="activation_code">
                    <?php esc_html_e( 'Activation Code', 'ezzy-wallet' ); ?>
                </label>
                <input 
                    type="text" 
                    id="activation_code" 
                    name="activation_code" 
                    class="ezzy-activation-input"
                    placeholder="EZZY-XXXX-XXXX-XXXX"
                    autocomplete="off"
                    required
                />
            </div>

            <button type="submit" name="ezzy_activate_license" class="ezzy-activation-button">
                <?php esc_html_e( 'Activate Plugin', 'ezzy-wallet' ); ?>
            </button>
        </form>

        <div class="ezzy-activation-footer">
            <p><?php esc_html_e( "Don't have a license?", 'ezzy-wallet' ); ?> <a href="mailto:support@bigstunnz.com"><?php esc_html_e( 'Contact Support', 'ezzy-wallet' ); ?></a></p>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
