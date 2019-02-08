<?php
class ModelBillmateOrder extends ModelCheckoutOrder
{
    const BILLMATE_METHOD_IDN = 'Billmate checkout';

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
    * ModelBillmateOrder constructor.
    *
    * @param $registry
    */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
        $this->bmcart  = new \Billmate\Bmcart($registry);
    }

    /**
     * @param $paymentInfo
     */
    public function createBmOrder($paymentInfo)
    {

        $this->paymentInfo = $paymentInfo;
        $order_data = $this->collectOrderData();

        $orderId = $this->addOrder($order_data);
        if (!$orderId) {
            throw new Exception('The order wasn\'t created in the web-store');
        }

        $this->addOrderHistory($orderId, $this->helperBillmate->getNewOrderStatusId());
        $sessionId = $this->helperBillmate->getCartId($this->paymentInfo['PaymentData']['orderid']);
        $this->bmcart->clearBySession($sessionId);

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
            'customer_id' => 0,
            'customer_group_id' => 0,
            'vouchers' =>
                array (
                ),
            'comment' => '',
            'affiliate_id' => 0,
            'commission' => 0,
            'marketing_id' => 0,
            'tracking' => '',
            'language_id' => $this->config->get('config_language_id'),
            'currency_id' => $this->paymentInfo['PaymentData']['currency'],
            'currency_code' => $this->paymentInfo['PaymentData']['currency'],
            'currency_value' => '0.10000000',
            'ip' => '127.0.0.1',
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
        $shippingData = [
            'shipping_firstname' => 'Alexey',
            'shipping_lastname' => 'customer',
            'shipping_company' => '',
            'shipping_address_1' => 'Mira 20',
            'shipping_address_2' => '',
            'shipping_city' => 'Chist',
            'shipping_postcode' => '222321',
            'shipping_zone' => 'Horad Minsk',
            'shipping_zone_id' => '339',
            'shipping_country' => 'Belarus',
            'shipping_country_id' => '20',
            'shipping_address_format' => '',
            'shipping_custom_field' =>[],
            'shipping_method' => 'Flat Shipping Rate',
            'shipping_code' => 'flat.flat'
        ];

        return $this->appendToOrderData($shippingData);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addBillingData()
    {
            $paymentMethod = $this->helperBillmate->getPaymentMethodByCode(
                $this->paymentInfo['PaymentData']['method']
            );
            $billingData = [
            'firstname' => $this->paymentInfo['Customer']['Billing']['firstname'],
            'lastname' => $this->paymentInfo['Customer']['Billing']['lastname'],
            'email' => $this->paymentInfo['Customer']['Billing']['email'],
            'telephone' => $this->paymentInfo['Customer']['Billing']['phone'],
            'custom_field' => NULL,
            'payment_firstname' => $this->paymentInfo['Customer']['Billing']['firstname'],
            'payment_lastname' => $this->paymentInfo['Customer']['Billing']['lastname'],
            'payment_company' => '',
            'payment_address_1' => $this->paymentInfo['Customer']['Billing']['street'],
            'payment_address_2' => '',
            'payment_city' => $this->paymentInfo['Customer']['Billing']['city'],
            'payment_postcode' => $this->paymentInfo['Customer']['Billing']['zip'],
            'payment_zone' => '',
            'payment_zone_id' => '',
            'payment_country' => '',
            'payment_country_id' => '',
            'payment_address_format' => '',
            'payment_custom_field' =>
                array (
                ),
            'payment_method' => self::BILLMATE_METHOD_IDN . ' - ' . $paymentMethod,
            'payment_code' => 'bmch'
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
                'value' => 101.0,
                'sort_order' => '1',
            ],
            [
                'code' => 'shipping',
                'title' => 'Flat Shipping Rate',
                'value' => '5.00',
                'sort_order' => '3',
            ],
            [
                'code' => 'total',
                'title' => 'Total',
                'value' => 106.0,
                'sort_order' => '9',
            ]
        ];
        $totals['total'] = 106.0;

        return $this->appendToOrderData($totals);
    }

    /**
     * @return ModelBillmateOrder
     */
    protected function addProductsData()
    {
        $products =  [
            'products' =>
                [
                    array (
                        'product_id' => '40',
                        'name' => 'iPhone',
                        'model' => 'product 11',
                        'option' =>
                            array (
                            ),
                        'download' =>
                            array (
                            ),
                        'quantity' => '1',
                        'subtract' => '1',
                        'price' => 101.0,
                        'total' => 101.0,
                        'tax' => 0,
                        'reward' => 0,
                    ),
                ]
        ];
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
}