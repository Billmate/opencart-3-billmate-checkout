<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/corebm.php');

class ControllerBillmatecheckoutAccept extends ControllerBillmatecheckoutCorebm {

    public function index() {
        $this->load->language('extension/module/billmate_accept');

        $this->document->setTitle($this->language->get('heading_title'));
        $data = $this->getTepmlateVariables();
        $this->clearCartSession();

        $this->response->setOutput($this->load->view('common/success', $data));
    }

    /**
     * @return mixed
     */
    protected function getTepmlateVariables() {

        $data['breadcrumbs'] = $this->getBreadcrumbs();

        if ($this->customer->isLogged()) {
            $data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
        } else {
            $data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
        }

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

    protected function clearCartSession() {
        $this->helperBillmate->unsetCart();
        $this->helperBillmate->resetSessionBmHash();
    }
}