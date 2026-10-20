<?php
/**
 * All Transactions Panel Template
 *
 * Displays full transaction history with pagination.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$user_currency = Ezzy_Wallet_Data_Service::get_user_currency_preference( $current_user->ID );
$currency_symbol = Ezzy_Wallet_Data_Service::get_currency_symbol( $user_currency );
?>

<div class="ezzy-transactions-panel">
    <div class="ezzy-panel-header">
        <div class="ezzy-panel-header-top">
            <h2 class="ezzy-panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <path d="M9 12h6M9 16h6"/>
                </svg>
                <?php esc_html_e( 'All Transactions', 'ezzy-wallet' ); ?>
            </h2>
            <button type="button" class="ezzy-delete-all-btn" id="ezzy-delete-all-transactions">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
                <?php esc_html_e( 'Delete All', 'ezzy-wallet' ); ?>
            </button>
        </div>
        <span class="ezzy-panel-subtitle"><?php esc_html_e( 'Complete history of all your transactions', 'ezzy-wallet' ); ?></span>
    </div>

    <!-- Filter Bar -->
    <div class="ezzy-transactions-filters">
        <div class="ezzy-filter-info">
            <span class="ezzy-total-transactions" id="ezzy-total-transactions">
                <?php esc_html_e( 'Loading...', 'ezzy-wallet' ); ?>
            </span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="ezzy-transactions-table-wrapper">
        <table class="ezzy-transactions-table ezzy-full-table">
            <thead>
                <tr>
                    <th class="col-type"><?php esc_html_e( 'Type', 'ezzy-wallet' ); ?></th>
                    <th class="col-description"><?php esc_html_e( 'Description', 'ezzy-wallet' ); ?></th>
                    <th class="col-amount"><?php esc_html_e( 'Amount', 'ezzy-wallet' ); ?></th>
                    <th class="col-status"><?php esc_html_e( 'Status', 'ezzy-wallet' ); ?></th>
                    <th class="col-date"><?php esc_html_e( 'Date', 'ezzy-wallet' ); ?></th>
                </tr>
            </thead>
            <tbody id="ezzy-all-transactions-body">
                <tr class="ezzy-loading-row">
                    <td colspan="5">
                        <div class="ezzy-loading-state">
                            <div class="ezzy-spinner"></div>
                            <p><?php esc_html_e( 'Loading transactions...', 'ezzy-wallet' ); ?></p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="ezzy-pagination" id="ezzy-transactions-pagination">
        <div class="ezzy-pagination-info">
            <span class="ezzy-page-info" id="ezzy-page-info">
                <?php esc_html_e( 'Page 1 of 1', 'ezzy-wallet' ); ?>
            </span>
        </div>
        <div class="ezzy-pagination-controls">
            <button type="button" class="ezzy-pagination-btn ezzy-prev-btn" id="ezzy-prev-page" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <span><?php esc_html_e( 'Previous', 'ezzy-wallet' ); ?></span>
            </button>
            <div class="ezzy-page-numbers" id="ezzy-page-numbers">
                <!-- Page numbers will be inserted here via JS -->
            </div>
            <button type="button" class="ezzy-pagination-btn ezzy-next-btn" id="ezzy-next-page" disabled>
                <span><?php esc_html_e( 'Next', 'ezzy-wallet' ); ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Empty State -->
    <div class="ezzy-empty-transactions" id="ezzy-empty-transactions" style="display:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
        </svg>
        <h3><?php esc_html_e( 'No Transactions Yet', 'ezzy-wallet' ); ?></h3>
        <p><?php esc_html_e( 'Your transaction history will appear here once you make your first deposit, withdrawal, or transfer.', 'ezzy-wallet' ); ?></p>
    </div>
</div>
