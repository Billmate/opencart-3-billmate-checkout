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
        'Paid' => 5,
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
    }

    /**
     * @param $paymentInfo
     */
    public function createBmOrder($paymentNumber, $paymentInfo)
    {
        $this->paymentInfo = $paymentInfo;
        $order_data = $this->collectOrderData();

        $orderId = $this->addOrder($order_data);
        if (!$orderId) {
            throw new Exception('The order wasn\'t created in the web-store');
        }

        $this->addOrderHistory($orderId, $this->helperBillmate->getNewOrderStatusId());
        $sessionId = $this->bmcart->getSessionByCartId(
            $this->paymentInfo['PaymentData']['orderid']
        );

        $this->getBillmateService()->addInvoiceIdToOrder(
            $orderId,
            $paymentNumber
        );

        $paymentInfo['PaymentInfo']['real_order_id'] = $orderId;
        $paymentInfo['PaymentData']['number'] = $paymentNumber;
        $paymentInfo['PaymentData']['orderid'] = $orderId;
        $this->updatePaymentData($paymentInfo);

        $this->bmcart->clearBySession($sessionId);
        return $orderId;
    }

    /**
     * @param $paymentInfo
     * @param $bmPaymentState
     */
    public function updateOrderStatus($paymentInfo, $bmPaymentState)
    {
        $orderId = $paymentInfo['PaymentInfo']['real_order_id'];
        $statusId = $this->getSystemSatusId($bmPaymentState);
        $this->addOrderHistory($orderId, $statusId);
    }

    /**
     * @param $paymentInfo
     */
    protected function updatePaymentData($paymentInfo)
    {
        $billmateConnection = $this->helperBillmate->getBillmateConnection();
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
            'customer_id' => 0,
            'customer_group_id' => 0,
            'vouchers' =>[],
            'comment' => '',
            'affiliate_id' => 0,
            'commission' => 0,
            'marketing_id' => 0,
            'tracking' => '',
            'ip' => '',
            'forwarded_ip' => '',
            'user_agent' => '',
            'accept_language' => '',
        ];

        return $this->appendToOrderData($generalStoreData);
    }


    /**
     * @return $this
     */
    protected function addShippingData()
    {
        $shippingBmData = $this->getBmShippingData();
        $shippingData = [
            'shipping_firstname' => $shippingBmData['firstname'],
            'shipping_lastname' => $shippingBmData['lastname'],
            'shipping_address_1' => $shippingBmData['street'],
            'shipping_city' => $shippingBmData['city'],
            'shipping_postcode' => $shippingBmData['zip'],
            'shipping_address_2' => '',
            'shipping_company' => '',
            'shipping_zone' => '',
            'shipping_zone_id' => '',
            'shipping_country' => '',
            'shipping_country_id' => '',
            'shipping_address_format' => '',
            'shipping_custom_field' =>[],
            'shipping_method' => $this->getShippingMethodName(),
            'shipping_code' => $this->getShippingMethodCode()
        ];

        return $this->appendToOrderData($shippingData);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addBillingData()
    {
        $billingBmData = $this->getBmBillingData();
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
            'payment_country' => '',
            'payment_country_id' => '',
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
        $totals = [
            [
                'code' => 'sub_total',
                'title' => 'Sub-Total',
                'value' =>$this->centsToPrice(
                    $this->paymentInfo['Cart']['Total']['sub_total']
                ),
            ],
            [
                'code' => 'shipping',
                'title' => 'Flat Shipping Rate',
                'value' => $this->centsToPrice(
                    $this->paymentInfo['Cart']['Shipping']['withouttax']
                ),
            ],
            [
                'code' => 'total',
                'title' => 'Total',
                'value' => $this->centsToPrice(
                    $this->paymentInfo['Cart']['Total']['withtax']
                ),
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
                'product_id' => 0,
                'name' => $_product['title'],
                'model' => $_product['title'],
                'option' => [],
                'download' => [],
                'quantity' => $_product['quantity'],
                'subtract' => '1',
                'price' => $this->centsToPrice($_product['aprice']),
                'total' => $this->centsToPrice($_product['total_article']),
                'tax' => 0,
                'reward' => 0,
            ];
        }

        $products['products'] = $bmProducts;
        return $this->appendToOrderData($products);
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
        return $this->helperBillmate->getPaymentMethodByCode(
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
            $addressData[$key] = $this->helperBillmate->encodeUtf8($addressField);
        }
        return $addressData;
    }

    /**
     * @return ModelBillmateService
     */
    protected function getBillmateService()
    {
        return $this->model_billmate_service;
    }
}