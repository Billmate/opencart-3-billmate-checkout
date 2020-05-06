<?php
require_once(DIR_APPLICATION . 'model/billmate/service/processor/status.php');

/**
 * Class ModelBillmateServiceProcessorActivate
 */
class ModelBillmateServiceProcessorActivate extends ModelBillmateServiceProcessorStatus
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

        $bmRequestData = $this->adaptRequestData($requestData);

        $billmateConnection = $this->getBMConnection();
        $paymentData = $billmateConnection->getPaymentInfo($requestData);

        if ($this->isAllowedToProcess($paymentData['PaymentData']['status'])) {
            $this->activatePayment($bmRequestData);
        }
    }

    /**
     * @param $status
     *
     * @return bool
     */
    protected function isAllowedToProcess($status)
    {
        return $status == self::BILLMATE_CREATED_STATUS;
    }
}