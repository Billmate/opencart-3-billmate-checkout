<?php

namespace Billmate;

use Billmate\Checkout;
use Billmate\Client;
use Billmate\Payment;

class Billmate
{
    public function __construct($id, $key, $test = false)
    {
        $this->client = new Client($id, $key, $test);
    }

    public function initCheckout(Checkout $checkout)
    {
        return $this->client->initCheckout($checkout->build());
    }

    public function getAccessRequest()
    {
        // @todo Return response object

        return $this->getPayload();
    }

    public function getCallbackRequest()
    {
        // @todo Return response object

        return $this->getPayload();
    }

    public function getPaymentInfo(Payment $payment)
    {
        return $this->client->getPaymentinfo($payment->build());
    }

    public function updatePayment(Payment $payment)
    {
        return $this->client->updatePayment($payment->build());
    }

    public function cancelPayment(Payment $payment)
    {
        return $this->client->cancelPayment($payment->build());
    }

    private function getPayload()
    {
        if (!empty($_REQUEST['data']) && !empty($_REQUEST['credentials'])) {
            return [
                'data'        => json_decode($_REQUEST['data'], true),
                'credentials' => json_decode($_REQUEST['credentials'], true),
            ];
        }

        try {
            return json_decode(file_get_contents('php://input'), true);
        } catch (Exception $e) {
            return null;
        }
    }
}
