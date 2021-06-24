<?php

class ModelExtensionPaymentBillmateCheckout extends Model
{
    public function install()
    {
        // Add new events
        $this->model_setting_event->addEvent('billmate_checkout', 'catalog/view/checkout/checkout/before', 'event/billmate/redirect');

        // Delete legacy events
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page_jscss');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_hash_validate');
        $this->model_setting_event->deleteEventByCode('billmate_push_events');
    }

    public function uninstall() {
        // Delete active events
        $this->model_setting_event->deleteEventByCode('billmate_checkout');

        // Delete legacy events
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page_jscss');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_hash_validate');
        $this->model_setting_event->deleteEventByCode('billmate_push_events');
    }
}
