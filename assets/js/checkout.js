/* global jQuery, CGV */
(function ($) {
    'use strict';

    $(function () {
        var $card = $('.cgv-card');
        if (!$card.length) {
            return;
        }
        var $form = $card.find('form.cgv-form');
        if (!$form.length) {
            return;
        }

        // 1. Tabs control the real (hidden) payment_method radios -------------
        $card.on('click', '.cgv-tab', function (e) {
            e.preventDefault();
            var $btn   = $(this);
            var tabKey = $btn.data('tab');
            var gw     = $btn.data('gateway');

            $card.find('.cgv-tab').removeClass('is-active').attr('aria-selected', 'false');
            $btn.addClass('is-active').attr('aria-selected', 'true');

            var $radio = $card.find('input[name="payment_method"][value="' + gw + '"]');
            if ($radio.length && !$radio.is(':checked')) {
                // Standard WC handler listens on `change`.
                $radio.prop('checked', true).trigger('click').trigger('change');
            }

            // Show only the matching payment_box.
            $card.find('.payment_box').hide();
            $card.find('.payment_method_' + gw + ' .payment_box').show();
        });

        // 2. Sync visible "full name" field to billing_first/last_name --------
        // (Server-side filter handles this on submit, but mirroring keeps
        // wc-checkout.js validation happy if it ever inspects fields.)
        var $fullName = $form.find('input[name="cgv_full_name"]');
        if ($fullName.length) {
            // Inject hidden first/last name inputs WC expects.
            if (!$form.find('input[name="billing_first_name"]').length) {
                $form.append('<input type="hidden" name="billing_first_name" value="" />');
            }
            if (!$form.find('input[name="billing_last_name"]').length) {
                $form.append('<input type="hidden" name="billing_last_name" value="." />');
            }
            var sync = function () {
                var v = ($fullName.val() || '').trim();
                var parts = v.split(/\s+/);
                var first = parts.shift() || '';
                var last  = parts.join(' ') || '.';
                $form.find('input[name="billing_first_name"]').val(first);
                $form.find('input[name="billing_last_name"]').val(last);
            };
            $fullName.on('input change blur', sync);
            sync();
        }

        // 3. Masks for the Brazilian billing fields --------------------------
        var onlyDigits = function (value) {
            return (value || '').replace(/\D/g, '');
        };
        var applyPattern = function (digits, pattern) {
            var out = '';
            var index = 0;
            for (var i = 0; i < pattern.length && index < digits.length; i++) {
                if (pattern.charAt(i) === '0') {
                    out += digits.charAt(index);
                    index++;
                } else {
                    out += pattern.charAt(i);
                }
            }
            return out;
        };
        var masks = {
            billing_cpf: function (value) {
                return applyPattern(onlyDigits(value).slice(0, 11), '000.000.000-00');
            },
            billing_cnpj: function (value) {
                return applyPattern(onlyDigits(value).slice(0, 14), '00.000.000/0000-00');
            },
            billing_birthdate: function (value) {
                return applyPattern(onlyDigits(value).slice(0, 8), '00/00/0000');
            },
            billing_cellphone: function (value) {
                var digits = onlyDigits(value).slice(0, 11);
                return applyPattern(digits, digits.length > 10 ? '(00) 00000-0000' : '(00) 0000-0000');
            }
        };
        $.each(masks, function (name, mask) {
            $form.on('input blur', 'input[name="' + name + '"]', function () {
                var masked = mask(this.value);
                if (this.value !== masked) {
                    this.value = masked;
                }
            });
        });

        // 4. General checkout cart controls ---------------------------------
        var updateCart = function (payload) {
            if (!CGV || !CGV.ajax_url || !CGV.cart_nonce) {
                return;
            }
            $card.addClass('is-updating');
            $card.find('.cgv-cart-feedback').remove();
            $.post(CGV.ajax_url, $.extend({
                action: 'cgv_update_cart',
                nonce: CGV.cart_nonce
            }, payload)).done(function (response) {
                if (response && response.success) {
                    window.location.reload();
                    return;
                }
                var message = response && response.data && response.data.message ? response.data.message : 'Erro ao atualizar o carrinho.';
                $('<div class="cgv-cart-feedback" />').text(message).prependTo($card);
            }).fail(function () {
                $('<div class="cgv-cart-feedback" />').text('Erro ao atualizar o carrinho.').prependTo($card);
            }).always(function () {
                $card.removeClass('is-updating');
            });
        };

        $card.on('click', '[data-cgv-cart-action="increase"], [data-cgv-cart-action="decrease"]', function () {
            var $item = $(this).closest('.cgv-cart-item');
            var $qty = $item.find('.cgv-qty-input');
            var current = parseInt($qty.val(), 10) || 0;
            var next = $(this).data('cgv-cart-action') === 'increase' ? current + 1 : Math.max(0, current - 1);
            $qty.val(next);
            updateCart({
                cart_action: 'set_quantity',
                cart_item_key: $item.data('cart-item-key'),
                quantity: next
            });
        });

        $card.on('change blur', '.cgv-qty-input', function () {
            var $item = $(this).closest('.cgv-cart-item');
            var next = Math.max(0, parseInt(this.value, 10) || 0);
            this.value = next;
            updateCart({
                cart_action: 'set_quantity',
                cart_item_key: $item.data('cart-item-key'),
                quantity: next
            });
        });

        $card.on('click', '[data-cgv-cart-action="remove_item"]', function () {
            var $item = $(this).closest('.cgv-cart-item');
            updateCart({
                cart_action: 'remove_item',
                cart_item_key: $item.data('cart-item-key')
            });
        });

        $card.on('click', '[data-cgv-cart-action="apply_coupon"]', function () {
            var code = $.trim($card.find('#cgv-coupon-code').val() || '');
            if (!code) {
                return;
            }
            updateCart({
                cart_action: 'apply_coupon',
                coupon_code: code
            });
        });

        $card.on('click', '[data-cgv-cart-action="remove_coupon"]', function () {
            updateCart({
                cart_action: 'remove_coupon',
                coupon_code: $(this).data('coupon-code')
            });
        });

        $card.on('keydown', '#cgv-coupon-code', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $card.find('[data-cgv-cart-action="apply_coupon"]').trigger('click');
            }
        });

        // 5. After WC AJAX fragment refresh, restore visual tab selection ----
        $(document.body).on('updated_checkout', function () {
            var $active = $card.find('.cgv-tab.is-active');
            if ($active.length) {
                var gw = $active.data('gateway');
                var $radio = $card.find('input[name="payment_method"][value="' + gw + '"]');
                if ($radio.length && !$radio.is(':checked')) {
                    $radio.prop('checked', true);
                }
                $card.find('.payment_box').hide();
                $card.find('.payment_method_' + gw + ' .payment_box').show();
            }

            // Sync left-side split summary total and discount with the right-side values
            if ($card.hasClass('cgv-layout-split')) {
                var rightSubtotal = $card.find('.cgv-form .cgv-summary-row:first-child span:last-child').html() || $card.find('.cgv-summary-row span:last-child').first().html();
                var rightTotal = $card.find('.cgv-form .cgv-summary-total strong').html();
                
                if (rightSubtotal) {
                    $card.find('.cgv-left-product-price').html(rightSubtotal);
                }
                if (rightTotal) {
                    $card.find('.cgv-left-cart-total').html(rightTotal);
                }

                // Check for discount
                var $rightDiscountRow = $card.find('.cgv-form .cgv-summary-row').filter(function() {
                    var txt = $(this).text() || '';
                    return txt.indexOf('Desconto') !== -1 || txt.indexOf('desconto') !== -1;
                });
                var $leftDiscountRow = $card.find('.cgv-left-discount-row');
                
                if ($rightDiscountRow.length) {
                    var discountVal = $rightDiscountRow.find('span:last-child').html();
                    if ($leftDiscountRow.length) {
                        $leftDiscountRow.find('.cgv-left-discount-value').html(discountVal);
                        $leftDiscountRow.show();
                    } else {
                        var discountHtml = '<div class="cgv-left-summary-row cgv-left-discount-row">' +
                            '<span class="cgv-left-summary-label">Desconto</span>' +
                            '<span class="cgv-left-summary-value cgv-left-discount-value">' + discountVal + '</span>' +
                            '</div>';
                        $card.find('.cgv-left-item-row').after(discountHtml);
                    }
                } else {
                    if ($leftDiscountRow.length) {
                        $leftDiscountRow.hide();
                    }
                }
            }
        });

        // 6. Override final thank-you redirect, if configured -----------------
        if (CGV && CGV.thank_you_url) {
            $(document.body).on('checkout_place_order', function () { return true; });
            // wc-checkout.js follows result.redirect verbatim. We hijack the
            // redirect URL once WC announces success but only if it's the
            // default order-received page (gateway off-site redirects keep working).
            var origAjax = $.ajax;
            $.ajax = function (opts) {
                var origSuccess = opts && opts.success;
                if (opts && typeof opts.url === 'string' && opts.url.indexOf('wc-ajax=checkout') !== -1 && origSuccess) {
                    opts.success = function (result) {
                        try {
                            if (result && result.result === 'success' && result.redirect) {
                                if (result.redirect.indexOf('order-received') !== -1) {
                                    result.redirect = CGV.thank_you_url;
                                }
                            }
                        } catch (err) { /* noop */ }
                        return origSuccess.apply(this, arguments);
                    };
                }
                return origAjax.apply(this, arguments);
            };
        }
    });
})(jQuery);
