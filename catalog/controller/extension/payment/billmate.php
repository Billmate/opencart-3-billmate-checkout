<?php

class ControllerExtensionPaymentBillmateCheckout extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/billmate_checkout');

        $data['error_message'] = $this->language->get('error_message');

        return $this->load->view('extension/payment/billmate_checkout', $data);
    }
}
