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

        $this->initDefaultShippingMethod();
        $bmResponse = $this->getModelBillmateCheckoutRequest()->getResponse();

        if (isset($bmResponse['url'])) {
            $hash = $this->getHelper()->getHashFromUrl($bmResponse['url']);
            if ($hash) {
                $this->getHelper()->setSessionBmHash($hash);
            }

            $checkoutData['iframe_url'] = $bmResponse['url'];
        }

        if (isset($bmResponse['PaymentData']['url'])) {
            $checkoutData['iframe_url'] = $bmResponse['PaymentData']['url'];
        }

        if (isset($bmResponse['message'])) {
            $checkoutData['error_message'] = $this->getHelper()->encodeUtf8($bmResponse['message']);
        }

        $checkoutData['shipping_block'] = $this->getBMShippingMethodsBlock();
        $checkoutData['bm_options'] = $this->getPluginOptions();

        return $checkoutData;
    }

    /**
     * @return string
     */
    protected function getBMShippingMethodsBlock()
    {
        $this->load->language('checkout/checkout');

        if (empty($this->session->data['shipping_methods'])) {
            $data['error_warning'] = sprintf(
                $this->language->get('error_no_shipping'),
                $this->url->link('information/contact')
            );
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

        $data['show_message_block'] = $this->getHelper()->isAllowedInvoiceMessage();

        return $this->load->view('billmate/shipping_method', $data);
    }

    public function getActiveShippingMethods()
    {
        $methods = [];
        $shippingAddress = $this->model_billmate_checkout_shipping->getDefaultAddress();
        $results = $this->model_setting_extension->getExtensions('shipping');

        foreach ($results as $result) {
            if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                $this->load->model('extension/shipping/' . $result['code']);

                $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($shippingAddress);

                if ($quote) {
                    $methods[$result['code']] = array(
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

        foreach ($methods as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $methods);

        $this->session->data['shipping_methods'] = $methods;
        return $methods;
    }

    /**
     * @return string
     */
    protected function getPluginOptions()
    {
        $pluginOptions = [
            'saveShippingUrl' => $this->url->link('billmatecheckout/ajax/updateShipping', '', true),
        ];
        return json_encode($pluginOptions);
    }

    /**
     * @return ModelBillmateCheckoutRequest
     */
    public function getModelBillmateCheckoutRequest()
    {
        return $this->model_billmate_checkout_request;
    }

    public function initDefaultShippingMethod()
    {
        $shippingMethods = $this->getActiveShippingMethods();
        if (
            $shippingMethods &&
            !isset($this->session->data['shipping_method'])
        ) {
            $firstShippingMethod = current($shippingMethods);
            $this->session->data['shipping_method'] = current($firstShippingMethod['quote']);
        }
    }

    /**
     * @return HelperBillmate|Helperbm
     */
    public function getHelper()
    {
        return $this->helperBillmate;
    }
}