<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutAjax extends FrontBmController {

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout/request');
    }

    public function updateShipping()
    {
        $this->updateShippingData();
        $bmResponse = $this->model_billmate_checkout_request
            ->setIsUpdated(true)
            ->getResponse();
        $responseData = [];
        if (isset($bmResponse['url'])) {
            $responseData = [
                'url' => $bmResponse['url']
            ];
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($responseData));
    }

    protected function updateShippingData()
    {
        if (isset($this->request->post['shipping_method'])) {
            $shipping = explode('.', $this->request->post['shipping_method']);
            if (isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                $this->session->data['comment'] = strip_tags($this->request->post['comment']);
            }
        }
    }
}