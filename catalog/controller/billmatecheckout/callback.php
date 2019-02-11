<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/corebm.php');

class ControllerBillmatecheckoutCallback extends ControllerBillmatecheckoutCorebm {
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
            $paymentInfo = $this->helperBillmate
            ->getBillmateConnection()
            ->getPaymentinfo( [
                'number' => $requestData['data']['number']
            ]);

            $this->model_billmate_order->createBmOrder($paymentInfo);

        } catch (\Exception $e) {
            $responseMessage = $e->getMessage();
        }


        $this->response->setOutput($responseMessage);
    }
}