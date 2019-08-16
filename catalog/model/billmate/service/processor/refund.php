<?php
require_once(DIR_APPLICATION . 'model/billmate/service/processor/status.php');

class ModelBillmateServiceProcessorRefund extends ModelBillmateServiceProcessorStatus
{
    public function process($orderId)
    {
        $requestData = $this->getRequestData($orderId);
        if (!$requestData) {
            return;
        }

        $billmateConnection = $this->getBMConnection();
        $paymentData = $billmateConnection->getPaymentInfo($requestData);

        if ($paymentData['PaymentData']['status'] == self::BILLMATE_PAID_STATUS) {
            $bmRequestData = [
                'PaymentData' => $requestData
            ];
            $this->creditPayment($bmRequestData);
        }
    }
}