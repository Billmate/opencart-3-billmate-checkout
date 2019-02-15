(function ( $ ) {
    $.fn.bmchekout = {
        init: function(options) {
            var settings = $.extend({
                shippingOptionSelector: '.radio input[name="shipping_method"]',
            }, options );
            bmcthis = this;
            bmcthis.config = settings;
            bmcthis.observeSwitchShipping();
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
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(respData) {
                    console.log(respData);
                    $('#billmate-checkout').attr('src',respData.url)
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }

    };
}( jQuery ));