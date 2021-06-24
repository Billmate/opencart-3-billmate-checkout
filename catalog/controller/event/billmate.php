<?php

class ControllerEventBillmate extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function redirect()
    {
        if (!$this->config->get('payment_billmate_checkout_status')) {
            return;
        }

        return $this->response->redirect($this->url->link('checkout/billmate/checkout'));
    }
}
