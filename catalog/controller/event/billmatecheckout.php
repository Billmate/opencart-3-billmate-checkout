<?php

class ControllerEventBillmatecheckout extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function replaceTotal(&$route, &$args, &$output)
    {
        if (!$this->config->get('payment_billmate_checkout_status')) {
            return;
        }

        return $this->response->redirect($this->url->link('checkout/billmate/checkout'));
    }
}
