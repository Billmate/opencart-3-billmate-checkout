<?php

/**
 * Class ModelBillmatePaymentBmsetup
 */
class ModelBillmatePaymentBmsetup extends Model
{
    const BILLMATE_CHECKOUT_EVENT_CODE = 'billmate_checkout_page';

    const BILLMATE_CHECKOUT_JSCSS_EVENT_CODE = 'billmate_checkout_page_jscss';

    const BILLMATE_CHECKOUT_HASH_CODE = 'billmate_checkout_hash_validate';

    const BILLMATE_PUSH_EVENTS_CODE = 'billmate_push_events';

    /**
     * ModelBillmatePaymentBmsetup constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/modification');
        $this->load->model('billmate/payment/modificator');
        $this->load->model('setting/event');
    }

    public function registerEvents()
    {
        $this->model_setting_event->addEvent(
            self::BILLMATE_CHECKOUT_EVENT_CODE,
            'catalog/view/checkout/checkout/after',
            'event/billmatecheckout/replaceTotal'
        );
        $this->model_setting_event->addEvent(
            self::BILLMATE_CHECKOUT_JSCSS_EVENT_CODE,
            'catalog/controller/common/header/before',
            'event/billmateheader/addJsCss'
        );

        $this->model_setting_event->addEvent(
            self::BILLMATE_CHECKOUT_HASH_CODE,
            'catalog/controller/*/before',
            'event/billmatehash/validate'
        );

        $this->model_setting_event->addEvent(
            self::BILLMATE_PUSH_EVENTS_CODE,
            'catalog/model/checkout/order/addOrderHistory/after',
            'event/billmaterequest/process'
        );
    }

    public function unregisterEvents()
    {
        $this->model_setting_event->deleteEventByCode(self::BILLMATE_CHECKOUT_EVENT_CODE);
        $this->model_setting_event->deleteEventByCode(self::BILLMATE_CHECKOUT_JSCSS_EVENT_CODE);
        $this->model_setting_event->deleteEventByCode(self::BILLMATE_CHECKOUT_HASH_CODE);
        $this->model_setting_event->deleteEventByCode(self::BILLMATE_PUSH_EVENTS_CODE);
    }

    public function addModifications()
    {
        $this->getBMPaymentModificator()->addModifications();
    }

    public function deleteModifications()
    {
        $this->getBMPaymentModificator()->deleteModifications();
    }

    /**
     * @return ModelBillmatePaymentModificator
     */
    public function getBMPaymentModificator()
    {
        return $this->model_billmate_payment_modificator;
    }
}