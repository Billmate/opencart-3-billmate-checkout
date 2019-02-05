<?php
class ControllerBillmatecheckoutAccept extends Controller {
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

        $this->helperBillmate->resetSessionBmHash();
        $this->response->setOutput('accepted');
    }
}