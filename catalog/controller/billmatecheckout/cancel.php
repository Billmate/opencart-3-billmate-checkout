<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/corebm.php');

class ControllerBillmatecheckoutCancel extends ControllerBillmatecheckoutCorebm {

    public function index()
    {
        $this->load->language('extension/module/billmate_cancel');
        $requestBm = $this->getRequestData();
        $this->helperBillmate->log($requestBm);

        $data = $this->getTepmlateVariables();

        $this->document->setTitle($this->language->get('heading_title'));
        $this->response->setOutput($this->load->view('common/success', $data));
    }

    /**
     * @return mixed
     */
    protected function getTepmlateVariables() {

        $data['breadcrumbs'] = $this->getBreadcrumbs();
        $data['text_message'] = $this->language->get('text_message');

        $data['continue'] = $this->url->link('common/home');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        return $data;
    }

    /**
     * @return array
     */
    protected function getBreadcrumbs() {
        $breadcrumbs[] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_basket'),
            'href' => $this->url->link('checkout/cart')
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', true)
        ];

        $breadcrumbs[] = [
            'text' => $this->language->get('text_success'),
            'href' => $this->url->link('billmatecheckout/accept')
        ];
        return $breadcrumbs;
    }
}