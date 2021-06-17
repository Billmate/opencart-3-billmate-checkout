<?php

class ModelCheckoutBillmateHelper extends Model
{
    public function getTermsUrl()
    {
        // @todo: Fix when admin is rebuilt
        return $this->config->get('payment_billmate_checkout_gdpr_link');

        return $this->url->link('information/information', 'information_id=' . $this->config->get('billmate_checkout_terms_id'));
    }

    public function getPolicyUrl()
    {
        // @todo: Fix when admin is rebuilt
        return $this->config->get('payment_billmate_checkout_gdpr_link');

        return $this->url->link('information/information', 'information_id=' . $this->config->get('billmate_checkout_policy_id'));
    }

    public function getCurrency()
    {
        return $this->session->data['currency'] ?? 'SEK';
    }

    public function getLanguage()
    {
        switch ($this->session->data['language']) {
            case 'da-dk':
                return 'da';

            case 'en-au':
            case 'en-ca':
            case 'en-gb':
            case 'en-ie':
            case 'en-us':
                return 'en';

            case 'sv-se':
                return 'sv';

            case 'nb-no':
                return 'no';

            default:
                return 'sv';
        }
    }

    public function getCountry()
    {
        switch ($this->session->data['language']) {
            case 'da-dk':
                return 'DK';

            case 'en-au':
            case 'en-ca':
            case 'en-gb':
            case 'en-ie':
            case 'en-us':
                return 'GB';

            case 'sv-se':
                return 'SE';

            case 'nb-no':
                return 'NO';

            default:
                return 'SE';
        }
    }

    public function getOrderStatus($status)
    {
        switch ($status) {
            case 'Paid':
                return $this->config->get('payment_billmate_checkout_activate_status_id');

            case 'Cancelled':
                return $this->config->get('payment_billmate_checkout_cancel_status_id');

            case 'Created':
                return $this->config->get('payment_billmate_checkout_order_status_id');

            default:
                return $this->config->get('payment_billmate_checkout_order_status_id');
        }
    }

    public function getTaxRate($price, $tax)
    {
        $value = $tax / $price;

        return $value * 100;
    }

    public function getTaxRateById($tax_class_id)
    {
        $value = $this->tax->getTax(100, $tax_class_id);

        return $value;
    }

    public function getShippingTaxRate()
    {
        $method = $this->session->data['shipping_method'];

        if (empty($method['tax_class_id'])) {
            return 0;
        }

        return $this->getTaxRateById($method['tax_class_id']);
    }
}
