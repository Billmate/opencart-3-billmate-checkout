<?php
class ModelBillmateCheckoutRequest extends Model {


    const METHOD_CODE = 93;

    const WINDOW_MODE = 'iframe';

    const SEND_RECIEPT = 'yes';

    const REDIRECT_ON_SUCCESS = 'true';

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
        $billmateHash = $this->helperBillmate->getSessionBmHash();
        $requestCartData = $this->getCartData();
        if (!$billmateHash) {
            return $billmateConnection->initCheckout($requestCartData);
        }

        $requestData = [
            'PaymentData' => ['hash' => $billmateHash]
        ];
        $bmCheckoutData = $billmateConnection->getCheckout($requestData);

        if (!$this->isSameCartUsed($requestCartData, $bmCheckoutData)) {
            return $billmateConnection->initCheckout($requestCartData);
        }

        $updateCheckoutData = $this->getUpdateDataFromComparison($bmCheckoutData, $requestCartData);
        if ($updateCheckoutData) {
            return $billmateConnection->updateCheckout($updateCheckoutData);
        }

        return $bmCheckoutData;
    }

    /**
     * @param $requestCartData
     * @param $bmCheckoutData
     *
     * @return bool
     */
    protected function isSameCartUsed($requestCartData, $bmCheckoutData)
    {
        return ($requestCartData['PaymentData']['orderid'] ==
        $bmCheckoutData['PaymentData']['orderid']);
    }

    /**
     * @param $bmCheckoutData
     * @param $requestCartData
     *
     * @return array
     */
    protected function getUpdateDataFromComparison($bmCheckoutData, $requestCartData)
    {
        $updateData = [];

        if (
            $bmCheckoutData['Cart']['Total']['withouttax']
            != $requestCartData['Cart']['Total']['withouttax']
        ) {
            unset($requestCartData['PaymentData']);
            $requestCartData['PaymentData']['number']  = $bmCheckoutData['PaymentData']['number'];
            $requestCartData['PaymentData']['orderid'] = $bmCheckoutData['PaymentData']['orderid'];
            $updateData = $requestCartData;
        }

        return $updateData;
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
                'currency_value' => strtoupper($this->session->data['currency']),
                'language' => 'sv',
                'country' => 'SE',
                'orderid' => $this->generateBillmateOrderId(),
                'sessionid' => $this->generateBillmateOrderId(),
                'logo' => $this->helperBillmate->getLogoName(),
                'accepturl' => $this->url->link(
                    'billmatecheckout/accept',
                    '',
                    $this->request->server['HTTPS']
                ),
                'cancelurl' => $this->url->link(
                    'billmatecheckout/cancel',
                    '',
                    $this->request->server['HTTPS']
                ),
                'callbackurl' => $this->url->link(
                    'billmatecheckout/callback',
                    '',
                    $this->request->server['HTTPS']
                ),
                'returnmethod' => 'POST',
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
        if (isset($this->session->data['coupon'])) {
            $couponCode = $this->session->data['coupon'];
            $couponDiscount = $this->model_extension_total_coupon->getCoupon($couponCode);

            $discountAmount = $this->getDiscountAmount($couponDiscount);
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
                    'method' => $this->getShippingMethodName(),
                    'method_code' => $this->getShippingMethodCode()
                ),
            'Total' =>
                array (
                    'withouttax' => $this->toCents($cartTotals['total_without_tax']),
                    'sub_total' => $this->toCents($cartTotals['sub_total']),
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
        $subtotal = $this->cart->getSubTotal();
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
        $cartTotals['sub_total'] = $this->convert($subtotal);

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
     * @param $couponCode
     *
     * @return float|int
     */
    protected function getDiscountAmount($couponDiscount)
    {
        $subTotal = $this->cart->getSubTotal();
        $discountAmount = 0;
        switch ($couponDiscount['type']) {
            case 'P':
                $discountAmount = ($subTotal/100) * $couponDiscount['discount'];
                break;
            case 'F':
                $discountAmount = $couponDiscount['discount'];
                break;
        }
        return $this->convert($discountAmount);
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

    /**
     * @return string
     */
    protected function generateBillmateOrderId()
    {
        return $this->session->getId();
    }

    /**
     * @return string
     */
    protected function getShippingMethodCode()
    {
        if (isset($this->session->data['shipping_method']['code'])) {
           return $this->session->data['shipping_method']['code'];
        }
        return '';
    }

    /**
     * @return string
     */
    public function getShippingMethodName()
    {
       if (isset($this->session->data['shipping_method']['title'])) {
           return $this->session->data['shipping_method']['title'];
       }
       return '';
    }
}