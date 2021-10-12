<?php

use Billmate\Billmate;
use Billmate\Payment;

class ControllerCheckoutBillmateAccept extends Controller
{
    public function index()
    {
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            return $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        $this->load->language('checkout/billmate/accept');

        $this->load->model('checkout/billmate/order');

        $billmate = new Billmate(
            $this->config->get('payment_billmate_checkout_merchant_id'),
            $this->config->get('payment_billmate_checkout_secret'),
            $this->config->get('payment_billmate_checkout_test_mode')
        );

        if (!$request = $billmate->getAccessRequest()) {
            return $this->failure('error_no_request');
        }

        // Log data for debugging
        $this->writeToCustomLog($request);

        if (empty($request['data']['number'])) {
            return $this->failure('error_no_number');
        }

        if (empty($request['data']['orderid'])) {
            return $this->failure('error_no_order_id');
        }

        if (!$order = $this->model_checkout_billmate_order->getOrder($request['data']['orderid'])) {
            return $this->failure('error_no_order');
        }

        $hash = !empty($order['billmate_checkout'])
            ? base64_encode($order['billmate_checkout'])
            : null;

        return $this->success($hash);
    }

    public function success($hash = null)
    {
        return ($this->config->get('payment_billmate_checkout_success_page'))
            ? $this->response->redirect($this->url->link('checkout/billmate/success', 'checkout=' . $hash, true))
            : $this->response->redirect($this->url->link('checkout/success', 'checkout=' . $hash, true));
    }

    public function failure($error = null)
    {
        if (!empty($error)) {
            $this->log->write($this->language->get($error));
        }

        return $this->response->redirect($this->url->link('checkout/billmate/failure', '', true));
    }

    private function writeToCustomLog($data)
    {
        if (!$this->config->get('payment_billmate_checkout_debug_mode')) {
            return;
        }

        $log = new Log('billmate_accept.log');
        $log->write($data);

        return true;
    }
}
