<?php
require_once(DIR_APPLICATION . 'model/billmate/service/processor/status.php');

class ModelBillmateServiceProcessorActivate extends ModelBillmateServiceProcessorStatus
{
    public function process($orderId)
    {
        $requestData = $this->getRequestData($orderId);
        if (!$requestData) {
            return;
        }
        $bmRequestData = [
            'PaymentData' => $requestData
        ];

        $this->activatePayment($bmRequestData);
    }
}