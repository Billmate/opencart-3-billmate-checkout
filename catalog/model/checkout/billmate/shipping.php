<?php

class ModelCheckoutBillmateShipping extends Model
{
    public function getShippingMethods()
    {
        $this->load->model('setting/extension');

        $extensions = $this->model_setting_extension->getExtensions('shipping');

        $address = $this->getStoreAddress();

        foreach ($extensions as $extension) {
            if (!$this->config->get('shipping_' . $extension['code'] . '_status')) {
                continue;
            }

            $this->load->model('extension/shipping/' . $extension['code']);

            if (!$quote = $this->{'model_extension_shipping_' . $extension['code']}->getQuote($address)) {
                continue;
            }

            $methods[$extension['code']] = [
                'code'       => $quote['code'] ?? null,
                'title'      => $quote['title'],
                'quote'      => $quote['quote'],
                'sort_order' => $quote['sort_order'],
                'error'      => $quote['error']
            ];
        }

        if (empty($methods)) {
            return null;
        }

        foreach ($methods as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $methods);

        return $methods;
    }

    public function setDefaultShippingMethod()
    {
        $this->session->data['shipping_methods'] = $this->getShippingMethods();

        if (!empty($this->session->data['shipping_methods']) && empty($this->session->data['shipping_method'])) {
            $method = current($this->session->data['shipping_methods']);
            $this->session->data['shipping_method'] = current($method['quote']);
        }

        return true;
    }

    private function getStoreAddress()
    {
        return [
            'address_1'  => $this->config->get('config_address'),
            'address_2'  => '',
            'postcode'   => '',
            'city'       => '',
            'zone_id'    => $this->config->get('config_zone_id'),
            'zone'       => '',
            'zone_code'  => '',
            'country_id' => $this->config->get('config_country_id'),
            'country'    => '',
            'iso_code_2' => '',
            'iso_code_3' => '',
        ];
    }
}
