<?php
ini_set('display_errors', true);
class ControllerBillmatecheckoutCallback extends Controller {

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * ControllerBillmatecheckoutAccept constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('checkout/order');
        $this->helperBillmate  = new Helperbm($registry);
    }

    public function index()
    {
        $responseMessage = 'OK';
        $requestData = $this->getRequestData();
        if ( isset($requestData['data']['number'])) {
            $paymentInfo = $this->helperBillmate
                ->getBillmateConnection()
                ->getPaymentinfo( [
                    'number' => $requestData['data']['number']
                ]);

            $paymentMethod = $this->helperBillmate->getPaymentMethodByCode($paymentInfo['PaymentData']['method']);
            $order_data =  array (
                'totals' =>
                    array (
                        0 =>
                            array (
                                'code' => 'sub_total',
                                'title' => 'Sub-Total',
                                'value' => 101.0,
                                'sort_order' => '1',
                            ),
                        1 =>
                            array (
                                'code' => 'shipping',
                                'title' => 'Flat Shipping Rate',
                                'value' => '5.00',
                                'sort_order' => '3',
                            ),
                        2 =>
                            array (
                                'code' => 'total',
                                'title' => 'Total',
                                'value' => 106.0,
                                'sort_order' => '9',
                            ),
                    ),
                'invoice_prefix' => 'INV-2019-00',
                'store_id' => 0,
                'store_name' => 'Your Store',
                'store_url' => 'http://opencartbm.loc/upload/',
                'customer_id' => '1',
                'customer_group_id' => '1',
                'firstname' => $paymentInfo['Customer']['Billing']['firstname'],
                'lastname' => $paymentInfo['Customer']['Billing']['lastname'],
                'email' => $paymentInfo['Customer']['Billing']['email'],
                'telephone' => $paymentInfo['Customer']['Billing']['phone'],
                'custom_field' => NULL,
                'payment_firstname' => $paymentInfo['Customer']['Billing']['firstname'],
                'payment_lastname' => $paymentInfo['Customer']['Billing']['lastname'],
                'payment_company' => '',
                'payment_address_1' => $paymentInfo['Customer']['Billing']['street'],
                'payment_address_2' => '',
                'payment_city' => $paymentInfo['Customer']['Billing']['city'],
                'payment_postcode' => $paymentInfo['Customer']['Billing']['zip'],
                'payment_zone' => '',
                'payment_zone_id' => '',
                'payment_country' => '',
                'payment_country_id' => '',
                'payment_address_format' => '',
                'payment_custom_field' =>
                    array (
                    ),
                'payment_method' => 'Billmate checkout - ' . $paymentMethod,
                'payment_code' => 'bmch',
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
                'shipping_custom_field' =>
                    array (
                    ),
                'shipping_method' => 'Flat Shipping Rate',
                'shipping_code' => 'flat.flat',
                'products' =>
                    array (
                        0 =>
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
                    ),
                'vouchers' =>
                    array (
                    ),
                'comment' => '',
                'total' => 106.0,
                'affiliate_id' => 0,
                'commission' => 0,
                'marketing_id' => 0,
                'tracking' => '',
                'language_id' => '1',
                'currency_id' => '4',
                'currency_code' => 'SEK',
                'currency_value' => '0.10000000',
                'ip' => '127.0.0.1',
                'forwarded_ip' => '',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
                'accept_language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            );


            $orderId = $this->model_checkout_order->addOrder($order_data);
            $this->model_checkout_order->addOrderHistory($orderId, $this->helperBillmate->getNewOrderStatusId());
            $this->helperBillmate->unsetCart();

            //$this->response->redirect($this->url->link('checkout/success'));
        }


        $this->response->setOutput($responseMessage);
    }

    protected function getRequestData()
    {
        if (isset($this->request->request['data'])) {
            $postData['data'] = json_decode($this->request->request['data'], true);
            $postData['credentials'] = json_decode($this->request->request['credentials'], true);
            return $postData;
        }

        $jsonBodyRequest = file_get_contents('php://input');
        if ($jsonBodyRequest) {
            return json_decode($jsonBodyRequest, true);
        }

        $testRequest = '{
            "credentials": {
                "hash": "27488bcdb10d0530ccae687c81bd9f69e9a96741e76096d9050b5884902ec2aca17986d5dc0641c3cde756afe215ebd7b31af2729b6d2664379f3c45e785ec3c"
            },
            "data": {
                "number": 550576,
                "status": "Created",
                "orderid": "2-1549018807",
                "url": "https:\/\/api.billmate.se\/invoice\/17338\/2019020195a6912ffd0b437f4fc794e1233be51c"
            }
        }';

        return $postData = json_decode($testRequest, true);
    }
}