<?php
class ModelBillmateCheckout extends Model {

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * ModelBillmateCheckout constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout/request');
        $this->load->model('billmate/checkout/shipping');
        $this->load->model('setting/extension');
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return array
     */
    public function getCheckoutData()
    {
        $checkoutData = [];

        $bmResponse = $this->model_billmate_checkout_request->getResponse();

        if (isset($bmResponse['url'])) {
            $hash = $this->helperBillmate->getHashFromUrl($bmResponse['url']);
            if ($hash) {
                $this->helperBillmate->setSessionBmHash($hash);
            }

            $checkoutData['iframe_url'] = $bmResponse['url'];
        }

        if (isset($bmResponse['PaymentData']['url'])) {
            $checkoutData['iframe_url'] = $bmResponse['PaymentData']['url'];
        }

        if (isset($bmResponse['message'])) {
            $checkoutData['error_message'] = $bmResponse['message'];
        }

        $checkoutData['shipping_block'] = $this->getBMShippingMethodsBlock();
        $checkoutData['bm_options'] = $this->getPluginOptions();

        return $checkoutData;
    }


    protected function getBMShippingMethodsBlock() {
        $this->load->language('checkout/checkout');
        $shippingAddress = $this->model_billmate_checkout_shipping->getDefaultAddress();

        $method_data = array();
        $results = $this->model_setting_extension->getExtensions('shipping');

        foreach ($results as $result) {
            if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                $this->load->model('extension/shipping/' . $result['code']);

                $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($shippingAddress);

                if ($quote) {
                    $method_data[$result['code']] = array(
                        'title'      => $quote['title'],
                        'quote'      => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error'      => $quote['error']
                    );
                    $data['quote'] = $quote;
                }
            }
        }

        $sort_order = array();

        foreach ($method_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $method_data);
        $this->session->data['shipping_methods'] = $method_data;


        if (empty($this->session->data['shipping_methods'])) {
            $data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['shipping_methods'])) {
            $data['shipping_methods'] = $this->session->data['shipping_methods'];
        } else {
            $data['shipping_methods'] = array();
        }

        if (isset($this->session->data['shipping_method']['code'])) {
            $data['code'] = $this->session->data['shipping_method']['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }
        return $this->load->view('billmate/shipping_method', $data);
    }


    protected function getPluginOptions() {

        $pluginOptions = [
            'saveShippingUrl' => $this->url->link('billmatecheckout/ajax/updateShipping', '', true)
        ];
        return json_encode($pluginOptions);
    }

}