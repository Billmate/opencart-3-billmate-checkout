<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutAccept extends FrontBmController {

    public function index() {
        if ($this->helperBillmate->isAddLog()) {
            $requestBm = $this->getRequestData();
            $this->helperBillmate->log($requestBm);
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
}