(function ( $ ) {
    $.fn.bmchekout = {
        init: function(options) {
            var settings = $.extend({
                cartBlockSelector: '#bm-cart-block',
                couponBlockSelector: '#bm-coupon-block',
                shippingBlockSelector: '#bm-shipping-block',
                cartFormSelector: '#bm-checkout-cart-form',
                removeButtonSelector: '.cart-item-remove',
                shippingOptionSelector: '.radio input[name="shipping_method"]',
                couponButtonSelector: '#button-coupon',
                couponFieldSelector: '#input-coupon',
                couponMessageSelector: '#coupon-message',
                commentFieldSelector: '#collapse-shipping-method textarea',
                loaderSelector: '.bm-loader-container',
                iframeSelector: 'iframe#billmate-checkout',
                delayHideLoader: 1000,
            }, options );
            bmcthis = this;
            bmcthis.config = settings;
            bmcthis.observeChangeShippingInfo();
            bmcthis.listenCartChanges();
            bmcthis.listenCouponButton();
            bmcthis.listenBmIframeEvents();
            return bmcthis;
        },
        observeChangeShippingInfo: function() {
            $(bmcthis.config.shippingOptionSelector + ',' +
                bmcthis.config.commentFieldSelector).on('change', function() {
                bmcthis.updateShipping();
            });
        },
        updateShipping: function () {
            data = $( bmcthis.config.shippingOptionSelector + ':checked,' + bmcthis.config.commentFieldSelector);
            bmcthis.sendRequest(bmcthis.config.saveShippingUrl, data, bmcthis.updateIframe);
        },
        updateIframe: function (respData) {
            if(respData.iframe_url) {
                $(bmcthis.config.iframeSelector).attr('src',respData.iframe_url)
            }
        },
        sendRequest: function (url, data, callbackEvent) {
            bmcthis.showLoader();
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(respData) {
                    if (respData.redirect) {
                        window.location.href = respData.redirect;
                        return;
                    }

                    if (typeof(callbackEvent) == 'function') {
                        callbackEvent(respData);
                    }

                    bmcthis.hideLoader(bmcthis.config.delayHideLoader);
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    bmcthis.hideLoader();
                }
            });
        },
        listenBmIframeEvents: function () {
            window.addEventListener("message",bmcthis.handleEvent);
        },
        handleEvent : function(event){
            try {
                var json = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            self.childWindow = json.source;
            switch (json.event) {
                case 'content_height':
                    bmcthis.changeIframeSize(json.data);
                    break;
                default:
                    break;
            }
        },
        changeIframeSize: function (eventHeight) {
            $(bmcthis.config.iframeSelector).css('height', eventHeight);
        },
        listenCartChanges: function(){
            bmcthis.itemUpdate();
            bmcthis.itemRemove();
        },
        listenCouponButton: function () {
            $(bmcthis.config.couponBlockSelector).on(
                'click',
                bmcthis.config.couponButtonSelector,
                bmcthis.applyCouponCode
            );
            $(bmcthis.config.couponFieldSelector).on('keypress',
                function(e) {
                    if (e.which == 13) {
                        bmcthis.applyCouponCode();
                    }
                }
            );
        },
        applyCouponCode: function() {
            var couponCode = $(bmcthis.config.couponFieldSelector).val();
            var requestData = {
                coupon_code: couponCode
            };
            $(bmcthis.config.couponButtonSelector).button('loading');
            bmcthis.sendRequest(
                bmcthis.config.addCouponUrl,
                requestData,
                bmcthis.afterApplyCopuponCode
            );
        },
        afterApplyCopuponCode: function (respData) {
            $(bmcthis.config.couponButtonSelector).button('reset');
            if (respData.error) {
                $(bmcthis.config.couponMessageSelector)
                    .text(respData.error).show();
            }
            if (respData.success) {
                $(bmcthis.config.couponMessageSelector)
                    .text(respData.success).show();
            }
            bmcthis.updateCartBlock(respData);
        },
        itemUpdate: function() {
            $(bmcthis.config.cartBlockSelector).on(
                'submit',
                bmcthis.config.cartFormSelector,
                bmcthis.updateCartInfo
            );
        },
        itemRemove: function () {
            $(bmcthis.config.cartBlockSelector).on(
                'click',
                bmcthis.config.removeButtonSelector,
                bmcthis.removeCartItem
            );
        },
        updateCartInfo: function (e) {
            var formData = $(bmcthis.config.cartFormSelector).serialize();
            bmcthis.sendRequest(
                bmcthis.config.updateCartUrl,
                formData,
                bmcthis.updateCartBlock
            );
            return false;
        },
        removeCartItem: function (e) {
            var cartItemId = $(this).data('cart-item-id');
            var requestData = {
                cart_item_id: cartItemId
            };
            bmcthis.sendRequest(
                bmcthis.config.removeCartItemUrl,
                requestData,
                bmcthis.updateCartBlock
            );
            return false;
        },
        updateCartBlock: function (responseData) {
            if (responseData.cart_block) {
                $(bmcthis.config.cartBlockSelector).html(
                    responseData.cart_block
                );
            }
            bmcthis.updateIframe(responseData);
        },
        showLoader: function () {
            $(bmcthis.config.loaderSelector).show();
        },
        hideLoader: function (delay) {
            if(!delay) {
                delay = 0;
            }
            setTimeout(function(){
                $(bmcthis.config.loaderSelector).hide();
            },delay);
        }
    };
}( jQuery ));