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
        $this->helperBillmate->resetSessionBmHash();
        $this->response->setOutput('tasdfasdf');
    }
}