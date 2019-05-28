<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutCart extends FrontBmController {

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout/request');
        $this->load->language('checkout/cart');
        $this->load->model('billmate/checkout');
        $this->load->model('extension/total/coupon');
    }

    public function updateProductQty()
    {
        if (!empty($this->request->post['quantity'])) {
            foreach ($this->request->post['quantity'] as $key => $value) {
                $this->cart->update($key, $value);
            }

            $this->unsetSessionVars();
        }

        $respData = $this->getResponseData();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($respData));
    }

    public function removeCartItem()
    {
        $this->load->language('checkout/cart');

        // Remove
        if (isset($this->request->post['cart_item_id'])) {
            $this->cart->remove($this->request->post['cart_item_id']);
            unset($this->session->data['vouchers'][$this->request->post['cart_item_id']]);
            $this->unsetSessionVars();
        }

        $respData = $this->getResponseData();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($respData));
    }

    public function addCoupon()
    {
        $this->load->language('extension/total/coupon');
        $error = '';
        $coupon = '';
        $success = '';

        if (isset($this->request->post['coupon_code'])) {
            $coupon = $this->request->post['coupon_code'];
        }

        $coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

        if (empty($this->request->post['coupon_code'])) {
            $error = $this->language->get('error_empty');
            unset($this->session->data['coupon']);
        } elseif ($coupon_info) {
            $this->session->data['coupon'] = $this->request->post['coupon_code'];
            $success = $this->language->get('text_success');
        } else {
            $error = $this->language->get('error_coupon');
        }

        $respData = $this->getResponseData();
        $respData['error'] = $error;
        $respData['success'] = $success;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($respData));
    }

    /**
     * @return ModelBillmateCheckout
     */
    public function getBillmateCheckoutModel()
    {
        return $this->model_billmate_checkout;
    }

    /**
     * @return ModelBillmateCheckoutRequest
     */
    public function getBillmateCheckoutRequestModel()
    {
        return $this->model_billmate_checkout_request;
    }


    protected function unsetSessionVars()
    {
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['reward']);
    }

    /**
     * @return mixed
     */
    protected function getResponseData()
    {
        $respData['cart_block'] = $this->getBillmateCheckoutModel()->getBMCartBlock();
        $respData['shipping_block'] = $this->getBillmateCheckoutModel()->getBMShippingMethodsBlock();
        $bmResponse = $this->getBillmateCheckoutRequestModel()->setIsUpdated(true)->getResponse();

        $respData['redirect'] = !$this->cart->hasProducts() ? $this->url->link('checkout/cart', '', true) : '';
        if (isset($bmResponse['url'])) {
            $respData['iframe_url'] = $bmResponse['url'];
        }
        return $respData;
    }
}