(function ($) {
    $.fn.loadCheckout = {
        init: function(options) {
            checkout = this;

            checkout.listenForIframeEvents();

            return checkout;
        },
        observeChangeShippingInfo: function() {
            //
        },
        updateShipping: function () {
            //
        },
        updateIframe: function (response) {
            //
        },
        sendRequest: function (url, data, callbackEvent) {
            //
        },
        listenForIframeEvents: function () {
            window.addEventListener("message", checkout.handleEvent);
        },
        handleEvent: function(event){
            try {
                var json = JSON.parse(event.data);
            } catch (e) {
                return;
            }

            self.childWindow = json.source;

            switch (json.event) {
                case 'content_height':
                    checkout.changeIframeSize(json.data);
                    break;
                default:
                    break;
            }

        },
        changeIframeSize: function (height) {
            height = (height === 0) ? 870 : height;

            $('iframe#billmate-checkout').css('height', height);
        },
        listenCartChanges: function() {
            //
        },
        listenCouponButton: function () {
            //
        },
        applyCouponCode: function() {
            //
        },
        afterApplyCopuponCode: function (response) {
            //
        },
        itemUpdate: function() {
            //
        },
        itemRemove: function () {
            //
        },
        updateCartInfo: function (e) {
            //
        },
        removeCartItem: function (e) {
            //
        },
        updateCartBlock: function (response) {
            //
        },
        showLoader: function () {
            //
        },
        hideLoader: function (delay) {
            //
        }
    };
}(jQuery));
