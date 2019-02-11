<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutCancel extends FrontBmController {

    public function index() {
        if ($this->helperBillmate->isAddLog()) {
            $requestBm = $this->getRequestData();
            $this->helperBillmate->log($requestBm);
        }

        $this->load->language('extension/module/billmate_cancel');
        $this->loadBreadcrumbs();
        $this->loadBaseBlocks();
        $this->loadTextMessage();

        $this->document->setTitle($this->language->get('heading_title'));
        $this->response->setOutput(
            $this->load->view('billmate/cancel', $this->getTemplateData())
        );
    }

    /**
     * @return mixed
     */
    protected function loadTextMessage() {

        $this->templateData['text_message'] = $this->language->get('text_message');
        return $this;
    }
}