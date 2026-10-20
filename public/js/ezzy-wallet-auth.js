/**
 * eZZY Wallet Frontend Auth Scripts
 *
 * Handles form animations and password visibility toggles.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * eZZY Wallet Auth Handler
     */
    var EzzyWalletAuth = {

        /**
         * Form wrappers.
         */
        $signupWrapper: null,
        $loginWrapper: null,

        /**
         * Animation duration in milliseconds.
         */
        animationDuration: 400,

        /**
         * Initialize auth functionality.
         */
        init: function() {
            this.$signupWrapper = $('#ezzy-signup-form-wrapper');
            this.$loginWrapper = $('#ezzy-login-form-wrapper');

            this.bindEvents();
            this.initPasswordToggles();
            this.initFormValidation();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            var self = this;

            // Show signup button click.
            $('#ezzy-show-signup').on('click', function(e) {
                e.preventDefault();
                self.showForm('signup');
            });

            // Show login button click.
            $('#ezzy-show-login').on('click', function(e) {
                e.preventDefault();
                self.showForm('login');
            });

            // Form switch links.
            $('.ezzy-form-switch').on('click', function(e) {
                e.preventDefault();
                var show = $(this).data('show');
                self.showForm(show);
            });
        },

        /**
         * Show specified form with animation.
         *
         * @param {string} formType Form type ('signup' or 'login').
         */
        showForm: function(formType) {
            var self = this;

            // Hide both forms first.
            this.$signupWrapper.stop(true, true);
            this.$loginWrapper.stop(true, true);

            if (formType === 'signup') {
                // Hide login, show signup.
                this.$loginWrapper.slideUp(this.animationDuration / 2, function() {
                    self.$signupWrapper.slideDown(self.animationDuration, function() {
                        self.scrollToForm(self.$signupWrapper);
                    });
                });
            } else if (formType === 'login') {
                // Hide signup, show login.
                this.$signupWrapper.slideUp(this.animationDuration / 2, function() {
                    self.$loginWrapper.slideDown(self.animationDuration, function() {
                        self.scrollToForm(self.$loginWrapper);
                    });
                });
            }
        },

        /**
         * Scroll to form smoothly.
         *
         * @param {jQuery} $form Form element.
         */
        scrollToForm: function($form) {
            if ($form.length && $form.is(':visible')) {
                $('html, body').animate({
                    scrollTop: $form.offset().top - 100
                }, 300);

                // Focus first input.
                $form.find('input:visible:first').focus();
            }
        },

        /**
         * Initialize password visibility toggles.
         */
        initPasswordToggles: function() {
            $('.ezzy-password-toggle').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var targetId = $button.data('target');
                var $input = $('#' + targetId);
                var $eyeOpen = $button.find('.ezzy-eye-open');
                var $eyeClosed = $button.find('.ezzy-eye-closed');

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
        },

        /**
         * Initialize form validation.
         */
        initFormValidation: function() {
            var self = this;

            // Signup form validation.
            $('#ezzy-signup-form').on('submit', function(e) {
                var $form = $(this);
                var isValid = true;

                // Clear previous errors.
                $form.find('.ezzy-input-error').removeClass('ezzy-input-error');

                // Validate username.
                var $username = $form.find('#ezzy_username');
                if ($username.val().length < 3) {
                    self.showFieldError($username, 'Username must be at least 3 characters');
                    isValid = false;
                }

                // Validate password strength.
                var $password = $form.find('#ezzy_password');
                var password = $password.val();
                
                if (!self.isStrongPassword(password)) {
                    self.showFieldError($password, 'Password does not meet requirements');
                    isValid = false;
                }

                // Validate password match.
                var $confirm = $form.find('#ezzy_confirm_password');
                if (password !== $confirm.val()) {
                    self.showFieldError($confirm, 'Passwords do not match');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Real-time password strength indicator.
            $('#ezzy_password').on('input', function() {
                var password = $(this).val();
                var $wrapper = $(this).closest('.ezzy-form-group');
                var $hint = $wrapper.find('.ezzy-input-hint');

                if (password.length > 0) {
                    if (self.isStrongPassword(password)) {
                        $hint.addClass('ezzy-hint-success').text('Password strength: Strong');
                    } else {
                        $hint.removeClass('ezzy-hint-success').text('Min 8 chars with uppercase, lowercase, number & special character');
                    }
                } else {
                    $hint.removeClass('ezzy-hint-success').text('Min 8 chars with uppercase, lowercase, number & special character');
                }
            });

            // Real-time password match check.
            $('#ezzy_confirm_password').on('input', function() {
                var $confirm = $(this);
                var $password = $('#ezzy_password');
                var $wrapper = $confirm.closest('.ezzy-input-wrapper');

                if ($confirm.val().length > 0) {
                    if ($confirm.val() === $password.val()) {
                        $wrapper.removeClass('ezzy-input-error').addClass('ezzy-input-success');
                    } else {
                        $wrapper.removeClass('ezzy-input-success').addClass('ezzy-input-error');
                    }
                } else {
                    $wrapper.removeClass('ezzy-input-error ezzy-input-success');
                }
            });
        },

        /**
         * Check if password is strong.
         *
         * @param {string} password Password to check.
         * @return {boolean} True if strong.
         */
        isStrongPassword: function(password) {
            if (password.length < 8) {
                return false;
            }

            // Check for uppercase.
            if (!/[A-Z]/.test(password)) {
                return false;
            }

            // Check for lowercase.
            if (!/[a-z]/.test(password)) {
                return false;
            }

            // Check for number.
            if (!/[0-9]/.test(password)) {
                return false;
            }

            // Check for special character.
            if (!/[^A-Za-z0-9]/.test(password)) {
                return false;
            }

            return true;
        },

        /**
         * Show field error.
         *
         * @param {jQuery} $field Input field.
         * @param {string} message Error message.
         */
        showFieldError: function($field, message) {
            var $wrapper = $field.closest('.ezzy-input-wrapper');
            $wrapper.addClass('ezzy-input-error');

            // Focus field.
            $field.focus();
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function() {
        EzzyWalletAuth.init();
    });

})(jQuery);
