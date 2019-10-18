<?php

class CoreBmController extends Controller
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
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getRequestData()
    {
        if (isset($this->request->request['data']) && isset($this->request->request['credentials'])) {
            $postData['data'] = json_decode($_REQUEST['data'], true);
            $postData['credentials'] = json_decode($_REQUEST['credentials'], true);
            return $postData;
        }

        $jsonBodyRequest = file_get_contents('php://input');
        if ($jsonBodyRequest) {
            return json_decode($jsonBodyRequest, true);
        }
        throw new Exception('The request does not contain information');
    }
}