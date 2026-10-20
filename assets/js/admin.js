/**
 * eZZY Wallet Admin Scripts
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * eZZY Wallet Admin Handler
     */
    var EzzyWalletAdmin = {

        /**
         * Initialize admin functionality.
         */
        init: function() {
            this.initColorPickers();
            this.initThemePreview();
            this.initMaintenanceToggle();
            this.initSkinThemeSelector();
        },

        /**
         * Initialize WordPress color pickers.
         */
        initColorPickers: function() {
            var self = this;

            $('.ezzy-wallet-color-picker').each(function() {
                var $picker = $(this);
                var defaultColor = $picker.data('default-color');

                $picker.wpColorPicker({
                    defaultColor: defaultColor,
                    change: function(event, ui) {
                        // Delay to ensure the value is updated
                        setTimeout(function() {
                            self.updateThemePreview();
                        }, 100);
                    },
                    clear: function() {
                        setTimeout(function() {
                            self.updateThemePreview();
                        }, 100);
                    }
                });
            });
        },

        /**
         * Initialize skin theme selector.
         */
        initSkinThemeSelector: function() {
            var self = this;
            var $select = $('.ezzy-skin-select');
            var $previewCards = $('.ezzy-skin-preview-card');

            if ($select.length === 0) {
                return;
            }

            // Update preview on select change
            $select.on('change', function() {
                var selectedTheme = $(this).val();
                var $selectedOption = $(this).find('option:selected');
                
                // Update preview cards active state
                $previewCards.removeClass('active');
                $previewCards.filter('[data-theme="' + selectedTheme + '"]').addClass('active');

                // Update the live preview
                self.updateSkinPreview($selectedOption);
            });

            // Click on preview card to select theme
            $previewCards.on('click', function() {
                var theme = $(this).data('theme');
                $select.val(theme).trigger('change');
            });

            // Initial preview update
            var $initialOption = $select.find('option:selected');
            if ($initialOption.length) {
                self.updateSkinPreview($initialOption);
            }
        },

        /**
         * Update skin preview based on selected theme.
         */
        updateSkinPreview: function($option) {
            var $previewCard = $('#ezzy-wallet-preview-card');

            if ($previewCard.length === 0 || !$option.length) {
                return;
            }

            var primary = $option.data('primary') || '#2563eb';
            var secondary = $option.data('secondary') || '#1e40af';
            var accent = $option.data('accent') || '#F4B400';
            var background = $option.data('background') || '#F9FAFB';
            var cardBg = $option.data('card') || '#ffffff';
            var formBg = $option.data('form') || '#f8fafc';
            var border = $option.data('border') || '#e2e8f0';
            var text = $option.data('text') || '#111827';
            var hover = $option.data('hover') || '#1d4ed8';

            // Check if dark theme
            var isDark = this.isLightColor(background) === false;

            // Apply colors to preview
            $previewCard.css({
                'border': '2px solid ' + primary
            });

            $previewCard.find('.preview-header').css({
                'background': 'linear-gradient(135deg, ' + primary + ' 0%, ' + secondary + ' 100%)',
                'color': '#fff'
            });

            $previewCard.find('.preview-body').css({
                'background-color': cardBg
            });

            $previewCard.find('.preview-form').css({
                'background-color': formBg,
                'padding': '15px',
                'border-radius': '4px'
            });

            $previewCard.find('.preview-form input').css({
                'border': '1px solid ' + border,
                'background-color': isDark ? formBg : cardBg,
                'color': text
            });

            var $button = $previewCard.find('.preview-button');
            $button.css({
                'background-color': primary,
                'color': '#fff'
            });

            // Add hover effect
            $button.off('mouseenter mouseleave').on({
                mouseenter: function() {
                    $(this).css('background-color', hover);
                },
                mouseleave: function() {
                    $(this).css('background-color', primary);
                }
            });
        },

        /**
         * Check if a color is light or dark.
         */
        isLightColor: function(color) {
            var hex = color.replace('#', '');
            var r = parseInt(hex.substr(0, 2), 16);
            var g = parseInt(hex.substr(2, 2), 16);
            var b = parseInt(hex.substr(4, 2), 16);
            var brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return brightness > 155;
        },

        /**
         * Initialize theme preview functionality.
         */
        initThemePreview: function() {
            // If skin selector exists, it handles preview
            if ($('.ezzy-skin-select').length) {
                return;
            }
            this.updateThemePreview();
        },

        /**
         * Update the theme preview card with current color values.
         */
        updateThemePreview: function() {
            var $previewCard = $('#ezzy-wallet-preview-card');

            if ($previewCard.length === 0) {
                return;
            }

            // Get color values
            var primaryColor = this.getColorValue('primary_color', '#2563eb');
            var secondaryColor = this.getColorValue('secondary_color', '#1e40af');
            var buttonColor = this.getColorValue('button_color', '#3b82f6');
            var cardBgColor = this.getColorValue('card_background_color', '#ffffff');
            var formBgColor = this.getColorValue('form_background_color', '#f8fafc');
            var inputBorderColor = this.getColorValue('input_border_color', '#e2e8f0');
            var hoverColor = this.getColorValue('hover_color', '#1d4ed8');

            // Apply colors to preview
            $previewCard.find('.preview-header').css({
                'background': 'linear-gradient(135deg, ' + primaryColor + ' 0%, ' + secondaryColor + ' 100%)'
            });

            $previewCard.find('.preview-body').css({
                'background-color': cardBgColor
            });

            $previewCard.find('.preview-form').css({
                'background-color': formBgColor,
                'padding': '15px',
                'border-radius': '4px'
            });

            $previewCard.find('.preview-form input').css({
                'border': '1px solid ' + inputBorderColor,
                'background-color': cardBgColor
            });

            var $button = $previewCard.find('.preview-button');
            $button.css({
                'background-color': buttonColor
            });

            // Add hover effect
            $button.off('mouseenter mouseleave').on({
                mouseenter: function() {
                    $(this).css('background-color', hoverColor);
                },
                mouseleave: function() {
                    $(this).css('background-color', buttonColor);
                }
            });
        },

        /**
         * Get color value from input field.
         *
         * @param {string} fieldName Field name.
         * @param {string} defaultValue Default color value.
         * @return {string} Color value.
         */
        getColorValue: function(fieldName, defaultValue) {
            var $input = $('#' + fieldName);
            var value = $input.val();

            if (value && this.isValidHex(value)) {
                return value;
            }

            return defaultValue;
        },

        /**
         * Validate hex color format.
         *
         * @param {string} color Color value to validate.
         * @return {boolean} True if valid hex color.
         */
        isValidHex: function(color) {
            return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
        },

        /**
         * Initialize maintenance mode toggle functionality.
         */
        initMaintenanceToggle: function() {
            var $toggle = $('#maintenance_mode');

            if ($toggle.length === 0) {
                return;
            }

            $toggle.on('change', function() {
                var $label = $(this).closest('.ezzy-wallet-maintenance-toggle').find('.ezzy-wallet-toggle-label');
                
                if ($(this).is(':checked')) {
                    $label.html('<span class="status-active">' + ezzyWalletAdminStrings.maintenanceActive + '</span>');
                } else {
                    $label.html('<span class="status-inactive">' + ezzyWalletAdminStrings.walletOperational + '</span>');
                }
            });
        }
    };

    /**
     * Localized strings fallback.
     */
    var ezzyWalletAdminStrings = window.ezzyWalletAdminStrings || {
        maintenanceActive: 'Maintenance Mode Active',
        walletOperational: 'Wallet Operational'
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function() {
        EzzyWalletAdmin.init();
    });

})(jQuery);
