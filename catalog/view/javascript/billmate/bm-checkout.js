(function ( $ ) {
    $.fn.bmchekout = {
        init: function(options) {
            var settings = $.extend({
                shippingOptionSelector: '.radio input[name="shipping_method"]',
                loaderSelector: '.bm-loader-container',
                iframeSelector: 'iframe#billmate-checkout',
                delayHideLoader: 1000,
            }, options );
            bmcthis = this;
            bmcthis.config = settings;
            bmcthis.observeSwitchShipping();
            bmcthis.listenBmIframeEvents();
            return bmcthis;
        },
        observeSwitchShipping: function() {
            $(bmcthis.config.shippingOptionSelector).on('change', function() {
                bmcthis.updateShipping();
            });
        },
        updateShipping: function () {
            data = $( bmcthis.config.shippingOptionSelector + ':checked, #collapse-shipping-method textarea');
            bmcthis.sendRequest(bmcthis.config.saveShippingUrl, data)
        },
        sendRequest: function (url, data) {
            bmcthis.showLoader();
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(respData) {
                    if(respData.url) {
                        $('#billmate-checkout').attr('src',respData.url)
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