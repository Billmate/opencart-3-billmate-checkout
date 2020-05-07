<?php
require_once(DIR_APPLICATION . 'model/billmate/service/processor/status.php');

/**
 * Class ModelBillmateServiceProcessorCancel
 */
class ModelBillmateServiceProcessorCancel extends ModelBillmateServiceProcessorStatus
{
    /**
     * @param $orderId
     */
    public function process($orderId)
    {
        $requestData = $this->getRequestData($orderId);
        if (!$requestData) {
            return;
        }

        $billmateConnection = $this->getBMConnection();
        $paymentData = $billmateConnection->getPaymentInfo($requestData);
        $bmRequestData = $this->adaptRequestData($requestData);

        switch ($paymentData['PaymentData']['status']) {
            case self::BILLMATE_CREATED_STATUS:
                $this->cancelPayment($bmRequestData);
            case self::BILLMATE_PAID_STATUS:
                $this->creditPayment($bmRequestData);
        }
    }
}