<?php
class ModelBillmateCheckout extends Model {

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * @var \Billmate\Bmcart
     */
    protected $bmcart ;

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
        $this->bmcart  = new \Billmate\Bmcart($registry);
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
				
				setcookie('billmate_hash_Copy', $hash, time()+ 86400);
            }

            $checkoutData['iframe_url'] = $bmResponse['url'];
        }

        if (isset($bmResponse['PaymentData']['url'])) {
            $checkoutData['iframe_url'] = $bmResponse['PaymentData']['url'];
        }

        if (isset($bmResponse['message'])) {
            $checkoutData['error_message'] = $this->getHelper()->encodeUtf8($bmResponse['message']);
        }

        $checkoutData['cart_block'] = $this->getBMCartBlock();
        $checkoutData['coupon_block'] = $this->getBMCouponBlock();
        $checkoutData['shipping_block'] = $this->getBMShippingMethodsBlock();
        $checkoutData['bm_options'] = $this->getPluginOptions();
		
        return $checkoutData;
    }

    /**
     * @return string
     */
    public function getBMCartBlock()
    {
        $data['action'] = $this->url->link('billmatecheckout/cart/edit', '', true);

        $this->load->model('tool/image');
        $this->load->model('tool/upload');

        $data['products'] = array();

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = '';
            }

            $option_data = array();

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
            } else {
                $price = false;
                $total = false;
            }

            $recurring = '';

            if ($product['recurring']) {
                $frequencies = array(
                    'day'        => $this->language->get('text_day'),
                    'week'       => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month'      => $this->language->get('text_month'),
                    'year'       => $this->language->get('text_year')
                );

                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }

                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }

            $data['products'][] = array(
                'cart_id'   => $product['cart_id'],
                'thumb'     => $image,
                'name'      => $product['name'],
                'model'     => $product['model'],
                'option'    => $option_data,
                'recurring' => $recurring,
                'quantity'  => $product['quantity'],
                'stock'     => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                'reward'    => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                'price'     => $price,
                'total'     => $total,
                'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        return $this->load->view('billmate/cart', $data);
    }

    /**
     * @return string
     */
    public function getBMCouponBlock()
    {
        if ($this->config->get('total_coupon_status')) {
            $this->load->language('extension/total/coupon');

            if (isset($this->session->data['coupon'])) {
                $data['coupon'] = $this->session->data['coupon'];
            } else {
                $data['coupon'] = '';
            }

            return $this->load->view('billmate/coupon', $data);
        }
        return '';
    }

    /**
     * @return string
     */
    public function getBMShippingMethodsBlock()
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

        $cartHasShipping = $this->getBmcart()->hasShipping();
        if (isset($this->session->data['shipping_methods']) && $cartHasShipping) {
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

    /**
     * @return array
     */
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
            'updateCartUrl' => $this->url->link('billmatecheckout/cart/updateProductQty', '', true),
            'removeCartItemUrl' => $this->url->link('billmatecheckout/cart/removeCartItem', '', true),
            'addCouponUrl' => $this->url->link('billmatecheckout/cart/addCoupon', '', true),
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

    /**
     * @return \Billmate\Bmcart
     */
    public function getBmcart(): \Billmate\Bmcart
    {
        return $this->bmcart;
    }
}