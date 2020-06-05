<?php
class ModelBillmateOrder extends ModelCheckoutOrder
{
    const BILLMATE_METHOD_IDN = 'Billmate checkout';

    const BILLMATE_METHOD_CODE = 'bmch';

    const DEFAUL_ORDER_STATUS_ID = 1;

    /**
     * @var array
     */
    protected $order_data = [];

    /**
     * @var array
     */
    protected $paymentInfo;

    /**
     * @var \Billmate\Bmcart
     */
    protected $bmcart;

    /**
     * @var array
     */
    protected $statusMap = [
        'Paid' => 2,
        'Cancelled' => 7,
        'Created' => 1,
    ];

    /**
     * ModelBillmateOrder constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
        $this->bmcart  = new \Billmate\Bmcart($registry);
        $this->load->model('billmate/service');
        $this->load->language('extension/module/billmate_accept');
        $this->load->model('account/customer');
        $this->load->model('checkout/marketing');
        $this->load->model('billmate/order/address');
        $this->load->model('billmate/order/mail');
    }

    /**
     * @param $paymentInfo
     */
    public function createBmOrder($paymentNumber, $paymentInfo)
    {
        $this->paymentInfo = $paymentInfo;
        $orderData = $this->collectOrderData();

        $orderId = $this->addOrder($orderData);

        if (!$orderId) {
            throw new Exception('The order wasn\'t created in the web-store');
        }

        $this->addOrderHistory(
            $orderId,
            $this->getHelperBillmate()->getNewOrderStatusId()
        );
        $sessionId = $this->getHelperBillmate()->getCartId($this->paymentInfo['PaymentData']['orderid']);

        $this->getBillmateService()->addInvoiceIdToOrder(
            $orderId,
            $paymentNumber
        );

        $actualPaymentInfo['PaymentInfo']['real_order_id'] = $orderId;
        $actualPaymentInfo['PaymentData']['number'] = $paymentNumber;
        $actualPaymentInfo['PaymentData']['orderid'] = $orderId;

        $this->updatePaymentData($actualPaymentInfo);

        $this->sendConfirmationEmail($orderId);

        $this->bmcart->clearBySession($sessionId);
        return $orderId;
    }

    /**
     * @param $order_id
     */
    protected function sendConfirmationEmail($orderId)
    {
        $orderInfo = $this->getOrder($orderId);
        $this->getOrderMailModel()->sendConfirmation($orderInfo);
    }

    /**
     * @param $paymentInfo
     * @param $bmPaymentState
     */
    public function updateOrderStatus($paymentInfo, $bmPaymentState)
    {
        if (isset($paymentInfo['PaymentInfo']['real_order_id'])) {
            $orderId = $paymentInfo['PaymentInfo']['real_order_id'];
            $statusId = $this->getSystemSatusId($bmPaymentState);
            $this->addOrderHistory($orderId, $statusId);

            $orderData = $this->getOrder($orderId);
            $this->getOrderMailModel()->sendUpdateStatus($orderData);
        }

    }

    /**
     * @param $paymentInfo
     */
    protected function updatePaymentData($paymentInfo)
    {
        $billmateConnection = $this->getHelperBillmate()->getBillmateConnection();
        $billmateConnection->updatePayment($paymentInfo);
    }

    /**
     * @return array
     */
    protected function collectOrderData()
    {
        $this->addGeneralStoreData();
        $this->initTotals();
        $this->addShippingData();
        $this->addBillingData();
        $this->addProductsData();
        return $this->order_data;
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addGeneralStoreData()
    {
        $generalStoreData = [
            'invoice_prefix' => $this->config->get('config_invoice_prefix'),
            'store_id' =>  $this->config->get('config_store_id'),
            'store_name' => $this->config->get('config_name'),
            'store_url' => $this->config->get('config_url'),
            'language_id' => $this->config->get('config_language_id'),
            'currency_id' => $this->currency->getId($this->paymentInfo['PaymentData']['currency']),
            'currency_code' => $this->paymentInfo['PaymentData']['currency'],
            'currency_value' => $this->currency->getValue($this->session->data['currency']),
            'customer_id' => $this->getCustomerId(),
            'customer_group_id' => $this->getCustomerGroupId(),
            'vouchers' => $this->getVouchers(),
            'ip' => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip' => $this->getForwardedIp(),
            'user_agent' => $this->getUserAgent(),
            'accept_language' => $this->getAcceptLanguage(),
        ];
        $affiliateInfo = $this->getAffiliateInfo();
        $generalStoreData = array_merge($generalStoreData, $affiliateInfo);

        return $this->appendToOrderData($generalStoreData);
    }

    /**
     * @return array
     */
    public function getAffiliateInfo()
    {
        $orderData['affiliate_id'] = 0;
        $orderData['commission'] = 0;
        $orderData['marketing_id'] = 0;
        $orderData['tracking'] = '';

        if (!isset($this->request->cookie['tracking'])) {
            return $orderData;
        }

        $orderData['tracking'] = $this->request->cookie['tracking'];

        $subtotal = $this->cart->getSubTotal();
        $affiliate_info = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

        if ($affiliate_info) {
            $orderData['affiliate_id'] = $affiliate_info['customer_id'];
            $orderData['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
        }

        $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

        if ($marketing_info) {
            $orderData['marketing_id'] = $marketing_info['marketing_id'];
        }

        return $orderData;
    }

    /**
     * @return string
     */
    public function getAcceptLanguage()
    {
        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            return $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getForwardedIp()
    {
        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            return $this->request->server['HTTP_CLIENT_IP'];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            return $this->request->server['HTTP_USER_AGENT'];
        }
        return '';
    }

    /**
     * @return array
     */
    public function getVouchers()
    {
        $vouchers = [];
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $vouchers[] = [
                    'description'      => $voucher['description'],
                    'code'             => token(10),
                    'to_name'          => $voucher['to_name'],
                    'to_email'         => $voucher['to_email'],
                    'from_name'        => $voucher['from_name'],
                    'from_email'       => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message'          => $voucher['message'],
                    'amount'           => $voucher['amount']
                ];
            }
        }
        return $vouchers;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        if (isset($this->session->data['customer_id'])) {
            return  (int)$this->session->data['customer_id'];
        }
        return 0;
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        if (isset($this->session->data['guest']['customer_group_id'])) {
            return  (int)$this->session->data['guest']['customer_group_id'];
        }
        return (int)$this->config->get('config_customer_group_id');
    }


    /**
     * @return $this
     */
    protected function addShippingData()
    {
        $shippingBmData = $this->getBmShippingData();
        $countryData = $this->getCountryData($shippingBmData['country']);
        $shippingData = [
            'shipping_firstname' => array_key_exists('firstname', $shippingBmData)?$shippingBmData['firstname']:'',
            'shipping_lastname' => array_key_exists('lastname', $shippingBmData)?$shippingBmData['lastname']:'',
            'shipping_address_1' => array_key_exists('street', $shippingBmData)?$shippingBmData['street']:'',
            'shipping_city' => array_key_exists('city', $shippingBmData)?$shippingBmData['city']:'',
            'shipping_postcode' => array_key_exists('zip', $shippingBmData)?$shippingBmData['zip']:'',
            'shipping_address_2' => '',
            'shipping_company' => '',
            'shipping_zone' => '',
            'shipping_zone_id' => '',
            'shipping_country' => $countryData['name'],
            'shipping_country_id' => $countryData['country_id'],
            'shipping_address_format' => '',
            'shipping_custom_field' =>[],
            'shipping_method' => $this->getShippingMethodName(),
            'shipping_code' => $this->getShippingMethodCode(),
            'comment' => $this->getComment()
        ];

        return $this->appendToOrderData($shippingData);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addBillingData()
    {
        $billingBmData = $this->getBmBillingData();
        $countryData = $this->getCountryData($billingBmData['country']);
        $billingData = [
            'firstname' => $billingBmData['firstname'],
            'lastname' => $billingBmData['lastname'],
            'email' => $billingBmData['email'],
            'telephone' => $billingBmData['phone'],
            'custom_field' => NULL,
            'payment_firstname' => $billingBmData['firstname'],
            'payment_lastname' => $billingBmData['lastname'],
            'payment_address_1' => $billingBmData['street'],
            'payment_city' => $billingBmData['city'],
            'payment_postcode' => $billingBmData['zip'],
            'payment_method' => self::BILLMATE_METHOD_IDN . ' - ' . $this->getTypeBmPayment(),
            'payment_code' => self::BILLMATE_METHOD_CODE,
            'payment_address_2' => '',
            'payment_company' => '',
            'payment_zone' => '',
            'payment_zone_id' => '',
            'payment_country' => $countryData['name'],
            'payment_country_id' => $countryData['country_id'],
            'payment_address_format' => '',
            'payment_custom_field' =>[]
        ];

        return $this->appendToOrderData($billingData);
    }

    /**
     * @return $this
     */
    protected function initTotals()
    {
        $totals['totals'] = [
            [
                'code' => 'sub_total',
                'title' => $this->language->get('text_order_sub_total'),
                'value' =>$this->centsToPrice(
                    $this->paymentInfo['Cart']['Total']['sub_total']
                ),
                'sort_order' => 4
            ],
            [
                'code' => 'shipping',
                'title' => $this->paymentInfo['Cart']['Shipping']['method'],
                'value' => $this->centsToPrice(
                    $this->paymentInfo['Cart']['Shipping']['withouttax']
                ),
                'sort_order' => 6
            ],
            [
                'code' => 'tax',
                'title' => $this->language->get('text_order_tax'),
                'value' => $this->centsToPrice(
                    $this->paymentInfo['Cart']['Total']['tax']
                ),
                'sort_order' => 8
            ],
            [
                'code' => 'total',
                'title' => $this->language->get('text_order_total'),
                'value' => $this->centsToPrice(
                    $this->paymentInfo['Cart']['Total']['withtax']
                ),
                'sort_order' => 10
            ]
        ];
        $totals['total'] = $this->centsToPrice($this->paymentInfo['Cart']['Total']['withtax']);

        return $this->appendToOrderData($totals);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addProductsData()
    {
        $bmRequestProducts = $this->getOrderedProducts();
        $bmProducts = [];
        foreach ($bmRequestProducts as $_product) {
            $bmProducts[] = [
                'product_id' => $_product['product_id'],
                'reward' => $_product['reward'],
                'points' => $_product['points'],
                'subtract' => $_product['subtract'],
                'option' => json_decode($_product['option'], true),
                'name' => $_product['title'],
                'model' => $_product['title'],
                'quantity' => $_product['quantity'],
                'price' => $this->centsToPrice($_product['aprice']),
                'total' => $this->centsToPrice($_product['total_article']),
                'download' => json_decode($_product['download'], true),
                'tax' => 0,
            ];
        }

        $products['products'] = $bmProducts;
        return $this->appendToOrderData($products);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        if (isset($this->session->data['comment'])) {
            return $this->session->data['comment'];
        }

        return '';
    }

    /**
     * @param $bmCountryCode
     *
     * @return array
     */
    public function getCountryData($bmCountryCode)
    {
        $countryData = $this->getOrderAddressModel()->getCountryByCode($bmCountryCode);
        if ($countryData) {
           return  $countryData;
        }

        return [
            'name' => $bmCountryCode,
            'country_id' => '',
        ];
    }


    /**
     * @param $data
     *
     * @return $thisappendToOrderData
     */
    protected function appendToOrderData($data)
    {
        $this->order_data = array_merge($this->order_data, $data);
        return $this;
    }

    /**
     * @param $value
     *
     * @return float|int
     */
    protected function centsToPrice($value)
    {
        return ($value / 100);
    }

    /**
     * @return mixed
     */
    protected function getOrderedProducts()
    {
        return $this->paymentInfo['Articles'];
    }

    /**
     * @return array
     */
    protected function getBmBillingData()
    {
        $billingAddress = $this->paymentInfo['Customer']['Billing'];
        return $this->encodeData($billingAddress);
    }

    /**
     * @return array
     */
    protected function getBmShippingData()
    {
        if (isset($this->paymentInfo['Customer']['Shipping'])) {
            return $this->encodeData(
                $this->paymentInfo['Customer']['Shipping']
            );
        }
        return $this->getBmBillingData();
    }

    /**
     * @return string
     */
    protected function getTypeBmPayment()
    {
        return $this->getHelperBillmate()->getPaymentMethodByCode(
            $this->paymentInfo['PaymentData']['method']
        );
    }

    /**
     * @return string
     */
    protected function getShippingMethodName()
    {
        return $this->paymentInfo['Cart']['Shipping']['method'];
    }

    /**
     * @return string
     */
    protected function getShippingMethodCode()
    {
        return $this->paymentInfo['Cart']['Shipping']['method_code'];
    }

    /**
     * @param $bmPaymentState
     *
     * @return int
     */
    protected function getSystemSatusId($bmPaymentState)
    {
        if(isset($this->statusMap[$bmPaymentState])) {
            return $this->statusMap[$bmPaymentState];
        }

        return self::DEFAUL_ORDER_STATUS_ID;
    }

    /**
     * @param $addressData
     *
     * @return mixed
     */
    protected function encodeData($addressData)
    {
        foreach ($addressData as $key => $addressField) {
            $addressData[$key] = $this->getHelperBillmate()->encodeUtf8($addressField);
        }
        return $addressData;
    }

    /**
     * @return ModelBillmateOrderAddress
     */
    protected function getOrderAddressModel()
    {
        return $this->model_billmate_order_address;
    }

    /**
     * @return ModelBillmateOrderMail
     */
    protected function getOrderMailModel()
    {
        return $this->model_billmate_order_mail;
    }

    /**
     * @return ModelBillmateService
     */
    protected function getBillmateService()
    {
        return $this->model_billmate_service;
    }

    /**
     * @return Helperbm
     */
    protected function getHelperBillmate()
    {
        return $this->helperBillmate;
    }
}