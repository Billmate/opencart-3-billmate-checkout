<?php
require_once(DIR_APPLICATION . 'controller/billmatecheckout/FrontBmController.php');

class ControllerBillmatecheckoutCart extends FrontBmController {

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout/request');
        $this->load->language('checkout/cart');
        $this->load->model('billmate/checkout');
    }

    public function updateProductQty()
    {
        if (!empty($this->request->post['quantity'])) {
            foreach ($this->request->post['quantity'] as $key => $value) {
                $this->cart->update($key, $value);
            }

            $this->unsetSessionVars();
        }

        $respData = $this->getResponceData();

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

        $respData = $this->getResponceData();
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
    protected function getResponceData()
    {
        $respData['cart_block'] = $this->getBillmateCheckoutModel()->getBMCartBlock();
        $respData['shipping_block'] = $this->getBillmateCheckoutModel()->getBMShippingMethodsBlock();
        $bmResponse = $this->getBillmateCheckoutRequestModel()->setIsUpdated(true)->getResponse();

        if (isset($bmResponse['url'])) {
            $respData['iframe_url'] = $bmResponse['url'];
        }
        return $respData;
    }
}