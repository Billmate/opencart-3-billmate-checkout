<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutAccept extends FrontBmController {

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('checkout/order');
        $this->load->model('billmate/order');
    }

    public function index() {

        if (!$this->helperBillmate->getSessionBmHash()) {
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }

        try{
            $requestData = $this->getRequestData();
            if ($this->helperBillmate->isAddLog()) {
                $this->helperBillmate->log($requestData);
            }
            $paymentInfo = $this->helperBillmate
                ->getBillmateConnection()
                ->getPaymentinfo( [
                    'number' => $requestData['data']['number']
                ]);

            $this->getBillmateOrderModel()
                ->createBmOrder($requestData['data']['number'], $paymentInfo);

        } catch (\Exception $e) {

            $responseMessage = $e->getMessage();
            $this->response->setOutput($responseMessage);
            $this->helperBillmate->log($responseMessage);
            return;
        }

        $this->load->language('extension/module/billmate_accept');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->loadBreadcrumbs();
        $this->loadBaseBlocks();
        $this->loadTextMessage();

        $this->clearCartSession();

        $this->response->setOutput(
            $this->load->view('billmate/accept', $this->getTemplateData())
        );
    }

    /**
     * @return mixed
     */
    protected function loadTextMessage() {

        $textMessage = sprintf(
            $this->language->get('text_guest'),
            $this->url->link('information/contact')
        );
        if ($this->customer->isLogged()) {
            $textMessage = sprintf(
                $this->language->get('text_customer'),
                $this->url->link('account/account', '', true),
                $this->url->link('account/order', '', true),
                $this->url->link('account/download', '', true),
                $this->url->link('information/contact')
            );
        }

        $this->templateData['text_message'] = $textMessage;
    }

    protected function clearCartSession() {
        $this->helperBillmate->unsetCart();
        $this->helperBillmate->resetSessionBmHash();
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function getBillmateOrderModel()
    {
        return $this->model_billmate_order;
    }
}