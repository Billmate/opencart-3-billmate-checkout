<?php
ini_set('display_errors', true);
class ControllerBillmatecheckoutCallback extends Controller {

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
        $this->helperBillmate  = new Helperbm($registry);
    }

    public function index()
    {

        $this->helperBillmate->log(__CLASS__);
        $this->helperBillmate->log($_REQUEST);

        $testRequest = '{
            "credentials": {
                "hash": "27488bcdb10d0530ccae687c81bd9f69e9a96741e76096d9050b5884902ec2aca17986d5dc0641c3cde756afe215ebd7b31af2729b6d2664379f3c45e785ec3c"
            },
            "data": {
                "number": 550576,
                "status": "Created",
                "orderid": "2-1549018807",
                "url": "https:\/\/api.billmate.se\/invoice\/17338\/2019020195a6912ffd0b437f4fc794e1233be51c"
            }
        }';

        $postData = json_decode($testRequest, true);
        /** @var @ $billmateRequest ModelBillmateCheckoutRequest */
        $this->load->model('billmate/checkout/request');
        $verifyData = $this->helperBillmate->getBillmateConnection()->verify_hash($postData);

        //$this->response->addHeader('Content-Type: application/json');
        $successMessage = 'OK';
        $this->response->setOutput($successMessage);
    }
}