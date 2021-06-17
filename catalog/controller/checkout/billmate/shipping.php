<?php

class ControllerCheckoutBillmateShipping extends Controller
{
    public function shipping()
    {
        $this->load->language('checkout/checkout');

        if (!isset($this->request->post['shipping_method'])) {
            $json['error']['warning'] = $this->language->get('error_shipping');
        } else {
            $shipping = explode('.', $this->request->post['shipping_method']);

            if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                $json['error']['warning'] = $this->language->get('error_shipping');
            }
        }

        if (!empty($json['error'])) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }

        $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
        ]));
    }

    public function comment()
    {
        $this->load->model('checkout/billmate/order');

        $this->session->data['comment'] = strip_tags($this->request->post['comment']);

        $this->model_checkout_billmate_order->updateOrderComment(
            $this->request->post['order_id'],
            $this->request->post['comment']
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode([
            'success' => true,
        ]));
    }
}
