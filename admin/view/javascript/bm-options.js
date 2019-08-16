$.fn.pushEventsSwitcher = {
    init: function() {
        esthis = this;
        esthis.config = {
          push_event_option_selector: '#input-push-events',
          push_event_status_block: '.push-events-options',
          push_event_hide_class: 'hidden',
        };
        esthis.listenOptionSwitch();
    },
    listenOptionSwitch: function () {
        $(esthis.config.push_event_option_selector).on('change',function(){
            esthis.switchShowMode($(this).val());
        });
    },
    switchShowMode: function(switchValue) {
        var optionBlock = $(esthis.config.push_event_status_block);
        if (switchValue == '0') {
            optionBlock.addClass('hidden');
        } else {
            optionBlock.removeClass('hidden');
        }
    }
}