<?php
class ModelBillmateCheckout extends Model {

    public function __construct($registry)
    {
        parent::__construct($registry);

    }

    /**
     * @return array
     */
    public function getCheckoutData() {
        return [
            'iframe_url' => 'https://checkout.billmate.se/17338/201901230c4ee66d1eedbe05c8f9b2eb95c8a41f/test'
        ];
    }
}