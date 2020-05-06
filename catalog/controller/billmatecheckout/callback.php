<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/CoreBmController.php');

class ControllerBillmatecheckoutCallback extends CoreBmController {
    /**
     * ControllerBillmatecheckoutAccept constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('checkout/order');
        $this->load->model('billmate/order');
    }

    public function index()
    {
        $responseMessage = 'OK';
        try {
            $requestData = $this->getRequestData();
            if ($this->helperBillmate->isAddLog()) {
                $this->helperBillmate->log($requestData);
            }


            $paymentInfo = $this->helperBillmate
                ->getBillmateConnection()
                ->getPaymentinfo( [
                    'number' => $requestData['data']['number']
                ]);

            if (!isset($paymentInfo['PaymentInfo']['real_order_id'])) {
                $this->getBillmateOrderModel()->createBmOrder(
                    $requestData['data']['number'],
                    $paymentInfo
                );
            }
            $this->getBillmateOrderModel()->updateOrderStatus($paymentInfo, $requestData['data']['status']);

        } catch (\Exception $e) {
            $responseMessage = $e->getMessage();
        }


        $this->response->setOutput($responseMessage);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function getBillmateOrderModel()
    {
        return $this->model_billmate_order;
    }
}