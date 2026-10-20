/**
 * eZZY Wallet Dashboard JavaScript
 * Smooth balance toggle, currency switching, and navigation handling
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initBalanceToggle();
        initNavigation();
        initCurrencySwitcher();
    });

    /**
     * Balance Toggle Functionality
     * Smoothly shows/hides the balance amount
     */
    function initBalanceToggle() {
        var $toggleBtn = $('#ezzy-balance-toggle');
        var $balanceDisplay = $('#ezzy-balance-display');

        if (!$toggleBtn.length || !$balanceDisplay.length) {
            return;
        }

        // Store the actual balance value from data attribute
        var actualBalance = $balanceDisplay.attr('data-balance') || $balanceDisplay.text();
        var hiddenBalance = '••••••••';
        var isHidden = false;

        // Click handler
        $toggleBtn.on('click', function(e) {
            e.preventDefault();
            
            isHidden = !isHidden;

            if (isHidden) {
                // Hide balance with smooth transition
                $balanceDisplay.css('opacity', '0');
                
                setTimeout(function() {
                    $balanceDisplay.text(hiddenBalance);
                    $balanceDisplay.addClass('is-hidden');
                    $toggleBtn.addClass('is-hidden');
                    $balanceDisplay.css('opacity', '1');
                }, 150);
                
                $toggleBtn.attr('aria-label', 'Show balance');
            } else {
                // Show balance with smooth transition
                $balanceDisplay.css('opacity', '0');
                
                setTimeout(function() {
                    $balanceDisplay.text(actualBalance);
                    $balanceDisplay.removeClass('is-hidden');
                    $toggleBtn.removeClass('is-hidden');
                    $balanceDisplay.css('opacity', '1');
                }, 150);
                
                $toggleBtn.attr('aria-label', 'Hide balance');
            }
        });

        // Add transition style
        $balanceDisplay.css('transition', 'opacity 0.15s ease');
    }

    /**
     * Currency Switcher Functionality
     */
    function initCurrencySwitcher() {
        var $toggleBtn = $('#ezzy-currency-toggle');
        var $dropdown = $('#ezzy-currency-dropdown');
        var $currentCurrency = $('#ezzy-current-currency');
        var $options = $('.ezzy-currency-option');

        if (!$toggleBtn.length || !$dropdown.length) {
            return;
        }

        // Toggle dropdown
        $toggleBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $dropdown.toggleClass('is-open');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.ezzy-currency-switcher').length) {
                $dropdown.removeClass('is-open');
            }
        });

        // Handle currency selection
        $options.on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var currency = $this.data('currency');
            
            if ($this.hasClass('active')) {
                $dropdown.removeClass('is-open');
                return;
            }

            // Show loading state
            $toggleBtn.prop('disabled', true);
            $currentCurrency.text('...');

            // Make AJAX request
            $.ajax({
                url: ezzyWalletFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ezzy_wallet_switch_currency',
                    nonce: ezzyWalletFrontend.nonce,
                    currency: currency
                },
                success: function(response) {
                    if (response.success) {
                        // Update currency display
                        $currentCurrency.text(response.data.currency);
                        
                        // Update active state
                        $options.removeClass('active');
                        $this.addClass('active');
                        
                        // Update balance
                        updateBalanceDisplay(response.data.balance_formatted);
                        
                        // Update crypto values
                        updateCryptoValues(response.data.crypto);
                        
                        // Update transactions (amounts)
                        if (response.data.transactions) {
                            updateTransactionsTable(response.data.transactions);
                        }
                    } else {
                        alert(response.data.message || 'Failed to switch currency');
                        $currentCurrency.text($('.ezzy-currency-option.active').data('currency'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $currentCurrency.text($('.ezzy-currency-option.active').data('currency'));
                },
                complete: function() {
                    $toggleBtn.prop('disabled', false);
                    $dropdown.removeClass('is-open');
                }
            });
        });
    }

    /**
     * Update balance display
     */
    function updateBalanceDisplay(formattedBalance) {
        var $balanceDisplay = $('#ezzy-balance-display');
        var $toggleBtn = $('#ezzy-balance-toggle');
        
        // Store new balance
        $balanceDisplay.attr('data-balance', formattedBalance);
        
        // Update display if not hidden
        if (!$balanceDisplay.hasClass('is-hidden')) {
            $balanceDisplay.css('opacity', '0');
            setTimeout(function() {
                $balanceDisplay.text(formattedBalance);
                $balanceDisplay.css('opacity', '1');
            }, 150);
        }
    }

    /**
     * Update crypto conversion values
     */
    function updateCryptoValues(crypto) {
        if (!crypto) return;
        
        if (crypto.btc) {
            $('#ezzy-crypto-btc').text(crypto.btc);
        }
        if (crypto.eth) {
            $('#ezzy-crypto-eth').text(crypto.eth);
        }
        if (crypto.usdt) {
            $('#ezzy-crypto-usdt').text(crypto.usdt);
        }
    }

    /**
     * Update transactions table
     */
    function updateTransactionsTable(transactions) {
        var $tbody = $('#ezzy-transactions-body');
        if (!$tbody.length || !transactions.length) return;
        
        var html = '';
        transactions.forEach(function(tx) {
            html += '<tr>';
            html += '<td class="ezzy-tx-type">' + escapeHtml(tx.type) + '</td>';
            html += '<td class="ezzy-tx-amount ' + escapeHtml(tx.amount_class) + '">' + escapeHtml(tx.amount) + '</td>';
            html += '<td><span class="ezzy-tx-status ezzy-status-' + escapeHtml(tx.status) + '">' + escapeHtml(capitalizeFirst(tx.status)) + '</span></td>';
            html += '<td class="ezzy-tx-date ezzy-hide-mobile">' + escapeHtml(tx.date) + '</td>';
            html += '</tr>';
        });
        
        $tbody.html(html);
    }

    /**
     * Helper: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    /**
     * Helper: Capitalize first letter
     */
    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /**
     * Navigation Functionality
     * Handles active states for sidebar and footer nav, and panel switching
     */
    function initNavigation() {
        var $sidebarLinks = $('.ezzy-nav-list a');
        var $footerLinks = $('.ezzy-footer-item');
        var $othersLinks = $('.ezzy-others-btn');
        var $panels = $('.ezzy-panel');

        // Combined click handler
        function handleNavClick(e) {
            e.preventDefault();
            
            var page = $(this).data('page');
            if (!page) return;

            // Update sidebar active state
            $sidebarLinks.each(function() {
                var $link = $(this);
                var $li = $link.parent();
                if ($link.data('page') === page) {
                    $li.addClass('active');
                } else {
                    $li.removeClass('active');
                }
            });

            // Update footer active state
            $footerLinks.each(function() {
                var $link = $(this);
                if ($link.data('page') === page) {
                    $link.addClass('active');
                } else {
                    $link.removeClass('active');
                }
            });

            // Update others menu active state
            $othersLinks.each(function() {
                var $link = $(this);
                if ($link.data('page') === page) {
                    $link.addClass('active');
                } else {
                    $link.removeClass('active');
                }
            });

            // Switch panels
            switchPanel(page);
        }

        // Attach click handlers
        $sidebarLinks.on('click', handleNavClick);
        $footerLinks.on('click', handleNavClick);
        $othersLinks.on('click', handleNavClick);
    }

    /**
     * Switch between panels
     */
    function switchPanel(page) {
        var $panels = $('.ezzy-panel');
        var $targetPanel = $('#ezzy-panel-' + page);

        // Hide all panels
        $panels.removeClass('active');

        // Show target panel
        if ($targetPanel.length) {
            $targetPanel.addClass('active');

            // Messages panel now uses paginated loading via initMessagesPanel event handler
            // Transactions panel uses loadTransactionsPage via initAllTransactions event handler
        } else {
            // Default to dashboard if panel doesn't exist
            $('#ezzy-panel-dashboard').addClass('active');
        }

        // Refresh dashboard data and notification counts when switching panels
        refreshDashboardData(page);
    }

    /**
     * Refresh dashboard data when switching panels
     * Silently updates balance, transactions, and notification badges in background
     */
    function refreshDashboardData(currentPage) {
        // Always refresh notification badge count
        refreshNotificationBadge();

        // Always refresh dashboard data silently in background
        // This ensures balance and transactions are always up-to-date
        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_get_dashboard_data',
                nonce: ezzyWalletFrontend.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Update balance display (always visible in header)
                    updateBalanceDisplay(response.data.balance_formatted);
                    
                    // Update crypto values
                    updateCryptoValues(response.data.crypto);
                    
                    // Update recent transactions table on dashboard panel
                    if (response.data.transactions) {
                        updateTransactionsTable(response.data.transactions);
                    }
                }
            }
        });
    }

    /**
     * Refresh notification badge count
     */
    function refreshNotificationBadge() {
        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_get_notifications',
                nonce: ezzyWalletFrontend.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateNotificationBadges(response.data.unread_count);
                }
            }
        });
    }

    /**
     * Show notification error
     */
    function showNotificationError(message) {
        var $list = $('#ezzy-notifications-list');
        $list.html(
            '<div class="ezzy-no-notifications">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                    '<circle cx="12" cy="12" r="10"/>' +
                    '<path d="M12 8v4M12 16h.01"/>' +
                '</svg>' +
                '<p>' + escapeHtml(message) + '</p>' +
            '</div>'
        );
    }

    /**
     * Mark all notifications as read
     */
    function markAllNotificationsRead() {
        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_mark_notifications_read',
                nonce: ezzyWalletFrontend.nonce,
                notification_id: 0
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationBadges(response.data.unread_count);
                }
            }
        });
    }

    /**
     * Update notification badges
     */
    function updateNotificationBadges(count) {
        var $sidebarBadge = $('#ezzy-sidebar-badge');
        var $footerBadge = $('#ezzy-footer-badge');

        if (count > 0) {
            $sidebarBadge.text(count).show();
            $footerBadge.text(count).show();
        } else {
            $sidebarBadge.hide();
            $footerBadge.hide();
        }
    }

    /**
     * Initialize Profile Panel
     */
    function initProfilePanel() {
        var $profilePanel = $('#ezzy-profile-panel');
        
        if (!$profilePanel.length) {
            return;
        }

        // Edit button click handler
        $profilePanel.on('click', '.ezzy-edit-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var field = $btn.data('field');
            var $fieldContainer = $btn.closest('.ezzy-profile-field');
            var $valueDisplay = $fieldContainer.find('.ezzy-profile-value');
            var $editForm = $fieldContainer.find('.ezzy-profile-edit');
            
            // Toggle edit mode
            if ($btn.hasClass('is-editing')) {
                // Cancel editing
                $btn.removeClass('is-editing');
                $editForm.slideUp(200);
                $valueDisplay.slideDown(200);
            } else {
                // Start editing
                $btn.addClass('is-editing');
                $valueDisplay.slideUp(200);
                $editForm.slideDown(200);
                
                // Focus the input
                setTimeout(function() {
                    $editForm.find('input, select, textarea').first().focus();
                }, 250);
            }
        });

        // Save field button click handler
        $profilePanel.on('click', '.ezzy-save-field-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var field = $btn.data('field');
            var $fieldContainer = $btn.closest('.ezzy-profile-field');
            
            // Prevent double submit
            if ($fieldContainer.hasClass('is-saving')) {
                return;
            }
            
            // Get the input value
            var $input = $fieldContainer.find('input, select, textarea').first();
            var value = $input.val();
            
            // Add saving state
            $fieldContainer.addClass('is-saving');
            $btn.prop('disabled', true);
            var originalText = $btn.text();
            $btn.text('Saving...');
            
            // Send AJAX request
            $.ajax({
                url: ezzyWalletFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ezzy_wallet_update_profile',
                    nonce: ezzyWalletFrontend.nonce,
                    field: field,
                    value: value
                },
                success: function(response) {
                    if (response.success) {
                        // Update the display value
                        var displayValue = response.data.display_value || value || '—';
                        $fieldContainer.find('.ezzy-field-text').text(displayValue);
                        
                        // Show success message
                        showProfileMessage('success', response.data.message);
                        
                        // Close edit mode
                        var $editBtn = $fieldContainer.find('.ezzy-edit-btn');
                        $editBtn.removeClass('is-editing');
                        $fieldContainer.find('.ezzy-profile-value').slideDown(200);
                        $fieldContainer.find('.ezzy-profile-edit').slideUp(200);
                        
                        // Update header if display_name changed
                        if (field === 'display_name') {
                            $('.ezzy-profile-name h2').text(value);
                        }
                    } else {
                        showProfileMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showProfileMessage('error', 'An error occurred. Please try again.');
                },
                complete: function() {
                    $fieldContainer.removeClass('is-saving');
                    $btn.prop('disabled', false);
                    $btn.text(originalText);
                }
            });
        });

        // Avatar edit click - open Gravatar
        $profilePanel.on('click', '.ezzy-avatar-edit', function(e) {
            e.preventDefault();
            window.open('https://gravatar.com/', '_blank');
        });
    }

    /**
     * Show profile message
     */
    function showProfileMessage(type, message) {
        var $successMsg = $('#ezzy-profile-success');
        var $errorMsg = $('#ezzy-profile-error');
        
        // Hide both first
        $successMsg.hide();
        $errorMsg.hide();
        
        // Show appropriate message
        if (type === 'success') {
            $successMsg.find('span').text(message);
            $successMsg.slideDown(200);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $successMsg.slideUp(200);
            }, 5000);
        } else {
            $errorMsg.find('span').text(message);
            $errorMsg.slideDown(200);
            
            // Auto-hide after 7 seconds
            setTimeout(function() {
                $errorMsg.slideUp(200);
            }, 7000);
        }
        
        // Scroll to message
        $('.ezzy-profile-content').animate({
            scrollTop: 0
        }, 200);
    }

    // Initialize profile panel when DOM is ready
    $(document).ready(function() {
        initProfilePanel();
        initAllTransactions();
    });

    /**
     * All Transactions Panel with Pagination
     */
    var transactionsState = {
        currentPage: 1,
        totalPages: 1,
        total: 0,
        isLoading: false
    };

    function initAllTransactions() {
        var $panel = $('#ezzy-panel-transactions');
        if (!$panel.length) {
            return;
        }

        // Bind pagination button events
        $('#ezzy-prev-page').on('click', function() {
            if (transactionsState.currentPage > 1) {
                loadTransactionsPage(transactionsState.currentPage - 1);
            }
        });

        $('#ezzy-next-page').on('click', function() {
            if (transactionsState.currentPage < transactionsState.totalPages) {
                loadTransactionsPage(transactionsState.currentPage + 1);
            }
        });

        // Bind page number clicks (delegated)
        $('#ezzy-page-numbers').on('click', '.ezzy-page-number', function() {
            var page = $(this).data('page');
            if (page && page !== transactionsState.currentPage) {
                loadTransactionsPage(page);
            }
        });

        // Load transactions when panel becomes visible - always refresh
        $(document).on('click', '[data-page="transactions"]', function() {
            // Small delay to let panel show, then always reload
            transactionsState.loaded = false;
            setTimeout(function() {
                if (!transactionsState.isLoading) {
                    loadTransactionsPage(1);
                }
            }, 100);
        });
    }

    function loadTransactionsPage(page) {
        if (transactionsState.isLoading) {
            return;
        }

        transactionsState.isLoading = true;
        var $tbody = $('#ezzy-all-transactions-body');
        var $pagination = $('#ezzy-transactions-pagination');
        var $emptyState = $('#ezzy-empty-transactions');

        // Show loading state
        $tbody.html(
            '<tr class="ezzy-loading-row"><td colspan="5">' +
            '<div class="ezzy-loading-state">' +
            '<div class="ezzy-spinner"></div>' +
            '<p>Loading transactions...</p>' +
            '</div></td></tr>'
        );

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_get_all_transactions',
                nonce: ezzyWalletFrontend.nonce,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    transactionsState.currentPage = data.current_page;
                    transactionsState.totalPages = data.total_pages;
                    transactionsState.total = data.total;

                    // Update total count display
                    $('#ezzy-total-transactions').text(
                        data.total + ' transaction' + (data.total !== 1 ? 's' : '') + ' total'
                    );

                    if (data.transactions.length === 0) {
                        // Show empty state
                        $tbody.closest('.ezzy-transactions-table-wrapper').hide();
                        $pagination.hide();
                        $emptyState.show();
                    } else {
                        // Render transactions
                        $emptyState.hide();
                        $tbody.closest('.ezzy-transactions-table-wrapper').show();
                        $pagination.show();
                        renderTransactionsTable(data.transactions);
                        renderPagination();
                    }
                } else {
                    $tbody.html(
                        '<tr><td colspan="5" class="ezzy-error-cell">' +
                        '<p>Error loading transactions. Please try again.</p>' +
                        '</td></tr>'
                    );
                }
            },
            error: function() {
                $tbody.html(
                    '<tr><td colspan="5" class="ezzy-error-cell">' +
                    '<p>Error loading transactions. Please try again.</p>' +
                    '</td></tr>'
                );
            },
            complete: function() {
                transactionsState.isLoading = false;
            }
        });
    }

    function renderTransactionsTable(transactions) {
        var $tbody = $('#ezzy-all-transactions-body');
        var html = '';

        for (var i = 0; i < transactions.length; i++) {
            var tx = transactions[i];
            html += '<tr>' +
                '<td class="col-type">' +
                    '<span class="ezzy-tx-type ezzy-type-' + tx.type + '">' + escapeHtml(tx.type_label) + '</span>' +
                '</td>' +
                '<td class="col-description">' +
                    '<span class="ezzy-tx-description" title="' + escapeHtml(tx.description) + '">' + escapeHtml(tx.description) + '</span>' +
                '</td>' +
                '<td class="col-amount">' +
                    '<span class="ezzy-tx-amount ' + tx.amount_class + '">' + tx.amount_formatted + '</span>' +
                '</td>' +
                '<td class="col-status">' +
                    '<span class="ezzy-tx-status ezzy-status-' + tx.status + '">' + capitalizeFirst(tx.status) + '</span>' +
                '</td>' +
                '<td class="col-date">' +
                    '<span class="ezzy-tx-date">' + tx.date + '</span>' +
                    '<span class="ezzy-tx-time">' + tx.time + '</span>' +
                '</td>' +
                '</tr>';
        }

        $tbody.html(html);
    }

    function renderPagination() {
        var $pageNumbers = $('#ezzy-page-numbers');
        var $prevBtn = $('#ezzy-prev-page');
        var $nextBtn = $('#ezzy-next-page');
        var $pageInfo = $('#ezzy-page-info');

        var current = transactionsState.currentPage;
        var total = transactionsState.totalPages;

        // Update page info
        $pageInfo.text('Page ' + current + ' of ' + total);

        // Enable/disable prev/next buttons
        $prevBtn.prop('disabled', current <= 1);
        $nextBtn.prop('disabled', current >= total);

        // Build page numbers
        var html = '';
        var maxVisible = 5; // Maximum visible page numbers
        var startPage = 1;
        var endPage = total;

        // Calculate which pages to show
        if (total > maxVisible) {
            var halfVisible = Math.floor(maxVisible / 2);
            startPage = Math.max(1, current - halfVisible);
            endPage = Math.min(total, startPage + maxVisible - 1);

            // Adjust start if we're near the end
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
        }

        // First page + ellipsis if needed
        if (startPage > 1) {
            html += '<button type="button" class="ezzy-page-number" data-page="1">1</button>';
            if (startPage > 2) {
                html += '<span class="ezzy-page-ellipsis">...</span>';
            }
        }

        // Page numbers
        for (var i = startPage; i <= endPage; i++) {
            var activeClass = (i === current) ? 'active' : '';
            html += '<button type="button" class="ezzy-page-number ' + activeClass + '" data-page="' + i + '">' + i + '</button>';
        }

        // Last page + ellipsis if needed
        if (endPage < total) {
            if (endPage < total - 1) {
                html += '<span class="ezzy-page-ellipsis">...</span>';
            }
            html += '<button type="button" class="ezzy-page-number" data-page="' + total + '">' + total + '</button>';
        }

        $pageNumbers.html(html);
    }

    /**
     * Helper: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Helper: Capitalize first letter
     */
    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /* ==========================================================================
       MESSAGES PANEL WITH PAGINATION
       ========================================================================== */

    var messagesState = {
        currentPage: 1,
        totalPages: 1,
        total: 0,
        isLoading: false,
        loaded: false
    };

    // Initialize messages when DOM ready
    $(document).ready(function() {
        initMessagesPanel();
        initDeleteAllButtons();
    });

    function initMessagesPanel() {
        var $panel = $('#ezzy-panel-messages');
        if (!$panel.length) {
            return;
        }

        // Bind pagination button events
        $(document).on('click', '#ezzy-messages-prev-page', function() {
            if (messagesState.currentPage > 1 && !messagesState.isLoading) {
                loadMessagesPage(messagesState.currentPage - 1);
            }
        });

        $(document).on('click', '#ezzy-messages-next-page', function() {
            if (messagesState.currentPage < messagesState.totalPages && !messagesState.isLoading) {
                loadMessagesPage(messagesState.currentPage + 1);
            }
        });

        // Bind page number clicks (delegated)
        $(document).on('click', '#ezzy-messages-page-numbers .ezzy-page-number', function() {
            var page = parseInt($(this).data('page'));
            if (page && page !== messagesState.currentPage && !messagesState.isLoading) {
                loadMessagesPage(page);
            }
        });

        // Bind notification expand/collapse (delegated)
        $(document).on('click', '#ezzy-notifications-list .ezzy-notification-header', function(e) {
            e.preventDefault();
            var $item = $(this).closest('.ezzy-notification-item');
            var $message = $item.find('.ezzy-notification-message');
            var $arrow = $item.find('.ezzy-notification-arrow');
            
            if ($item.hasClass('is-expanded')) {
                $item.removeClass('is-expanded');
                $message.slideUp(200);
                $arrow.removeClass('is-open');
            } else {
                $item.addClass('is-expanded');
                $message.slideDown(200);
                $arrow.addClass('is-open');
                
                // Mark as read if unread
                if ($item.hasClass('is-unread')) {
                    markNotificationRead($item.data('id'));
                    $item.removeClass('is-unread').addClass('is-read');
                }
            }
        });

        // Load messages when panel becomes visible
        $(document).on('click', '[data-page="messages"]', function() {
            // Always load when clicking messages tab (reset loaded state)
            messagesState.loaded = false;
            setTimeout(function() {
                if (!messagesState.isLoading) {
                    loadMessagesPage(1);
                }
            }, 100);
        });
    }

    function loadMessagesPage(page) {
        if (messagesState.isLoading) {
            return;
        }

        messagesState.isLoading = true;
        var $list = $('#ezzy-notifications-list');
        var $pagination = $('#ezzy-messages-pagination');
        var $emptyState = $('#ezzy-empty-messages');
        var $tableWrapper = $list;

        // Show loading state
        $list.html(
            '<div class="ezzy-loading-state">' +
            '<div class="ezzy-spinner"></div>' +
            '<p>Loading messages...</p>' +
            '</div>'
        ).show();
        $emptyState.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_get_notifications_paginated',
                nonce: ezzyWalletFrontend.nonce,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    messagesState.currentPage = parseInt(data.current_page);
                    messagesState.totalPages = parseInt(data.total_pages);
                    messagesState.total = parseInt(data.total);
                    messagesState.loaded = true;

                    // Update total count display
                    $('#ezzy-total-messages').text(
                        data.total + ' message' + (data.total !== 1 ? 's' : '') + ' total'
                    );

                    // Update notification badges
                    if (typeof updateNotificationBadges === 'function') {
                        updateNotificationBadges(data.unread_count);
                    }

                    if (data.notifications.length === 0) {
                        // Show empty state
                        $list.hide();
                        $pagination.hide();
                        $emptyState.show();
                    } else {
                        // Render messages
                        $emptyState.hide();
                        $list.show();
                        renderMessagesList(data.notifications);
                        
                        if (data.total_pages > 1) {
                            $pagination.show();
                            renderMessagesPagination();
                        } else {
                            $pagination.hide();
                        }
                    }
                } else {
                    $list.html(
                        '<div class="ezzy-error-state">' +
                        '<p>Error loading messages. Please try again.</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $list.html(
                    '<div class="ezzy-error-state">' +
                    '<p>Error loading messages. Please try again.</p>' +
                    '</div>'
                );
            },
            complete: function() {
                messagesState.isLoading = false;
            }
        });
    }

    function renderMessagesList(notifications) {
        var $list = $('#ezzy-notifications-list');
        var html = '';

        for (var i = 0; i < notifications.length; i++) {
            var notification = notifications[i];
            var readClass = notification.is_read ? 'is-read' : 'is-unread';
            var sourceIcon = notification.source === 'system' ? 
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>' :
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>';

            // Arrow icon for expand/collapse
            var arrowIcon = '<svg class="ezzy-notification-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';

            html += '<div class="ezzy-notification-item ' + readClass + '" data-id="' + notification.id + '">' +
                    '<div class="ezzy-notification-icon">' + sourceIcon + '</div>' +
                    '<div class="ezzy-notification-content">' +
                    '<div class="ezzy-notification-header">' +
                    '<div class="ezzy-notification-title-row">' +
                    '<h4 class="ezzy-notification-title">' + escapeHtml(notification.title) + '</h4>' +
                    arrowIcon +
                    '</div>' +
                    '<span class="ezzy-notification-date">' + notification.date + ' ' + notification.time + '</span>' +
                    '</div>' +
                    '<p class="ezzy-notification-message" style="display:none;">' + escapeHtml(notification.message) + '</p>' +
                    '</div>' +
                    '</div>';
        }

        $list.html(html);
    }

    function markNotificationRead(notificationId) {
        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_mark_notifications_read',
                nonce: ezzyWalletFrontend.nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success && typeof updateNotificationBadges === 'function') {
                    updateNotificationBadges(response.data.unread_count);
                }
            }
        });
    }

    function renderMessagesPagination() {
        var $pageNumbers = $('#ezzy-messages-page-numbers');
        var $prevBtn = $('#ezzy-messages-prev-page');
        var $nextBtn = $('#ezzy-messages-next-page');
        var $pageInfo = $('#ezzy-messages-page-info');

        var current = messagesState.currentPage;
        var total = messagesState.totalPages;

        // Update page info
        $pageInfo.text('Page ' + current + ' of ' + total);

        // Enable/disable prev/next buttons
        $prevBtn.prop('disabled', current <= 1);
        $nextBtn.prop('disabled', current >= total);

        // Build page numbers
        var html = '';
        var maxVisible = 5;
        var startPage = 1;
        var endPage = total;

        if (total > maxVisible) {
            var halfVisible = Math.floor(maxVisible / 2);
            startPage = Math.max(1, current - halfVisible);
            endPage = Math.min(total, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
        }

        if (startPage > 1) {
            html += '<button type="button" class="ezzy-page-number" data-page="1">1</button>';
            if (startPage > 2) {
                html += '<span class="ezzy-page-ellipsis">...</span>';
            }
        }

        for (var i = startPage; i <= endPage; i++) {
            var activeClass = (i === current) ? 'active' : '';
            html += '<button type="button" class="ezzy-page-number ' + activeClass + '" data-page="' + i + '">' + i + '</button>';
        }

        if (endPage < total) {
            if (endPage < total - 1) {
                html += '<span class="ezzy-page-ellipsis">...</span>';
            }
            html += '<button type="button" class="ezzy-page-number" data-page="' + total + '">' + total + '</button>';
        }

        $pageNumbers.html(html);
    }

    /* ==========================================================================
       DELETE ALL FUNCTIONALITY
       ========================================================================== */

    function initDeleteAllButtons() {
        // Delete All Messages
        $('#ezzy-delete-all-messages').on('click', function() {
            if (!confirm('Are you sure you want to delete all messages? This action cannot be undone.')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: ezzyWalletFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ezzy_wallet_delete_all_messages',
                    nonce: ezzyWalletFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reset state and show empty
                        messagesState.currentPage = 1;
                        messagesState.totalPages = 1;
                        messagesState.total = 0;

                        $('#ezzy-notifications-list').hide();
                        $('#ezzy-messages-pagination').hide();
                        $('#ezzy-empty-messages').show();
                        $('#ezzy-total-messages').text('0 messages total');
                        
                        // Update badges
                        updateNotificationBadges(0);
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not delete messages.'));
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('is-loading');
                }
            });
        });

        // Delete All Transactions
        $('#ezzy-delete-all-transactions').on('click', function() {
            if (!confirm('Are you sure you want to delete all transaction history? This action cannot be undone.')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: ezzyWalletFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ezzy_wallet_delete_all_transactions',
                    nonce: ezzyWalletFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reset state and show empty
                        transactionsState.currentPage = 1;
                        transactionsState.totalPages = 1;
                        transactionsState.total = 0;

                        $('#ezzy-all-transactions-body').closest('.ezzy-transactions-table-wrapper').hide();
                        $('#ezzy-transactions-pagination').hide();
                        $('#ezzy-empty-transactions').show();
                        $('#ezzy-total-transactions').text('0 transactions total');
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not delete transactions.'));
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('is-loading');
                }
            });
        });
    }

    /* ==========================================================================
       ACCOUNT SETTINGS FUNCTIONALITY
       ========================================================================== */

    $(document).ready(function() {
        initAccountSettings();
        initSupportPanel();
    });

    function initAccountSettings() {
        // 2FA Toggle
        initTwoFactorAuth();
        
        // Password Change
        initPasswordChange();
        
        // Delete Account
        initDeleteAccount();
    }

    /* -------------------------
       Two-Factor Authentication
       ------------------------- */
    function initTwoFactorAuth() {
        var $toggle = $('#ezzy-2fa-toggle');
        var $setupPanel = $('#ezzy-2fa-setup');
        var $enabledPanel = $('#ezzy-2fa-enabled');
        var isEnabled = $toggle.is(':checked');

        $toggle.on('change', function() {
            if ($(this).is(':checked')) {
                // Start 2FA setup
                setup2FA();
            } else {
                // Disable 2FA
                disable2FA();
            }
        });

        // Verify 2FA code
        $('#ezzy-verify-2fa').on('click', function() {
            var code = $('#ezzy-2fa-code').val().trim();
            verify2FA(code);
        });

        // Allow Enter key in code input
        $('#ezzy-2fa-code').on('keypress', function(e) {
            if (e.which === 13) {
                $('#ezzy-verify-2fa').click();
            }
        });

        // Copy secret key
        $('#ezzy-copy-secret').on('click', function() {
            var secret = $('#ezzy-secret-code').text();
            navigator.clipboard.writeText(secret).then(function() {
                alert('Secret key copied to clipboard!');
            });
        });
    }

    function setup2FA() {
        var $setupPanel = $('#ezzy-2fa-setup');
        var $qrContainer = $('#ezzy-qr-container');
        var $secretKey = $('#ezzy-secret-key');
        var $error = $('#ezzy-2fa-error');

        $setupPanel.slideDown(300);
        $error.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_setup_2fa',
                nonce: ezzyWalletFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show QR code
                    $qrContainer.html('<img src="' + response.data.qr_url + '" alt="QR Code" class="ezzy-qr-image">');
                    
                    // Show secret key
                    $('#ezzy-secret-code').text(response.data.secret);
                    $secretKey.show();
                } else {
                    $error.text(response.data.message).show();
                    $('#ezzy-2fa-toggle').prop('checked', false);
                }
            },
            error: function() {
                $error.text('Failed to setup 2FA. Please try again.').show();
                $('#ezzy-2fa-toggle').prop('checked', false);
            }
        });
    }

    function verify2FA(code) {
        var $btn = $('#ezzy-verify-2fa');
        var $error = $('#ezzy-2fa-error');
        var $setupPanel = $('#ezzy-2fa-setup');
        var $enabledPanel = $('#ezzy-2fa-enabled');

        $btn.prop('disabled', true).text('Verifying...');
        $error.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_verify_2fa',
                nonce: ezzyWalletFrontend.nonce,
                code: code
            },
            success: function(response) {
                if (response.success) {
                    $setupPanel.slideUp(300);
                    $enabledPanel.slideDown(300);
                    $('#ezzy-2fa-code').val('');
                } else {
                    $error.text(response.data.message).show();
                }
            },
            error: function() {
                $error.text('Failed to verify code. Please try again.').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Verify & Enable');
            }
        });
    }

    function disable2FA() {
        if (!confirm('Are you sure you want to disable two-factor authentication?')) {
            $('#ezzy-2fa-toggle').prop('checked', true);
            return;
        }

        var $setupPanel = $('#ezzy-2fa-setup');
        var $enabledPanel = $('#ezzy-2fa-enabled');

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_disable_2fa',
                nonce: ezzyWalletFrontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $setupPanel.hide();
                    $enabledPanel.hide();
                } else {
                    alert(response.data.message);
                    $('#ezzy-2fa-toggle').prop('checked', true);
                }
            },
            error: function() {
                alert('Failed to disable 2FA. Please try again.');
                $('#ezzy-2fa-toggle').prop('checked', true);
            }
        });
    }

    /* -------------------------
       Password Change
       ------------------------- */
    function initPasswordChange() {
        var $toggle = $('#ezzy-password-toggle');
        var $form = $('#ezzy-password-form');
        var $arrow = $toggle.find('.ezzy-settings-arrow');

        // Toggle password form
        $toggle.on('click', function() {
            $form.slideToggle(300);
            $arrow.toggleClass('is-open');
        });

        // Cancel button
        $('#ezzy-cancel-password').on('click', function() {
            $form.slideUp(300);
            $arrow.removeClass('is-open');
            clearPasswordForm();
        });

        // Password visibility toggles
        $('.ezzy-password-toggle-btn').on('click', function() {
            var targetId = $(this).data('target');
            var $input = $('#' + targetId);
            var $eyeOpen = $(this).find('.eye-open');
            var $eyeClosed = $(this).find('.eye-closed');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $eyeOpen.hide();
                $eyeClosed.show();
            } else {
                $input.attr('type', 'password');
                $eyeOpen.show();
                $eyeClosed.hide();
            }
        });

        // Password validation on input
        $('#ezzy-new-password').on('input', function() {
            validatePassword($(this).val());
            checkPasswordMatch();
        });

        $('#ezzy-confirm-password').on('input', function() {
            checkPasswordMatch();
        });

        // Save password
        $('#ezzy-save-password').on('click', function() {
            savePassword();
        });
    }

    function validatePassword(password) {
        var requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };

        // Update requirement indicators
        for (var req in requirements) {
            var $li = $('#req-' + req);
            if (requirements[req]) {
                $li.removeClass('ezzy-req-invalid').addClass('ezzy-req-valid');
                $li.find('.ezzy-req-icon').html('<polyline points="20 6 9 17 4 12"/>');
            } else {
                $li.removeClass('ezzy-req-valid').addClass('ezzy-req-invalid');
                $li.find('.ezzy-req-icon').html('<circle cx="12" cy="12" r="10"/>');
            }
        }

        return requirements.length && requirements.uppercase && requirements.lowercase && requirements.number && requirements.special;
    }

    function checkPasswordMatch() {
        var newPass = $('#ezzy-new-password').val();
        var confirmPass = $('#ezzy-confirm-password').val();
        var $match = $('#ezzy-password-match');
        var $saveBtn = $('#ezzy-save-password');

        if (confirmPass.length === 0) {
            $match.hide();
            $saveBtn.prop('disabled', true);
            return false;
        }

        if (newPass === confirmPass) {
            $match.text('Passwords match').removeClass('is-error').addClass('is-success').show();
            
            // Enable save button if password is valid
            if (validatePassword(newPass) && $('#ezzy-current-password').val().length > 0) {
                $saveBtn.prop('disabled', false);
            }
            return true;
        } else {
            $match.text('Passwords do not match').removeClass('is-success').addClass('is-error').show();
            $saveBtn.prop('disabled', true);
            return false;
        }
    }

    function savePassword() {
        var $btn = $('#ezzy-save-password');
        var $message = $('#ezzy-password-message');
        
        var currentPassword = $('#ezzy-current-password').val();
        var newPassword = $('#ezzy-new-password').val();
        var confirmPassword = $('#ezzy-confirm-password').val();

        if (!currentPassword) {
            $message.text('Please enter your current password.').removeClass('is-success').addClass('is-error').show();
            return;
        }

        $btn.prop('disabled', true).text('Updating...');
        $message.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_change_password',
                nonce: ezzyWalletFrontend.nonce,
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            },
            success: function(response) {
                if (response.success) {
                    $message.text(response.data.message).removeClass('is-error').addClass('is-success').show();
                    clearPasswordForm();
                    
                    // Close form after success
                    setTimeout(function() {
                        $('#ezzy-password-form').slideUp(300);
                        $('#ezzy-password-toggle .ezzy-settings-arrow').removeClass('is-open');
                        $message.hide();
                    }, 2000);
                } else {
                    $message.text(response.data.message).removeClass('is-success').addClass('is-error').show();
                }
            },
            error: function() {
                $message.text('Failed to update password. Please try again.').removeClass('is-success').addClass('is-error').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Update Password');
            }
        });
    }

    function clearPasswordForm() {
        $('#ezzy-current-password').val('');
        $('#ezzy-new-password').val('');
        $('#ezzy-confirm-password').val('');
        $('#ezzy-password-match').hide();
        $('#ezzy-password-message').hide();
        $('#ezzy-save-password').prop('disabled', true);
        
        // Reset requirement indicators
        $('.ezzy-requirements-list li').removeClass('ezzy-req-valid').addClass('ezzy-req-invalid');
        $('.ezzy-req-icon').html('<circle cx="12" cy="12" r="10"/>');
    }

    /* -------------------------
       Delete Account
       ------------------------- */
    function initDeleteAccount() {
        var $modal = $('#ezzy-delete-modal');
        var $confirmInput = $('#ezzy-delete-confirm-input');
        var $confirmBtn = $('#ezzy-confirm-delete');

        // Show modal
        $('#ezzy-delete-account-btn').on('click', function() {
            $modal.fadeIn(200);
            $confirmInput.val('').focus();
            $confirmBtn.prop('disabled', true);
        });

        // Cancel/close modal
        $('#ezzy-cancel-delete').on('click', function() {
            $modal.fadeOut(200);
        });

        // Close on overlay click
        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.fadeOut(200);
            }
        });

        // Enable confirm button when DELETE is typed
        $confirmInput.on('input', function() {
            var value = $(this).val().toUpperCase();
            $confirmBtn.prop('disabled', value !== 'DELETE');
        });

        // Confirm delete
        $confirmBtn.on('click', function() {
            deleteAccount();
        });
    }

    function deleteAccount() {
        var $btn = $('#ezzy-confirm-delete');
        var confirmText = $('#ezzy-delete-confirm-input').val();

        $btn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_delete_account',
                nonce: ezzyWalletFrontend.nonce,
                confirm: confirmText
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect || '/';
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).text('Delete My Account');
                }
            },
            error: function() {
                alert('Failed to delete account. Please try again.');
                $btn.prop('disabled', false).text('Delete My Account');
            }
        });
    }

    /* ==========================================================================
       SUPPORT PANEL FUNCTIONALITY
       ========================================================================== */

    function initSupportPanel() {
        // FAQ accordion
        $('.ezzy-faq-question').on('click', function() {
            var $item = $(this).closest('.ezzy-faq-item');
            var $answer = $item.find('.ezzy-faq-answer');
            var $arrow = $(this).find('svg');

            // Close other FAQs
            $('.ezzy-faq-item').not($item).find('.ezzy-faq-answer').slideUp(200);
            $('.ezzy-faq-item').not($item).removeClass('is-open');

            // Toggle current
            $item.toggleClass('is-open');
            $answer.slideToggle(200);
        });

        // Live chat button (placeholder)
        $('#ezzy-start-chat').on('click', function() {
            alert('Live chat feature coming soon! Please use email support for now.');
        });

        // Email Support Modal
        initEmailSupportModal();
    }

    function initEmailSupportModal() {
        var $modal = $('#ezzy-email-support-modal');
        var $form = $('#ezzy-support-form');
        var $sendBtn = $('#ezzy-send-support');
        var $result = $('#ezzy-support-result');
        var $messageInput = $('#ezzy-support-message');
        var $charCount = $('#ezzy-message-chars');

        // Open modal
        $('#ezzy-email-support-btn').on('click', function() {
            $modal.fadeIn(200);
            $result.hide();
            $messageInput.val('');
            $charCount.text('0');
            $('#ezzy-support-subject').val('');
        });

        // Close modal
        $('#ezzy-close-support-modal, #ezzy-cancel-support').on('click', function() {
            $modal.fadeOut(200);
        });

        // Close on overlay click
        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.fadeOut(200);
            }
        });

        // Character count for message
        $messageInput.on('input', function() {
            var len = $(this).val().length;
            $charCount.text(len);
            
            if (len > 1000) {
                $charCount.addClass('is-over');
            } else {
                $charCount.removeClass('is-over');
            }
        });

        // Send support email
        $sendBtn.on('click', function() {
            sendSupportEmail();
        });
    }

    function sendSupportEmail() {
        var $sendBtn = $('#ezzy-send-support');
        var $result = $('#ezzy-support-result');
        var $modal = $('#ezzy-email-support-modal');

        var username = $('#ezzy-support-username').val();
        var email = $('#ezzy-support-email').val();
        var phone = $('#ezzy-support-phone').val();
        var subject = $('#ezzy-support-subject').val();
        var message = $('#ezzy-support-message').val().trim();

        // Validate
        if (!message) {
            $result.html('<div class="ezzy-result-error">Please enter a message.</div>').show();
            return;
        }

        if (message.length > 1000) {
            $result.html('<div class="ezzy-result-error">Message is too long. Maximum 1000 characters.</div>').show();
            return;
        }

        // Disable button and show loading
        $sendBtn.prop('disabled', true).html(
            '<svg class="ezzy-spinner-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="32"/>' +
            '</svg> Sending...'
        );
        $result.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_send_support_email',
                nonce: ezzyWalletFrontend.nonce,
                username: username,
                email: email,
                phone: phone,
                subject: subject,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    var ticketHtml = '';
                    if (response.data.ticket_number) {
                        ticketHtml = '<div class="ezzy-ticket-info">' +
                            '<span class="ezzy-ticket-label">Your Ticket Number:</span>' +
                            '<span class="ezzy-ticket-number">' + response.data.ticket_number + '</span>' +
                            '<p class="ezzy-ticket-note">Please check your email for our reply. Use this ticket number for any follow-up inquiries.</p>' +
                        '</div>';
                    }
                    
                    $result.html('<div class="ezzy-result-success">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                        '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>' +
                        '<polyline points="22 4 12 14.01 9 11.01"/>' +
                        '</svg>' +
                        '<span>' + response.data.message + '</span>' +
                    '</div>' + ticketHtml).show();
                    
                    // Clear form
                    $('#ezzy-support-message').val('');
                    $('#ezzy-support-subject').val('');
                    $('#ezzy-support-phone').val($('#ezzy-support-phone').attr('value') || '');
                    $('#ezzy-message-chars').text('0');
                    
                    // Close modal after delay (longer to allow reading ticket number)
                    setTimeout(function() {
                        $modal.fadeOut(200);
                    }, 5000);
                } else {
                    $result.html('<div class="ezzy-result-error">' + response.data.message + '</div>').show();
                }
            },
            error: function() {
                $result.html('<div class="ezzy-result-error">Failed to send message. Please try again.</div>').show();
            },
            complete: function() {
                $sendBtn.prop('disabled', false).html(
                    '<svg class="ezzy-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<line x1="22" y1="2" x2="11" y2="13"/>' +
                    '<polygon points="22 2 15 22 11 13 2 9 22 2"/>' +
                    '</svg> Send Message'
                );
            }
        });
    }

    /* ==========================================================================
       WALLET ACTIONS - DEPOSIT, WITHDRAW, TRANSFER
       ========================================================================== */

    $(document).ready(function() {
        initWalletActions();
    });

    function initWalletActions() {
        // Deposit Modal
        initDepositModal();
        
        // Withdraw Modal
        initWithdrawModal();
        
        // Transfer Modal
        initTransferModal();
        
        // Generic modal close handlers
        initModalCloseHandlers();
    }

    function initModalCloseHandlers() {
        // Close on X button or Cancel button
        $(document).on('click', '[data-close-modal]', function() {
            $(this).closest('.ezzy-modal-overlay').fadeOut(200);
        });

        // Close on overlay click
        $('.ezzy-wallet-modal').on('click', function(e) {
            if ($(e.target).is(this)) {
                $(this).fadeOut(200);
            }
        });

        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.ezzy-wallet-modal:visible').fadeOut(200);
            }
        });
    }

    /* -------------------------
       Deposit Modal
       ------------------------- */
    function initDepositModal() {
        var $modal = $('#ezzy-deposit-modal');
        var $form = $('#ezzy-deposit-form');
        var $result = $('#ezzy-deposit-result');
        var $submitBtn = $('#ezzy-submit-deposit');

        // Open modal
        $('#ezzy-deposit-btn').on('click', function() {
            resetDepositForm();
            $modal.fadeIn(200);
        });

        // Submit deposit
        $submitBtn.on('click', function() {
            submitDeposit();
        });
    }

    function resetDepositForm() {
        $('#ezzy-deposit-amount').val('');
        $('input[name="deposit_method"]').prop('checked', false);
        $('#ezzy-deposit-note').val('');
        $('#ezzy-deposit-result').hide().html('');
        $('#ezzy-submit-deposit').prop('disabled', false);
    }

    function submitDeposit() {
        var $btn = $('#ezzy-submit-deposit');
        var $result = $('#ezzy-deposit-result');
        var $modal = $('#ezzy-deposit-modal');

        var amount = $('#ezzy-deposit-amount').val();
        var method = $('input[name="deposit_method"]:checked').val();
        var note = $('#ezzy-deposit-note').val();

        // Validate
        if (!amount || parseFloat(amount) <= 0) {
            showResult($result, 'error', 'Please enter a valid amount.');
            return;
        }

        if (!method) {
            showResult($result, 'error', 'Please select a deposit method.');
            return;
        }

        // Submit
        $btn.prop('disabled', true).html('<span class="ezzy-spinner-sm"></span> Submitting...');
        $result.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_deposit_request',
                nonce: ezzyWalletFrontend.nonce,
                amount: amount,
                method: method,
                note: note
            },
            success: function(response) {
                if (response.success) {
                    showResult($result, 'success', response.data.message);
                    resetDepositForm();
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $modal.fadeOut(200);
                        // Refresh page to show new transaction
                        location.reload();
                    }, 2000);
                } else {
                    showResult($result, 'error', response.data.message);
                }
            },
            error: function() {
                showResult($result, 'error', 'An error occurred. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg> Submit Request'
                );
            }
        });
    }

    /* -------------------------
       Withdraw Modal
       ------------------------- */
    function initWithdrawModal() {
        var $modal = $('#ezzy-withdraw-modal');
        var $form = $('#ezzy-withdraw-form');
        var $result = $('#ezzy-withdraw-result');
        var $submitBtn = $('#ezzy-submit-withdraw');
        var $destGroup = $('#ezzy-withdraw-destination-group');
        var $destLabel = $('#ezzy-withdraw-destination-label');
        var $destInput = $('#ezzy-withdraw-destination');

        // Open modal
        $('#ezzy-withdraw-btn').on('click', function() {
            if ($(this).hasClass('is-disabled') || $(this).is(':disabled')) {
                return;
            }
            resetWithdrawForm();
            $modal.fadeIn(200);
        });

        // Method change - show destination field
        $('input[name="withdraw_method"]').on('change', function() {
            var method = $(this).val();
            var labels = {
                'bitcoin': 'Bitcoin Wallet Address',
                'paypal': 'PayPal Email',
                'cashapp': 'CashTag (e.g., $username)',
                'bank_transfer': 'Bank Account Details'
            };
            var placeholders = {
                'bitcoin': 'Enter your Bitcoin wallet address',
                'paypal': 'Enter your PayPal email',
                'cashapp': 'Enter your CashTag',
                'bank_transfer': 'Enter bank name, account number, routing number'
            };

            $destLabel.html(labels[method] + ' <span class="ezzy-required">*</span>');
            $destInput.attr('placeholder', placeholders[method]);
            $destGroup.slideDown(200);
        });

        // Submit withdrawal
        $submitBtn.on('click', function() {
            submitWithdraw();
        });
    }

    function resetWithdrawForm() {
        $('#ezzy-withdraw-amount').val('');
        $('input[name="withdraw_method"]').prop('checked', false);
        $('#ezzy-withdraw-destination').val('');
        $('#ezzy-withdraw-destination-group').hide();
        $('#ezzy-withdraw-note').val('');
        $('#ezzy-withdraw-result').hide().html('');
        $('#ezzy-submit-withdraw').prop('disabled', false);
    }

    function submitWithdraw() {
        var $btn = $('#ezzy-submit-withdraw');
        var $result = $('#ezzy-withdraw-result');
        var $modal = $('#ezzy-withdraw-modal');

        var amount = $('#ezzy-withdraw-amount').val();
        var method = $('input[name="withdraw_method"]:checked').val();
        var destination = $('#ezzy-withdraw-destination').val();
        var note = $('#ezzy-withdraw-note').val();

        // Validate
        if (!amount || parseFloat(amount) <= 0) {
            showResult($result, 'error', 'Please enter a valid amount.');
            return;
        }

        if (!method) {
            showResult($result, 'error', 'Please select a withdrawal method.');
            return;
        }

        if (!destination) {
            showResult($result, 'error', 'Please enter your withdrawal destination.');
            return;
        }

        // Submit
        $btn.prop('disabled', true).html('<span class="ezzy-spinner-sm"></span> Submitting...');
        $result.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_withdraw_request',
                nonce: ezzyWalletFrontend.nonce,
                amount: amount,
                method: method,
                destination: destination,
                note: note
            },
            success: function(response) {
                if (response.success) {
                    showResult($result, 'success', response.data.message);
                    resetWithdrawForm();
                    
                    setTimeout(function() {
                        $modal.fadeOut(200);
                        location.reload();
                    }, 2000);
                } else {
                    showResult($result, 'error', response.data.message);
                }
            },
            error: function() {
                showResult($result, 'error', 'An error occurred. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5m-7 7l7-7 7 7"/></svg> Submit Request'
                );
            }
        });
    }

    /* -------------------------
       Transfer Modal
       ------------------------- */
    function initTransferModal() {
        var $modal = $('#ezzy-transfer-modal');
        var $form = $('#ezzy-transfer-form');
        var $result = $('#ezzy-transfer-result');
        var $submitBtn = $('#ezzy-submit-transfer');

        // Open modal
        $('#ezzy-transfer-btn').on('click', function() {
            resetTransferForm();
            $modal.fadeIn(200);
        });

        // Submit transfer
        $submitBtn.on('click', function() {
            submitTransfer();
        });
    }

    function resetTransferForm() {
        $('#ezzy-transfer-recipient').val('');
        $('#ezzy-transfer-amount').val('');
        $('#ezzy-transfer-message').val('');
        $('#ezzy-transfer-result').hide().html('');
        $('#ezzy-submit-transfer').prop('disabled', false);
    }

    function submitTransfer() {
        var $btn = $('#ezzy-submit-transfer');
        var $result = $('#ezzy-transfer-result');
        var $modal = $('#ezzy-transfer-modal');

        var recipient = $('#ezzy-transfer-recipient').val().trim();
        var amount = $('#ezzy-transfer-amount').val();
        var message = $('#ezzy-transfer-message').val();

        // Validate
        if (!recipient) {
            showResult($result, 'error', 'Please enter a recipient username or email.');
            return;
        }

        if (!amount || parseFloat(amount) <= 0) {
            showResult($result, 'error', 'Please enter a valid amount.');
            return;
        }

        // Submit
        $btn.prop('disabled', true).html('<span class="ezzy-spinner-sm"></span> Sending...');
        $result.hide();

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ezzy_wallet_transfer',
                nonce: ezzyWalletFrontend.nonce,
                recipient: recipient,
                amount: amount,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    showResult($result, 'success', response.data.message);
                    resetTransferForm();
                    
                    // Update balance display
                    if (response.data.balance_formatted) {
                        $('#ezzy-balance-display').text(response.data.balance_formatted)
                            .attr('data-balance', response.data.balance_formatted);
                        $('#ezzy-transfer-available').text(response.data.balance_formatted);
                        $('#ezzy-withdraw-available').text(response.data.balance_formatted);
                    }
                    
                    setTimeout(function() {
                        $modal.fadeOut(200);
                        location.reload();
                    }, 2000);
                } else {
                    showResult($result, 'error', response.data.message);
                }
            },
            error: function() {
                showResult($result, 'error', 'An error occurred. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg> Send Transfer'
                );
            }
        });
    }

    /* -------------------------
       Helper Functions
       ------------------------- */
    function showResult($container, type, message) {
        var iconHtml = type === 'success' 
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
        
        $container.html(
            '<div class="ezzy-result-' + type + '">' + iconHtml + '<span>' + message + '</span></div>'
        ).show();
    }

    /**
     * Initialize Profile Image Upload
     */
    function initProfileImageUpload() {
        // Header avatar click
        $('#ezzy-header-avatar').on('click', function(e) {
            if (!$(e.target).is('input')) {
                $('#ezzy-header-avatar-input').click();
            }
        });

        // Profile panel avatar click
        $('#ezzy-profile-avatar').on('click', function(e) {
            if (!$(e.target).is('input') && !$(e.target).closest('.ezzy-avatar-edit').length) {
                $('#ezzy-profile-avatar-input').click();
            }
        });

        // Profile panel edit button click
        $('#ezzy-profile-avatar .ezzy-avatar-edit').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#ezzy-profile-avatar-input').click();
        });

        // Handle file selection - header
        $('#ezzy-header-avatar-input').on('change', function() {
            handleProfileImageUpload(this);
        });

        // Handle file selection - profile panel
        $('#ezzy-profile-avatar-input').on('change', function() {
            handleProfileImageUpload(this);
        });
    }

    /**
     * Handle profile image upload
     */
    function handleProfileImageUpload(input) {
        if (!input.files || !input.files[0]) {
            return;
        }

        var file = input.files[0];

        // Validate file type
        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            showProfileImageError('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.');
            return;
        }

        // Validate file size (max 2MB)
        var maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            showProfileImageError('Image file is too large. Maximum size is 2MB.');
            return;
        }

        // Show loading state
        showProfileImageLoading();

        // Create FormData and upload
        var formData = new FormData();
        formData.append('action', 'ezzy_wallet_upload_profile_image');
        formData.append('nonce', ezzyWalletFrontend.nonce);
        formData.append('profile_image', file);

        $.ajax({
            url: ezzyWalletFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideProfileImageLoading();
                
                if (response.success) {
                    // Update both avatars with new image
                    updateAllAvatars(response.data.image_url);
                    showProfileImageSuccess(response.data.message);
                } else {
                    showProfileImageError(response.data.message || 'Upload failed. Please try again.');
                }
            },
            error: function() {
                hideProfileImageLoading();
                showProfileImageError('Upload failed. Please check your connection and try again.');
            }
        });

        // Clear input
        $(input).val('');
    }

    /**
     * Update all avatar images on the page
     */
    function updateAllAvatars(imageUrl) {
        // Add cache buster
        var cacheBuster = '?t=' + new Date().getTime();
        var newUrl = imageUrl + cacheBuster;

        // Update header avatar
        $('#ezzy-header-avatar-img').attr('src', newUrl);

        // Update profile panel avatar
        $('#ezzy-profile-avatar-img').attr('src', newUrl);
    }

    /**
     * Show loading state on avatars
     */
    function showProfileImageLoading() {
        $('.ezzy-avatar-upload').addClass('ezzy-avatar-loading');
    }

    /**
     * Hide loading state on avatars
     */
    function hideProfileImageLoading() {
        $('.ezzy-avatar-upload').removeClass('ezzy-avatar-loading');
    }

    /**
     * Show profile image upload error
     */
    function showProfileImageError(message) {
        // Show toast notification
        showToast(message, 'error');
    }

    /**
     * Show profile image upload success
     */
    function showProfileImageSuccess(message) {
        // Show toast notification
        showToast(message, 'success');
    }

    /**
     * Show toast notification
     */
    function showToast(message, type) {
        // Remove existing toasts
        $('.ezzy-toast').remove();

        var iconHtml = type === 'success'
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

        var $toast = $(
            '<div class="ezzy-toast ezzy-toast-' + type + '">' +
            iconHtml +
            '<span>' + escapeHtml(message) + '</span>' +
            '</div>'
        );

        $('body').append($toast);

        // Animate in
        setTimeout(function() {
            $toast.addClass('ezzy-toast-visible');
        }, 10);

        // Auto dismiss after 4 seconds
        setTimeout(function() {
            $toast.removeClass('ezzy-toast-visible');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 4000);
    }

    // Initialize profile image upload on document ready
    $(document).ready(function() {
        initProfileImageUpload();
    });

})(jQuery);
