<?php

use Billmate\Encoding;

class ModelCheckoutBillmateOrder extends Model
{
    public function getOrder($order_id, $strict = false)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "' LIMIT 1");

        if ($strict && !empty($query->row['order_status_id'])) {
            return null;
        }

        return $query->row;
    }

    public function createOrder()
    {
        $this->load->model('checkout/order');

        $order_id = $this->model_checkout_order->addOrder($this->buildOrder());

        return $this->model_checkout_order->getOrder($order_id);
    }

    public function updateOrder($order_id, $payment_data)
    {
        $this->load->model('checkout/billmate/country');

        $order_data = $this->getOrder($order_id);

        if (!empty($payment_data['Customer'])) {
            $shipping_address = !empty($payment_data['Customer']['Shipping'])
                ? $payment_data['Customer']['Shipping']
                : $payment_data['Customer']['Billing'];

            array_walk($shipping_address, function (&$value) {
                $value = Encoding::fixUTF8($value);
            });

            $order_data['shipping_firstname']  = $shipping_address['firstname'];
            $order_data['shipping_lastname']   = $shipping_address['lastname'];
            $order_data['shipping_company']    = !empty($shipping_address['company']) ? $shipping_address['company'] : null;
            $order_data['shipping_address_1']  = $shipping_address['street'];
            $order_data['shipping_address_2']  = !empty($shipping_address['street2']) ? $shipping_address['street2'] : null;
            $order_data['shipping_city']       = $shipping_address['city'];
            $order_data['shipping_postcode']   = $shipping_address['zip'];
            $order_data['shipping_country']    = $shipping_address['country'];
            $order_data['shipping_country_id'] = $this->model_checkout_billmate_country->getCountryIdByCode(
                $shipping_address['country']
            );

            $payment_address = $payment_data['Customer']['Billing'];

            array_walk($payment_address, function (&$value) {
                $value = Encoding::fixUTF8($value);
            });

            $order_data['payment_firstname']  = $payment_address['firstname'];
            $order_data['payment_lastname']   = $payment_address['lastname'];
            $order_data['payment_company']    = !empty($payment_address['company']) ? $payment_address['company'] : null;
            $order_data['payment_address_1']  = $payment_address['street'];
            $order_data['payment_address_2']  = !empty($payment_address['street2']) ? $payment_address['street2'] : null;
            $order_data['payment_city']       = $payment_address['city'];
            $order_data['payment_postcode']   = $payment_address['zip'];
            $order_data['payment_country']    = $payment_address['country'];
            $order_data['payment_country_id'] = $this->model_checkout_billmate_country->getCountryIdByCode(
                $payment_address['country']
            );

            if (!empty($payment_address['firstname'])) {
                $order_data['firstname'] = $payment_address['firstname'];
            }

            if (!empty($payment_address['lastname'])) {
                $order_data['lastname'] = $payment_address['lastname'];
            }

            if (!empty($payment_data['Customer']['Billing']['email'])) {
                $order_data['email'] = $payment_data['Customer']['Billing']['email'];
            }

            if (!empty($payment_data['Customer']['Billing']['phone'])) {
                $order_data['telephone'] = $payment_data['Customer']['Billing']['phone'];
            }
        }

        if (!empty($payment_data['PaymentData'])) {
            $billmate_method = (int)$payment_data['PaymentData']['method'];

            switch ($billmate_method) {
                case 1:
                    $payment_method = 'Billmate Checkout - Invoice';
                    break;

                case 2:
                    $payment_method = 'Billmate Checkout - Invoice Service';
                    break;

                case 4:
                    $payment_method = 'Billmate Checkout - Part Payment';
                    break;

                case 8:
                    $payment_method = 'Billmate Checkout - Card';
                    break;

                case 16:
                    $payment_method = 'Billmate Checkout - Bank';
                    break;

                case 1024:
                    $payment_method = 'Billmate Checkout - Swish';
                    break;

                default:
                    $payment_method = 'Billmate Checkout';
                    break;

            }

            $order_data['payment_method'] = $payment_method;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET
            firstname = '" . $this->db->escape($order_data['firstname']) . "',
            lastname = '" . $this->db->escape($order_data['lastname']) . "',
            email = '" . $this->db->escape($order_data['email']) . "',
            telephone = '" . $this->db->escape($order_data['telephone']) . "',
            payment_firstname = '" . $this->db->escape($order_data['payment_firstname']) . "',
            payment_lastname = '" . $this->db->escape($order_data['payment_lastname']) . "',
            payment_company = '" . $this->db->escape($order_data['payment_company']) . "',
            payment_address_1 = '" . $this->db->escape($order_data['payment_address_1']) . "',
            payment_address_2 = '" . $this->db->escape($order_data['payment_address_2']) . "',
            payment_city = '" . $this->db->escape($order_data['payment_city']) . "',
            payment_postcode = '" . $this->db->escape($order_data['payment_postcode']) . "',
            payment_country = '" . $this->db->escape($order_data['payment_country']) . "',
            payment_country_id = '" . (int)$order_data['payment_country_id'] . "',
            payment_method = '" . $this->db->escape($order_data['payment_method']) . "',
            shipping_firstname = '" . $this->db->escape($order_data['shipping_firstname']) . "',
            shipping_lastname = '" . $this->db->escape($order_data['shipping_lastname']) . "',
            shipping_company = '" . $this->db->escape($order_data['shipping_company']) . "',
            shipping_address_1 = '" . $this->db->escape($order_data['shipping_address_1']) . "',
            shipping_address_2 = '" . $this->db->escape($order_data['shipping_address_2']) . "',
            shipping_city = '" . $this->db->escape($order_data['shipping_city']) . "',
            shipping_postcode = '" . $this->db->escape($order_data['shipping_postcode']) . "',
            shipping_country = '" . $this->db->escape($order_data['shipping_country']) . "',
            shipping_country_id = '" . (int)$order_data['shipping_country_id'] . "',
            date_modified = NOW()
            WHERE order_id = '" . (int)$order_id . "'
        ");

        if (!empty($payment_data['Cart'])) {
            $invoice_fee = ($payment_data['Cart']['Handling']['withouttax'] ?? 0) / 100;
            $invoice_tax_rate = $payment_data['Cart']['Handling']['taxrate'] ?? 0;

            if (!empty($invoice_fee)) {
                $this->addInvoiceFee($order_id, $invoice_fee, $invoice_tax_rate);
            }

            // @todo Check if total is equal to order
            $total = intval($payment_data['Cart']['Total']['withtax']) / 100;
        }

        return true;
    }

    public function addInvoiceFee($order_id, $fee, $tax_rate)
    {
        $this->load->language('checkout/billmate/checkout');

        $totals = $this->model_checkout_order->getOrderTotals($order_id);

        // @todo Convert to order total extension
        $sort_order = 3;

        if (array_search('billmate_fee', array_column($totals, 'code')) !== false) {
            return;
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

        foreach ($totals as $total) {
            switch ($total['code']) {
                case 'tax':
                    $sort_order = $total['sort_order'] - 1;
                    $total['value'] += ($tax_rate / 100) * $fee;

                    $this->insertOrderTotal($order_id, $total['code'], $total['title'], $total['value'], $total['sort_order']);
                    break;

                case 'total':
                    $total['value'] += (($tax_rate / 100) + 1) * $fee;

                    $this->insertOrderTotal($order_id, $total['code'], $total['title'], $total['value'], $total['sort_order']);
                    $this->updateOrderTotal($order_id, $total['value']);
                    break;

                default:
                    $this->insertOrderTotal($order_id, $total['code'], $total['title'], $total['value'], $total['sort_order']);
                    break;
            }
        }

        // @todo Convert to order total extension
        $this->insertOrderTotal($order_id, 'billmate_fee', $this->language->get('text_invoice_fee'), $fee, $sort_order);
    }

    public function getProducts()
    {
        $products = [];

        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id'       => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id'               => $option['option_id'],
                    'option_value_id'         => $option['option_value_id'],
                    'name'                    => $option['name'],
                    'value'                   => $option['value'],
                    'type'                    => $option['type']
                );
            }

            $products[] = array(
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'option'     => $option_data,
                'download'   => $product['download'],
                'quantity'   => $product['quantity'],
                'subtract'   => $product['subtract'],
                'price'      => $product['price'],
                'total'      => $product['total'],
                'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward'     => $product['reward']
            );
        }

        return $products;
    }

    public function getVouchers()
    {
        $vouchers = [];

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $vouchers[] = array(
                    'description'      => $voucher['description'],
                    'code'             => token(10),
                    'to_name'          => $voucher['to_name'],
                    'to_email'         => $voucher['to_email'],
                    'from_name'        => $voucher['from_name'],
                    'from_email'       => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message'          => $voucher['message'],
                    'amount'           => $voucher['amount']
                );
            }
        }

        return $vouchers;
    }

    public function getShippingTitle()
    {
        return !empty($this->session->data['shipping_method']['title'])
            ? $this->session->data['shipping_method']['title']
            : null;
    }

    public function getShippingCode()
    {
        return !empty($this->session->data['shipping_method']['code'])
            ? $this->session->data['shipping_method']['code']
            : null;
    }

    public function getAffiliateId()
    {
        $this->load->model('affiliate/affiliate');

        $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);

        return ($affiliate_info) ? $affiliate_info['affiliate_id'] : 0;
    }

    public function getMarketingId()
    {
        $this->load->model('checkout/marketing');

        $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);

        return ($marketing_info) ? $marketing_info['marketing_id'] : 0;
    }

    public function getForwardedIp()
    {
        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            return $this->request->server['HTTP_CLIENT_IP'];
        }

        return null;
    }

    public function getCommission()
    {
        $this->load->model('affiliate/affiliate');

        $subtotal = $this->cart->getSubTotal();

        $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);

        if (!$affiliate_info) {
            return 0;
        }

        return (($subtotal / 100) * $affiliate_info['commission']);
    }

    public function getUserAgent()
    {
        return !empty($this->request->server['HTTP_USER_AGENT'])
            ? $this->request->server['HTTP_USER_AGENT']
            : null;
    }

    public function getAcceptLanguage()
    {
        return !empty($this->request->server['HTTP_ACCEPT_LANGUAGE'])
            ? $this->request->server['HTTP_ACCEPT_LANGUAGE']
            : null;
    }

    public function getTotals()
    {
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = array();

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);

        return array($totals, $taxes, $total);
    }

    public function getLoggedCustomerInfo()
    {
        $this->load->model('account/customer');

        $customer = $this->model_account_customer->getCustomer($this->customer->getId());

        return [
            'customer_id'       => $this->customer->getId(),
            'customer_group_id' => $customer['customer_group_id'],
            'firstname'         => $customer['firstname'],
            'lastname'          => $customer['lastname'],
            'email'             => $customer['email'],
            'telephone'         => $customer['telephone'],
            'custom_field'      => json_decode($customer['custom_field'], true),
        ];
    }

    public function insertOrderTotal($order_id, $code, $title, $value, $sort_order)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($code) . "', title = '" . $this->db->escape($title) . "', `value` = '" . (float)$value . "', sort_order = '" . (int)$sort_order . "'");
    }

    public function updateCheckoutUrl($order_id, $billmate_checkout_url)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET billmate_checkout = '" . $this->db->escape($billmate_checkout_url) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updateBillmateNumber($order_id, $billmate_number)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET billmate_number = '" . $this->db->escape($billmate_number) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updateBillmateStatus($order_id, $billmate_status)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET billmate_status = '" . $this->db->escape($billmate_status) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updateOrderComment($order_id, $comment)
    {
        $comment = strip_tags($comment);

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET comment = '" . $this->db->escape($comment) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updateOrderTotal($order_id, $total)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET total = '" . $this->db->escape($total) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updateOrderStatus($order_id, $order_status_id)
    {
        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
    }

    private function buildOrder()
    {
        $order_data = [
            'invoice_no'              => null,
            'invoice_prefix'          => $this->config->get('config_invoice_prefix'),
            'store_id'                => $this->config->get('config_store_id'),
            'store_name'              => $this->config->get('config_name'),
            'store_url'               => $this->config->get('config_url'),
            'customer_id'             => 0,
            'customer_group_id'       => 0,
            'firstname'               => null,
            'lastname'                => null,
            'email'                   => null,
            'telephone'               => null,
            'fax'                     => null,
            'custom_field'            => null,
            'payment_firstname'       => null,
            'payment_lastname'        => null,
            'payment_company'         => null,
            'payment_address_1'       => null,
            'payment_address_2'       => null,
            'payment_city'            => null,
            'payment_postcode'        => null,
            'payment_country'         => null,
            'payment_country_id'      => 0,
            'payment_zone'            => null,
            'payment_zone_id'         => 0,
            'payment_address_format'  => null,
            'payment_custom_field'    => null,
            'payment_method'          => 'Billmate Checkout',
            'payment_code'            => 'billmate_checkout',
            'shipping_firstname'      => null,
            'shipping_lastname'       => null,
            'shipping_company'        => null,
            'shipping_address_1'      => null,
            'shipping_address_2'      => null,
            'shipping_city'           => null,
            'shipping_postcode'       => null,
            'shipping_country'        => null,
            'shipping_country_id'     => 0,
            'shipping_zone'           => null,
            'shipping_zone_id'        => 0,
            'shipping_address_format' => null,
            'shipping_custom_field'   => null,
            'shipping_method'         => null,
            'shipping_code'           => null,
            'comment'                 => !empty($this->session->data['comment']) ? $this->session->data['comment'] : null,
            'total'                   => null,
            'order_status_id'         => 0,
            'affiliate_id'            => 0,
            'commission'              => 0,
            'marketing_id'            => 0,
            'tracking'                => null,
            'language_id'             => $this->config->get('config_language_id'),
            'currency_id'             => $this->currency->getId($this->session->data['currency']),
            'currency_code'           => $this->session->data['currency'],
            'currency_value'          => $this->currency->getValue($this->session->data['currency']),
            'ip'                      => $this->request->server['REMOTE_ADDR'],
            'forwarded_ip'            => $this->getForwardedIp(),
            'user_agent'              => $this->getUserAgent(),
            'accept_language'         => $this->getAcceptLanguage(),
            'products'                => [],
            'totals'                  => [],
            'vouchers'                => [],
        ];

        if ($this->cart->hasShipping()) {
            $order_data['shipping_method'] = $this->getShippingTitle();
            $order_data['shipping_code']   = $this->getShippingCode();
        }

        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');

            $customer = $this->model_account_customer->getCustomer($this->customer->getId());

            $order_data['customer_id']       = $this->customer->getId();
            $order_data['customer_group_id'] = $customer['customer_group_id'];
            $order_data['firstname']         = $customer['firstname'];
            $order_data['lastname']          = $customer['lastname'];
            $order_data['email']             = $customer['email'];
            $order_data['telephone']         = $customer['telephone'];
            $order_data['custom_field']      = json_decode($customer['custom_field'], true);
        }

        if (!empty($this->request->cookie['tracking'])) {
            $order_data['affiliate_id'] = $this->getAffiliateId();
            $order_data['commission']   = $this->getCommission();
            $order_data['marketing_id'] = $this->getMarketingId();
            $order_data['tracking']     = $this->request->cookie['tracking'];
        }

        list($totals, $taxes, $total) = $this->getTotals();

        $order_data['total'] = $total;
        $order_data['totals'] = $totals;

        $order_data['products'] = $this->getProducts();
        $order_data['vouchers'] = $this->getVouchers();

        return $order_data;
    }
}
