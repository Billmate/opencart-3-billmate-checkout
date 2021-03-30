<?php

class ModelExtensionPaymentBillmateCheckout extends Model
{
    /**
     * @param $address
     * @param $total
     *
     * @return array
     */
    public function getMethod($address, $total)
    {
        return [];
    }
}
