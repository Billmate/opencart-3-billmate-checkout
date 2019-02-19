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


        } catch (\Exception $e) {
            $responseMessage = $e->getMessage();
        }


        $this->response->setOutput($responseMessage);
    }
}