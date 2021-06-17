<?php

use Billmate\Billmate;
use Billmate\Payment;

class ControllerCheckoutBillmateCallback extends Controller
{
    public function index()
    {
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        $this->load->language('checkout/billmate/callback');

        $this->load->model('checkout/order');
        $this->load->model('checkout/billmate/helper');
        $this->load->model('checkout/billmate/order');

        $billmate = new Billmate(
            $this->config->get('payment_billmate_checkout_bm_id'),
            $this->config->get('payment_billmate_checkout_secret'),
            $this->config->get('payment_billmate_checkout_test_mode')
        );

        if (!$request = $billmate->getCallbackRequest()) {
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

        if (!$order = $this->model_checkout_order->getOrder($request['data']['orderid'])) {
            return $this->failure('error_no_order');
        }

        $payment = new Payment();
        $payment->addNumber($request['data']['number']);

        if (!$response = $billmate->getPaymentInfo($payment)) {
            return $this->failure('error_no_payment');
        }

        // Log data for debugging
        $this->writeToCustomLog($response);

        try {
            $this->model_checkout_billmate_order->updateOrder($request['data']['orderid'], $response);
        } catch (Exception $e) {
            $this->writeToCustomLog($e->getMessage());

            return $this->failure('error_order_update');
        }

        if (empty($response['PaymentData']['status'])) {
            return $this->failure('error_no_status');
        }

        $order_status = $this->model_checkout_billmate_helper->getOrderStatus($response['PaymentData']['status']);
        $order_message = sprintf('Billmate: %s (%s)', $request['data']['number'], $response['PaymentData']['status']);

        if (!empty($response['PaymentData']['url'])) {
            $order_message = sprintf('%s (<a href="%s" target="_blank">PDF</a>)', $order_message, $response['PaymentData']['url']);
        }

        try {
            if (empty($order['order_status_id'])) {
                $this->model_checkout_order->addOrderHistory($order['order_id'], $order_status, null, true);
            }

            $this->model_checkout_order->addOrderHistory($order['order_id'], $order_status, $order_message, false);
        } catch (Exception $e) {
            $this->writeToCustomLog($e->getMessage());

            return $this->failure('error_order_history');
        }

        http_response_code(200);
        exit;
    }

    private function failure($message)
    {
        if (preg_match('#^error_#', $message) === 1) {
            $message = $this->language->get($message);
        }

        $this->log->write($message);

        http_response_code(500);
        exit($message);
    }

    private function writeToCustomLog($data)
    {
        if (!$this->config->get('payment_billmate_checkout_log_enabled')) {
            return;
        }

        $log = new Log('billmate_callback.log');
        $log->write($data);

        return true;
    }
}
