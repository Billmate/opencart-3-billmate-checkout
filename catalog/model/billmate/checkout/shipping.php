<?php
class ModelBillmateCheckoutShipping extends Model {

    protected $_defaultShipping = [
        'address_id' => 0,
        'firstname' => 'Default',
        'lastname' => 'Customer',
        'company' => '',
        'address_1' => 'Street 12',
        'address_2' => '',
        'postcode' => '12345',
        'city' => 'City',
        'zone_id' => '',
        'zone' => '',
        'zone_code' => '',
        'country_id' => '',
        'country' => 'Sweden',
        'iso_code_2' => 'SE',
        'iso_code_3' => 'SWE',
        'address_format' => '',
        'custom_field' => NULL,
    ];
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

    public function getDefaultAddress()
    {
        if (isset($this->session->data['shipping_address'])) {
            return $this->session->data['shipping_address'];
        }
        return $this->_defaultShipping;
    }
}