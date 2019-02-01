<?php
class ModelBillmateCheckoutRequest extends Model {


    const METHOD_CODE = 93;

    const WINDOW_MODE = 'iframe';

    const SEND_RECIEPT = 'yes';

    const REDIRECT_ON_SUCCESS = true;

    /**
     * @var array
     */
    protected $requestData = [];

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * ModelBillmateCheckoutRequest constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        $billmateConnection = $this->helperBillmate->getBillmateConnection();
        $requestCartData = $this->model_billmate_checkout_request->getCartData();
        $billmateHash = $this->helperBillmate->getSessionBmHash();
        if ($billmateHash) {
            $requestData = [
                'PaymentData' => ['hash' => $billmateHash]
            ];
            return $billmateConnection->getCheckout($requestData);
        }
        return $billmateConnection->initCheckout($requestCartData);
    }

    /**
     * @return array
     */
    public function getCartData()
    {
        $this->initPaymentData();
        $this->initCheckoutData();
        $this->addArticlesData();
        $this->addDiscountData();
        $this->addCartTotalsData();

        return $this->getRequestData();
    }

    /**
     * @return $this
     */
    protected function initPaymentData()
    {
        $this->requestData['PaymentData'] = [
                'method' => self::METHOD_CODE,
                'currency' => strtoupper($this->session->data['currency']),
                'language' => 'sv',
                'country' => 'SE',
                'orderid' => $this->generateBillmateOrderId(),
                'logo' => '',
                'accepturl' => $this->url->link(
                    'billmatecheckout/accept',
                    'method=checkout',
                    $this->request->server['HTTPS']
                ),
                'cancelurl' => $this->url->link(
                    'billmatecheckout/cancel',
                    'method=checkout',
                    $this->request->server['HTTPS']
                ),
                'callbackurl' => $this->url->link(
                    'billmatecheckout/callback',
                    'method=checkout',
                    $this->request->server['HTTPS']
                ),
                'returnmethod' => ($this->request->server['HTTPS'] == "on") ?'POST' : 'GET',
            ];

        return $this;
    }

    /**
     * @return $this
     */
    protected function initCheckoutData()
    {
        $termsUrl = $this->config->get('module_billmate_checkout_gdpr_link');
        $this->requestData['CheckoutData'] = [
            'terms' => $termsUrl,
            'windowmode' => self::WINDOW_MODE,
            'sendreciept' => self::SEND_RECIEPT,
            'redirectOnSuccess' => self::REDIRECT_ON_SUCCESS,
        ];

        return $this;
    }

    /**
     * @return $this
     */
    protected function addArticlesData()
    {
        $data['products'] = array();

        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            $prices = $this->getProductPrices($product);
            $this->requestData['Articles'][] = [
                'quantity' => $product['quantity'],
                'title' => $product['name'],
                'artnr' => $product['model'],
                'aprice' => $this->toCents($prices['unit_price']),
                'taxrate' => 0,
                'discount' => 0,
                'withouttax' => $this->toCents($prices['total_without_tax']),
                'total_article' => $this->toCents($prices['total_with_tax']),
            ];
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function addDiscountData()
    {

        $couponCode = $this->session->data['coupon'];
        if ($couponCode) {
            $subTotal = $this->cart->getSubTotal();
            $couponDiscount = $this->model_extension_total_coupon->getCoupon($couponCode);
            $discountAmount = 0;
            switch ($couponDiscount['type']) {
                case 'P':
                    $discountAmount = ($subTotal/100) * $couponDiscount['discount'];
                    break;
                case 'F':
                    $discountAmount = $couponDiscount['discount'];
                    break;
            }
            $discountAmount = $this->convert($discountAmount);
            $this->requestData['Articles'][] = [
                'quantity' => 1,
                'title' => $couponDiscount['name'],
                'artnr' => 'discount-item',
                'aprice' => $this->toCents(-$discountAmount),
                'taxrate' => 0,
                'discount' => 0,
                'withouttax' => $this->toCents(-$discountAmount),
                'total_article' => $this->toCents(-$discountAmount),
            ];
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function addCartTotalsData()
    {
        $cartTotals = $this->getCartTotals();
        $this->requestData['Cart'] = [
            'Shipping' =>
                array (
                    'withouttax' => $this->toCents($cartTotals['total_shipping']),
                    'taxrate' => 0.0,
                ),
            'Total' =>
                array (
                    'withouttax' => $this->toCents($cartTotals['total_without_tax']),
                    'tax' => 0.0,
                    'rounding' => 0.0,
                    'withtax' => $this->toCents($cartTotals['total_with_tax']),
                ),
        ];
        return $this;
    }

    /**
     * @return array
     */
    protected function getCartTotals()
    {
        $shippingPrice = 0;
        $cartTotals = [];
        $totals = [];

        $taxes = $this->cart->getTaxes();
        $total = $this->cart->getTotal();

        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );
        $this->model_extension_total_coupon->getTotal($total_data);
        $this->model_extension_total_shipping->getTotal($total_data);

        if (isset($this->session->data['shipping_method'])) {
            $shippingPrice = $this->session->data['shipping_method']['cost'];
        }

        $cartTotals['total_shipping'] = $this->convert($shippingPrice);
        $cartTotals['total_without_tax'] = $cartTotals['total_with_tax'] = $this->convert($total);

        return $cartTotals;
    }

    /**
     * @return array
     */
    protected function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @param $product
     */
    protected function getProductPrices($product)
    {
        $productPrices = [];
        $unit_price = $this->tax->calculate(
            $product['price'],
            $product['tax_class_id'],
            $this->config->get('config_tax')
        );

        $productPrices['unit_price'] = $this->convert($unit_price);

        $productPrices['total_without_tax'] = $product['quantity'] * $productPrices['unit_price'];
        $this->convert($product['quantity'] * $product['price']);

        $productPrices['total_with_tax'] = $product['quantity'] * $productPrices['unit_price'] ;
        return $productPrices;
    }

    /**
     * @param $value
     *
     * @return float
     */
    protected function toCents($value)
    {
        return $value * 100;
    }

    /**
     * @param $amount
     *
     * @return float
     */
    protected function convert($amount)
    {
        return $this->currency->format(
            $amount,
            $this->session->data['currency'],
            '',
            false
        );
    }

    protected function generateBillmateOrderId()
    {
        $products = $this->cart->getProducts();
         return current($products)['cart_id'] . '-' . time();
    }
}