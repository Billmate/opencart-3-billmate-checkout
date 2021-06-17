<?php

namespace Billmate;

class Checkout
{
    protected $checkoutData = [];
    protected $paymentData = [];
    protected $paymentInfo = [];
    protected $articles = [];
    protected $cart = [];

    public function __construct()
    {
        $this->cart = [
            'Shipping' => [
                'withouttax' => 0,
                'taxrate'    => 0,
            ],
            'Handling' => [
                'withouttax' => 0,
                'taxrate'    => 0,
            ],
            'Total' => [
                'withouttax' => 0,
                'rounding'   => 0,
                'withtax'    => 0,
            ],
        ];
    }

    public function toJson()
    {
        return json_encode($this->build());
    }

    public function build()
    {
        return [
            'CheckoutData' => $this->checkoutData,
            'PaymentData'  => $this->paymentData,
            'PaymentInfo'  => $this->paymentInfo,
            'Articles'     => $this->articles,
            'Cart'         => $this->cart,
        ];
    }

    public function addCheckoutData($key, $value)
    {
        $this->setValue('checkoutData', $key, $value);
    }

    public function addPaymentData($key, $value)
    {
        $this->setValue('paymentData', $key, $value);
    }

    public function addPaymentInfo($key, $value)
    {
        $this->setValue('paymentInfo', $key, $value);
    }

    public function addArticle($value)
    {
        $this->articles[] = $value;
    }

    public function addCart($key, $subkey, $value)
    {
        $this->cart[$key][$subkey] = $value;
    }

    public function setValue($group, $key, $value)
    {
        $this->{$group}[$key] = $value;
    }

    public function calculateCart()
    {
        $tax = 0;
        $withtax = 0;
        $withouttax = 0;

        foreach ($this->articles as $article) {
            $tax += ($article['taxrate'] / 100) * $article['withouttax'];
            $withouttax += $article['withouttax'];
        }

        if ($this->cart['Shipping']['withouttax']) {
            $tax += ($this->cart['Shipping']['taxrate'] / 100) * $this->cart['Shipping']['withouttax'];
            $withouttax += $this->cart['Shipping']['withouttax'];
        }

        if ($this->cart['Handling']['withouttax']) {
            $tax += ($this->cart['Handling']['taxrate'] / 100) * $this->cart['Handling']['withouttax'];
            $withouttax += $this->cart['Handling']['withouttax'];
        }

        $withtax = $withouttax + $tax;

        $this->addCart('Total', 'withtax', $withtax);
        $this->addCart('Total', 'rounding', 0);
        $this->addCart('Total', 'withouttax', $withouttax);
        $this->addCart('Total', 'tax', $tax);
    }
}
