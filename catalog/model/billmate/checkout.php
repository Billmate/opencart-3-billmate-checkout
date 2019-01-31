<?php
class ModelBillmateCheckout extends Model {

    const SESSION_HASH_CODE = 'billmate_checkout_hash';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout/request');
    }

    /**
     * @return array
     */
    public function getCheckoutData() {
        $checkoutData = [];

        $bmResponse = $this->model_billmate_checkout_request->getResponse();

        if (isset($bmResponse['url'])) {

            $hash = $this->getHashFromUrl($bmResponse['url']);
            if ($hash) {
                $this->session->data[ModelBillmateCheckout::SESSION_HASH_CODE] = $hash;
            }

            $checkoutData['iframe_url'] = $bmResponse['url'];
        }

        if (isset($bmResponse['PaymentData']['url'])) {
            $checkoutData['iframe_url'] = $bmResponse['PaymentData']['url'];
        }

        if (isset($bmResponse['message'])) {
            $checkoutData['error_message'] = $bmResponse['message'];
        }
        return $checkoutData;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getHashFromUrl($url = '')
    {
        $parts = explode('/',$url);
        $sum = count($parts);
        $hash = ($parts[$sum-1] == 'test') ? str_replace('\\','',$parts[$sum-2]) : str_replace('\\','',$parts[$sum-1]);
        return $hash;
    }
}