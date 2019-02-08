<?php

class ControllerBillmatecheckoutCallback extends Controller
{
    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

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
        $this->helperBillmate  = new Helperbm($registry);
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

    protected function getRequestData()
    {
        if (isset($this->request->request['data'])) {
            $postData['data'] = json_decode($this->request->request['data'], true);
            $postData['credentials'] = json_decode($this->request->request['credentials'], true);
            return $postData;
        }

        $jsonBodyRequest = file_get_contents('php://input');
        if ($jsonBodyRequest) {
            return json_decode($jsonBodyRequest, true);
        }
        throw new Exception('The request does not contain information');
    }
}