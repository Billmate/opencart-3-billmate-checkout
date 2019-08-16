<?php
class ControllerEventBillmatehash extends Controller {

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * ControllerEventBillmatecheckout constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @param $data
     */
    public function validate(&$data)
    {
        if (!$this->helperBillmate->isBmCheckoutEnabled()) {
            return;
        }
        if(!$this->cart->hasProducts()) {
            $this->helperBillmate->resetSessionBmHash();
            $this->cart->clear();
        }
        $this->event->unregister('controller/*/before','event/billmatehash/validate');
    }
}