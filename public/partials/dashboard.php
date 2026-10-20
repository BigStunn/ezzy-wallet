<?php
/**
 * Dashboard template for eZZY Wallet
 * Responsive design with mobile footer navigation
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$wallet_page_url = $this->get_wallet_page_url();
$logout_url = wp_logout_url( $wallet_page_url );

$display_name = '';
if ( ! empty( $current_user->first_name ) ) {
    $display_name = $current_user->first_name;
} elseif ( ! empty( $current_user->display_name ) ) {
    $display_name = $current_user->display_name;
} else {
    $display_name = $current_user->user_login;
}

// Get custom avatar or fallback to Gravatar
$custom_avatar = get_user_meta( $current_user->ID, 'ezzy_wallet_profile_image', true );
if ( ! empty( $custom_avatar ) ) {
    $avatar_url = $custom_avatar;
} else {
    $avatar_url = get_avatar_url( $current_user->ID, array( 'size' => 80 ) );
}

// Get real dashboard data using Data Service
$user_currency = Ezzy_Wallet_Data_Service::get_user_currency_preference( $current_user->ID );
$currency_symbol = Ezzy_Wallet_Data_Service::get_currency_symbol( $user_currency );
$balance_usd = Ezzy_Wallet_Data_Service::get_user_balance( $current_user->ID );
$balance_display = Ezzy_Wallet_Data_Service::convert_from_usd( $balance_usd, $user_currency );
$balance_formatted = number_format( $balance_display, 2, '.', ',' );

// Get crypto conversions
$crypto_conversions = Ezzy_Wallet_Data_Service::get_balance_crypto_conversions( $current_user->ID, $user_currency );
$btc_amount = Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['BTC'], 'BTC' );
$eth_amount = Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['ETH'], 'ETH' );
$usdt_amount = Ezzy_Wallet_Data_Service::format_crypto_amount( $crypto_conversions['USDT'], 'USDT' );

// Get recent transactions (only 5 for dashboard)
$transactions = Ezzy_Wallet_Data_Service::get_user_transactions( $current_user->ID, 5 );

// Get supported currencies for switcher
$supported_currencies = Ezzy_Wallet_Data_Service::get_supported_currencies();

// Get unread notification count
$unread_notification_count = Ezzy_Wallet_Data_Service::get_unread_notification_count( $current_user->ID );

$settings = get_option( 'ezzy_wallet_settings', array() );

// Check if user can withdraw (global + individual check)
$can_withdraw = Ezzy_Wallet_Frontend::can_user_withdraw( $current_user->ID );
$withdraw_disabled_class = $can_withdraw ? '' : 'ezzy-btn-disabled';
$withdraw_disabled_attr = $can_withdraw ? '' : 'disabled';
?>

<div class="ezzy-dashboard">
    <!-- SIDEBAR (Desktop Only) -->
    <aside class="ezzy-sidebar">
        <!-- Logo - Phase 4 Final Version -->
        <div class="ezzy-logo">
            <div class="ezzy-logo-row">
                <span class="ezzy-logo-icon">
                    <svg viewBox="0 0 38 38">
                        <defs>
                            <linearGradient id="ezzyLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--ezzy-primary)"/>
                                <stop offset="100%" style="stop-color:var(--ezzy-secondary)"/>
                            </linearGradient>
                        </defs>
                        <circle cx="19" cy="19" r="17" fill="url(#ezzyLogoGrad)"/>
                        <text x="19" y="25" text-anchor="middle" fill="#fff" font-size="18" font-weight="700" font-style="italic" font-family="Arial">e</text>
                    </svg>
                </span>
                <span class="ezzy-logo-brand">ZZY WaLLet</span>
            </div>
            <span class="ezzy-logo-tagline">by Big Stunnz</span>
        </div>

        <!-- Main Navigation -->
        <nav class="ezzy-nav">
            <div class="ezzy-nav-group">
                <span class="ezzy-nav-label">MAIN</span>
                <ul class="ezzy-nav-list">
                    <li class="active">
                        <a href="#" data-page="dashboard">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" data-page="messages" class="ezzy-nav-messages">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                            <span>Messages</span>
                            <?php if ( $unread_notification_count > 0 ) : ?>
                                <span class="ezzy-notification-badge" id="ezzy-sidebar-badge"><?php echo esc_html( $unread_notification_count ); ?></span>
                            <?php else : ?>
                                <span class="ezzy-notification-badge" id="ezzy-sidebar-badge" style="display:none;">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" data-page="profile">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/>
                            </svg>
                            <span>User Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" data-page="transactions">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                                <rect x="9" y="3" width="6" height="4" rx="1"/>
                                <path d="M9 12h6M9 16h6"/>
                            </svg>
                            <span>All Transactions</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="ezzy-nav-group ezzy-nav-others">
                <span class="ezzy-nav-label">OTHERS</span>
                <ul class="ezzy-nav-list">
                    <li>
                        <a href="#" data-page="support">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/>
                            </svg>
                            <span>Support</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" data-page="settings">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                            <span>Account Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="ezzy-main">
        <!-- Top Header -->
        <header class="ezzy-header">
            <!-- Mobile/Tablet Logo - Full Phase 4 Version -->
            <div class="ezzy-header-logo">
                <div class="ezzy-header-logo-row">
                    <span class="ezzy-header-logo-icon">
                        <svg viewBox="0 0 38 38">
                            <defs>
                                <linearGradient id="ezzyLogoGradMobile" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" style="stop-color:var(--ezzy-primary)"/>
                                    <stop offset="100%" style="stop-color:var(--ezzy-secondary)"/>
                                </linearGradient>
                            </defs>
                            <circle cx="19" cy="19" r="17" fill="url(#ezzyLogoGradMobile)"/>
                            <text x="19" y="25" text-anchor="middle" fill="#fff" font-size="18" font-weight="700" font-style="italic" font-family="Arial">e</text>
                        </svg>
                    </span>
                    <span class="ezzy-header-logo-brand">ZZY WaLLet</span>
                </div>
                <span class="ezzy-header-logo-tagline">by Big Stunnz</span>
            </div>

            <!-- Crypto Pills -->
            <div class="ezzy-crypto-bar" id="ezzy-crypto-bar">
                <div class="ezzy-crypto-pill">
                    <span class="ezzy-crypto-icon" style="background:#f7931a;">₿</span>
                    <span class="ezzy-crypto-value" id="ezzy-crypto-btc"><?php echo esc_html( $btc_amount ); ?></span>
                </div>
                <div class="ezzy-crypto-pill">
                    <span class="ezzy-crypto-icon" style="background:#627eea;">Ξ</span>
                    <span class="ezzy-crypto-value" id="ezzy-crypto-eth"><?php echo esc_html( $eth_amount ); ?></span>
                </div>
                <div class="ezzy-crypto-pill">
                    <span class="ezzy-crypto-icon" style="background:#26a17b;">₮</span>
                    <span class="ezzy-crypto-value" id="ezzy-crypto-usdt"><?php echo esc_html( $usdt_amount ); ?></span>
                </div>
            </div>

            <!-- User Section -->
            <div class="ezzy-header-user">
                <!-- Others Menu (Tablet/Mobile) -->
                <div class="ezzy-others-menu">
                    <a href="#" class="ezzy-others-btn" data-page="support" title="Support">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3M12 17h.01"/>
                        </svg>
                    </a>
                    <a href="#" class="ezzy-others-btn" data-page="settings" title="Settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                        </svg>
                    </a>
                </div>

                <span class="ezzy-user-greeting">Hello, <strong><?php echo esc_html( $display_name ); ?></strong></span>
                <a href="<?php echo esc_url( $logout_url ); ?>" class="ezzy-user-signout">Signout</a>
                <div class="ezzy-user-avatar ezzy-avatar-upload" id="ezzy-header-avatar" title="<?php esc_attr_e( 'Click to change profile picture', 'ezzy-wallet' ); ?>">
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" id="ezzy-header-avatar-img"/>
                    <div class="ezzy-avatar-overlay">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </div>
                    <input type="file" id="ezzy-header-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;"/>
                </div>
            </div>
        </header>

        <!-- Content Area with Panels -->
        <div class="ezzy-content">
            <!-- Dashboard Panel -->
            <div class="ezzy-panel ezzy-panel-dashboard active" id="ezzy-panel-dashboard">
                <div class="ezzy-card">
                    <!-- Wallet Header -->
                    <div class="ezzy-wallet-header">
                        <div class="ezzy-wallet-info">
                            <span class="ezzy-wallet-icon">
                                <svg viewBox="0 0 32 32">
                                    <circle cx="16" cy="16" r="14" fill="var(--ezzy-primary)"/>
                                    <rect x="8" y="11" width="16" height="10" rx="2" fill="#fff"/>
                                    <circle cx="20" cy="16" r="2" fill="var(--ezzy-primary)"/>
                                </svg>
                            </span>
                            <span class="ezzy-wallet-title">Digital Wallet</span>
                        </div>
                        <!-- Currency Switcher -->
                        <div class="ezzy-currency-switcher" id="ezzy-currency-switcher">
                            <button class="ezzy-switch-btn" type="button" id="ezzy-currency-toggle">
                                <span id="ezzy-current-currency"><?php echo esc_html( $user_currency ); ?></span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>
                            <div class="ezzy-currency-dropdown" id="ezzy-currency-dropdown">
                                <?php foreach ( $supported_currencies as $curr ) : ?>
                                    <button type="button" 
                                            class="ezzy-currency-option <?php echo $curr === $user_currency ? 'active' : ''; ?>" 
                                            data-currency="<?php echo esc_attr( $curr ); ?>">
                                        <?php echo esc_html( $curr ); ?>
                                        <span class="ezzy-currency-symbol"><?php echo esc_html( Ezzy_Wallet_Data_Service::get_currency_symbol( $curr ) ); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <!-- Balance Section -->
                <div class="ezzy-balance-section">
                    <div class="ezzy-balance-info">
                        <span class="ezzy-balance-label">
                            Available Balance
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 16v-4m0-4h.01"/>
                            </svg>
                        </span>
                        <div class="ezzy-balance-row">
                            <span class="ezzy-balance-amount" id="ezzy-balance-display" data-balance="<?php echo esc_attr( $currency_symbol . $balance_formatted ); ?>"><?php echo esc_html( $currency_symbol . $balance_formatted ); ?></span>
                            <button class="ezzy-balance-toggle" id="ezzy-balance-toggle" type="button" aria-label="Toggle balance visibility">
                                <svg class="ezzy-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="ezzy-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="ezzy-balance-actions">
                        <button class="ezzy-action-btn ezzy-action-primary" type="button" id="ezzy-deposit-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14m-7-7h14"/>
                            </svg>
                            <span>Deposit</span>
                        </button>
                        <button class="ezzy-action-btn ezzy-action-outline <?php echo esc_attr( $withdraw_disabled_class ); ?>" type="button" id="ezzy-withdraw-btn" <?php echo esc_attr( $withdraw_disabled_attr ); ?>>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 19V5m-7 7l7-7 7 7"/>
                            </svg>
                            <span>Withdraw</span>
                        </button>
                        <button class="ezzy-action-btn ezzy-action-outline" type="button" id="ezzy-transfer-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                            <span>Transfer</span>
                        </button>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div class="ezzy-transactions-section">
                    <h3 class="ezzy-transactions-title">Recent Transactions</h3>
                    <div class="ezzy-transactions-table ezzy-recent-table" id="ezzy-transactions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-type">Type</th>
                                    <th class="col-description">Description</th>
                                    <th class="col-amount">Amount</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-date ezzy-hide-mobile">Date</th>
                                </tr>
                            </thead>
                            <tbody id="ezzy-transactions-body">
                                <?php if ( empty( $transactions ) ) : ?>
                                <tr class="ezzy-no-transactions">
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #64748b;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin:0 auto 10px;display:block;opacity:0.5;">
                                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                                            <rect x="9" y="3" width="6" height="4" rx="1"/>
                                        </svg>
                                        No transactions yet
                                    </td>
                                </tr>
                                <?php else : ?>
                                    <?php foreach ( $transactions as $tx ) : 
                                        // Normalize transaction type for display
                                        $tx_type = $tx['type'];
                                        $tx_type_display = $tx_type;
                                        
                                        // Map types for display
                                        if ( $tx_type === 'deposit_success' ) {
                                            $tx_type_display = 'deposit';
                                        } elseif ( $tx_type === 'withdraw_success' ) {
                                            $tx_type_display = 'withdrawal';
                                        } elseif ( $tx_type === 'transfer_success' ) {
                                            $tx_type_display = 'transfer';
                                        } elseif ( $tx_type === 'deposit_request' ) {
                                            $tx_type_display = 'deposit';
                                        } elseif ( $tx_type === 'withdrawal_request' ) {
                                            $tx_type_display = 'withdrawal';
                                        } elseif ( $tx_type === 'transfer_sent' ) {
                                            $tx_type_display = 'transfer';
                                        } elseif ( $tx_type === 'transfer_received' ) {
                                            $tx_type_display = 'transfer';
                                        }
                                        
                                        // Type label for display
                                        $type_labels = array(
                                            'deposit'    => __( 'Deposit', 'ezzy-wallet' ),
                                            'withdrawal' => __( 'Withdrawal', 'ezzy-wallet' ),
                                            'transfer'   => __( 'Transfer', 'ezzy-wallet' ),
                                        );
                                        $tx_type_label = isset( $type_labels[ $tx_type_display ] ) ? $type_labels[ $tx_type_display ] : ucfirst( $tx_type_display );
                                        
                                        // Get description - use the stored one
                                        $tx_description = $tx['description'];
                                        if ( empty( $tx_description ) || strpos( $tx_description, 'Updated by' ) !== false ) {
                                            // Generate user-friendly description
                                            switch ( $tx_type_display ) {
                                                case 'deposit':
                                                    $tx_description = __( 'Deposit successful and your total balance updated accordingly.', 'ezzy-wallet' );
                                                    break;
                                                case 'withdrawal':
                                                    $tx_description = __( 'Withdrawal successful and your total balance updated accordingly.', 'ezzy-wallet' );
                                                    break;
                                                case 'transfer':
                                                    $tx_description = __( 'Transfer successful and your total balance updated accordingly.', 'ezzy-wallet' );
                                                    break;
                                                default:
                                                    $tx_description = __( 'Transaction completed successfully.', 'ezzy-wallet' );
                                            }
                                        }
                                        
                                        $tx_amount = (float) $tx['amount'];
                                        $tx_amount_display = Ezzy_Wallet_Data_Service::convert_from_usd( abs( $tx_amount ), $user_currency );
                                        
                                        // Determine if positive or negative based on type
                                        $positive_types = array( 'deposit', 'deposit_success', 'deposit_request', 'transfer_received' );
                                        $is_positive = in_array( $tx_type, $positive_types, true );
                                        $tx_amount_class = $is_positive ? 'amount-positive' : 'amount-negative';
                                        $tx_amount_prefix = $is_positive ? '+' : '-';
                                        $tx_amount_formatted = $tx_amount_prefix . $currency_symbol . number_format( $tx_amount_display, 2 );
                                        
                                        $tx_date = date_i18n( 'M d, Y', strtotime( $tx['created_at'] ) );
                                        $tx_time = date_i18n( 'g:i A', strtotime( $tx['created_at'] ) );
                                    ?>
                                    <tr>
                                        <td class="col-type">
                                            <span class="ezzy-type-badge ezzy-type-<?php echo esc_attr( $tx_type_display ); ?>"><?php echo esc_html( $tx_type_label ); ?></span>
                                        </td>
                                        <td class="col-description">
                                            <span class="ezzy-tx-description" title="<?php echo esc_attr( $tx_description ); ?>"><?php echo esc_html( $tx_description ); ?></span>
                                        </td>
                                        <td class="col-amount">
                                            <span class="ezzy-tx-amount <?php echo esc_attr( $tx_amount_class ); ?>">
                                                <?php echo esc_html( $tx_amount_formatted ); ?>
                                            </span>
                                        </td>
                                        <td class="col-status">
                                            <span class="ezzy-tx-status ezzy-status-<?php echo esc_attr( $tx['status'] ); ?>">
                                                <?php echo esc_html( ucfirst( $tx['status'] ) ); ?>
                                            </span>
                                        </td>
                                        <td class="col-date ezzy-hide-mobile">
                                            <span class="ezzy-tx-date"><?php echo esc_html( $tx_date ); ?></span>
                                            <span class="ezzy-tx-time"><?php echo esc_html( $tx_time ); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- End Dashboard Panel -->

            <!-- Messages Panel -->
            <div class="ezzy-panel ezzy-panel-messages" id="ezzy-panel-messages">
                <div class="ezzy-card">
                    <div class="ezzy-panel-header">
                        <div class="ezzy-panel-header-top">
                            <h2 class="ezzy-panel-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                </svg>
                                <?php esc_html_e( 'Messages', 'ezzy-wallet' ); ?>
                            </h2>
                            <button type="button" class="ezzy-delete-all-btn" id="ezzy-delete-all-messages">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                <?php esc_html_e( 'Delete All', 'ezzy-wallet' ); ?>
                            </button>
                        </div>
                        <span class="ezzy-panel-subtitle"><?php esc_html_e( 'Your notifications and profile updates', 'ezzy-wallet' ); ?></span>
                        <div class="ezzy-messages-info">
                            <span class="ezzy-total-messages" id="ezzy-total-messages"></span>
                        </div>
                    </div>
                    
                    <div class="ezzy-notifications-list" id="ezzy-notifications-list">
                        <div class="ezzy-loading-state">
                            <div class="ezzy-spinner"></div>
                            <p><?php esc_html_e( 'Loading messages...', 'ezzy-wallet' ); ?></p>
                        </div>
                    </div>

                    <!-- Messages Pagination -->
                    <div class="ezzy-pagination ezzy-messages-pagination" id="ezzy-messages-pagination" style="display:none;">
                        <div class="ezzy-pagination-info">
                            <span class="ezzy-page-info" id="ezzy-messages-page-info">
                                <?php esc_html_e( 'Page 1 of 1', 'ezzy-wallet' ); ?>
                            </span>
                        </div>
                        <div class="ezzy-pagination-controls">
                            <button type="button" class="ezzy-pagination-btn ezzy-prev-btn" id="ezzy-messages-prev-page" disabled>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                                <span><?php esc_html_e( 'Previous', 'ezzy-wallet' ); ?></span>
                            </button>
                            <div class="ezzy-page-numbers" id="ezzy-messages-page-numbers">
                            </div>
                            <button type="button" class="ezzy-pagination-btn ezzy-next-btn" id="ezzy-messages-next-page" disabled>
                                <span><?php esc_html_e( 'Next', 'ezzy-wallet' ); ?></span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div class="ezzy-empty-messages" id="ezzy-empty-messages" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                        <h3><?php esc_html_e( 'No Messages', 'ezzy-wallet' ); ?></h3>
                        <p><?php esc_html_e( 'You have no messages or notifications at the moment.', 'ezzy-wallet' ); ?></p>
                    </div>
                </div>
            </div><!-- End Messages Panel -->

            <!-- Profile Panel -->
            <div class="ezzy-panel ezzy-panel-profile" id="ezzy-panel-profile">
                <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/profile-panel.php'; ?>
            </div><!-- End Profile Panel -->

            <!-- Transactions Panel -->
            <div class="ezzy-panel ezzy-panel-transactions" id="ezzy-panel-transactions">
                <div class="ezzy-card">
                    <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/transactions-panel.php'; ?>
                </div>
            </div><!-- End Transactions Panel -->

            <!-- Account Settings Panel -->
            <div class="ezzy-panel ezzy-panel-settings" id="ezzy-panel-settings">
                <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/settings-panel.php'; ?>
            </div><!-- End Account Settings Panel -->

            <!-- Support Panel -->
            <div class="ezzy-panel ezzy-panel-support" id="ezzy-panel-support">
                <?php include EZZY_WALLET_PLUGIN_DIR . 'public/partials/support-panel.php'; ?>
            </div><!-- End Support Panel -->

        </div>
    </main>

    <!-- MOBILE FOOTER NAVIGATION -->
    <nav class="ezzy-footer-nav">
        <a href="#" class="ezzy-footer-item active" data-page="dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="#" class="ezzy-footer-item ezzy-footer-messages" data-page="messages">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
            </svg>
            <span>Messages</span>
            <?php if ( $unread_notification_count > 0 ) : ?>
                <span class="ezzy-notification-badge" id="ezzy-footer-badge"><?php echo esc_html( $unread_notification_count ); ?></span>
            <?php else : ?>
                <span class="ezzy-notification-badge" id="ezzy-footer-badge" style="display:none;">0</span>
            <?php endif; ?>
        </a>
        <a href="#" class="ezzy-footer-item" data-page="profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/>
            </svg>
            <span>Profile</span>
        </a>
        <a href="#" class="ezzy-footer-item" data-page="transactions">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M9 12h6M9 16h6"/>
            </svg>
            <span>History</span>
        </a>
    </nav>
</div>

<!-- WALLET ACTION MODALS -->

<!-- Deposit Modal -->
<div class="ezzy-modal-overlay ezzy-wallet-modal" id="ezzy-deposit-modal" style="display:none;">
    <div class="ezzy-modal ezzy-action-modal">
        <div class="ezzy-modal-header">
            <div class="ezzy-modal-icon ezzy-modal-icon-deposit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'Deposit Funds', 'ezzy-wallet' ); ?></h3>
            <button type="button" class="ezzy-modal-close" data-close-modal>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="ezzy-modal-body">
            <form id="ezzy-deposit-form" class="ezzy-wallet-form">
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Amount (USD)', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <div class="ezzy-amount-input">
                        <span class="ezzy-amount-prefix">$</span>
                        <input type="number" id="ezzy-deposit-amount" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                </div>
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Deposit Method', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <div class="ezzy-method-options" id="ezzy-deposit-methods">
                        <label class="ezzy-method-option">
                            <input type="radio" name="deposit_method" value="bitcoin" required>
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-bitcoin">₿</span>
                                <span class="ezzy-method-name">Bitcoin</span>
                            </span>
                        </label>
                        <label class="ezzy-method-option">
                            <input type="radio" name="deposit_method" value="paypal">
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-paypal">P</span>
                                <span class="ezzy-method-name">PayPal</span>
                            </span>
                        </label>
                        <label class="ezzy-method-option">
                            <input type="radio" name="deposit_method" value="cashapp">
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-cashapp">$</span>
                                <span class="ezzy-method-name">CashApp</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Note', 'ezzy-wallet' ); ?> <span class="ezzy-optional">(<?php esc_html_e( 'Optional', 'ezzy-wallet' ); ?>)</span></label>
                    <input type="text" id="ezzy-deposit-note" placeholder="<?php esc_attr_e( 'Reference or note...', 'ezzy-wallet' ); ?>">
                </div>
            </form>
            <div class="ezzy-modal-result" id="ezzy-deposit-result" style="display:none;"></div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="ezzy-btn ezzy-btn-secondary" data-close-modal><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-submit-deposit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg>
                <?php esc_html_e( 'Submit Request', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="ezzy-modal-overlay ezzy-wallet-modal" id="ezzy-withdraw-modal" style="display:none;">
    <div class="ezzy-modal ezzy-action-modal">
        <div class="ezzy-modal-header">
            <div class="ezzy-modal-icon ezzy-modal-icon-withdraw">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 19V5m-7 7l7-7 7 7"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'Withdraw Funds', 'ezzy-wallet' ); ?></h3>
            <button type="button" class="ezzy-modal-close" data-close-modal>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="ezzy-modal-body">
            <div class="ezzy-available-balance">
                <span class="ezzy-balance-label"><?php esc_html_e( 'Available Balance:', 'ezzy-wallet' ); ?></span>
                <span class="ezzy-balance-value" id="ezzy-withdraw-available"><?php echo esc_html( $currency_symbol . $balance_formatted ); ?></span>
            </div>
            <form id="ezzy-withdraw-form" class="ezzy-wallet-form">
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Amount (USD)', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <div class="ezzy-amount-input">
                        <span class="ezzy-amount-prefix">$</span>
                        <input type="number" id="ezzy-withdraw-amount" step="0.01" min="0.01" max="<?php echo esc_attr( $balance ); ?>" placeholder="0.00" required>
                    </div>
                </div>
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Withdrawal Method', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <div class="ezzy-method-options ezzy-method-4" id="ezzy-withdraw-methods">
                        <label class="ezzy-method-option">
                            <input type="radio" name="withdraw_method" value="bitcoin" required>
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-bitcoin">₿</span>
                                <span class="ezzy-method-name">Bitcoin</span>
                            </span>
                        </label>
                        <label class="ezzy-method-option">
                            <input type="radio" name="withdraw_method" value="paypal">
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-paypal">P</span>
                                <span class="ezzy-method-name">PayPal</span>
                            </span>
                        </label>
                        <label class="ezzy-method-option">
                            <input type="radio" name="withdraw_method" value="cashapp">
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-cashapp">$</span>
                                <span class="ezzy-method-name">CashApp</span>
                            </span>
                        </label>
                        <label class="ezzy-method-option">
                            <input type="radio" name="withdraw_method" value="bank_transfer">
                            <span class="ezzy-method-card">
                                <span class="ezzy-method-icon ezzy-icon-bank">🏦</span>
                                <span class="ezzy-method-name">Bank</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="ezzy-form-group" id="ezzy-withdraw-destination-group" style="display:none;">
                    <label id="ezzy-withdraw-destination-label"><?php esc_html_e( 'Destination', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                    <input type="text" id="ezzy-withdraw-destination" placeholder="" required>
                </div>
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Note', 'ezzy-wallet' ); ?> <span class="ezzy-optional">(<?php esc_html_e( 'Optional', 'ezzy-wallet' ); ?>)</span></label>
                    <input type="text" id="ezzy-withdraw-note" placeholder="<?php esc_attr_e( 'Add any note...', 'ezzy-wallet' ); ?>">
                </div>
            </form>
            <div class="ezzy-modal-result" id="ezzy-withdraw-result" style="display:none;"></div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="ezzy-btn ezzy-btn-secondary" data-close-modal><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-submit-withdraw">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5m-7 7l7-7 7 7"/></svg>
                <?php esc_html_e( 'Submit Request', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div class="ezzy-modal-overlay ezzy-wallet-modal" id="ezzy-transfer-modal" style="display:none;">
    <div class="ezzy-modal ezzy-action-modal">
        <div class="ezzy-modal-header">
            <div class="ezzy-modal-icon ezzy-modal-icon-transfer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'Transfer Funds', 'ezzy-wallet' ); ?></h3>
            <button type="button" class="ezzy-modal-close" data-close-modal>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="ezzy-modal-body">
            <div class="ezzy-available-balance">
                <span class="ezzy-balance-label"><?php esc_html_e( 'Available:', 'ezzy-wallet' ); ?></span>
                <span class="ezzy-balance-value" id="ezzy-transfer-available"><?php echo esc_html( $currency_symbol . $balance_formatted ); ?></span>
            </div>
            <form id="ezzy-transfer-form" class="ezzy-wallet-form">
                <div class="ezzy-form-row-2col">
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Recipient', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                        <input type="text" id="ezzy-transfer-recipient" placeholder="<?php esc_attr_e( 'Username or email', 'ezzy-wallet' ); ?>" required>
                    </div>
                    <div class="ezzy-form-group">
                        <label><?php esc_html_e( 'Amount', 'ezzy-wallet' ); ?> <span class="ezzy-required">*</span></label>
                        <div class="ezzy-amount-input">
                            <span class="ezzy-amount-prefix">$</span>
                            <input type="number" id="ezzy-transfer-amount" step="0.01" min="0.01" max="<?php echo esc_attr( $balance ); ?>" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="ezzy-form-group">
                    <label><?php esc_html_e( 'Message', 'ezzy-wallet' ); ?> <span class="ezzy-optional">(<?php esc_html_e( 'Optional', 'ezzy-wallet' ); ?>)</span></label>
                    <input type="text" id="ezzy-transfer-message" placeholder="<?php esc_attr_e( 'Message for recipient...', 'ezzy-wallet' ); ?>">
                </div>
            </form>
            <div class="ezzy-modal-result" id="ezzy-transfer-result" style="display:none;"></div>
        </div>
        <div class="ezzy-modal-footer">
            <button type="button" class="ezzy-btn ezzy-btn-secondary" data-close-modal><?php esc_html_e( 'Cancel', 'ezzy-wallet' ); ?></button>
            <button type="button" class="ezzy-btn ezzy-btn-primary" id="ezzy-submit-transfer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                <?php esc_html_e( 'Send', 'ezzy-wallet' ); ?>
            </button>
        </div>
    </div>
</div>
