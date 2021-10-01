<?php

use Billmate\Billmate;
use Billmate\Checkout;

class ControllerCheckoutBillmateCheckout extends Controller
{
    public function index()
    {
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->database();

        $this->load->language('checkout/checkout');
        $this->load->language('checkout/billmate/checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_cart'),
            'href' => $this->url->link('checkout/cart', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('checkout/billmate/checkout', '', true)
        );

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/stylesheet/billmate/billmate-checkout.css')) {
            $this->document->addStyle('catalog/view/theme/' . $this->config->get('config_template'). '/stylesheet/billmate/billmate-checkout.css');
        } else {
            $this->document->addStyle('catalog/view/theme/default/stylesheet/billmate/billmate-checkout.css');
        }

        $this->document->addScript('catalog/view/javascript/billmate/billmate-checkout.js');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->load->model('checkout/billmate/shipping');
        $this->model_checkout_billmate_shipping->setDefaultShippingMethod();

        $data['cart'] = $this->cart();
        $data['totals'] = $this->totals();
        $data['order'] = $this->order();
        $data['checkout'] = $this->checkout($data['order']);

        $data['shipping'] = $this->cart->hasShipping();
        $data['shipping_method'] = $this->session->data['shipping_method'] ?? null;
        $data['shipping_methods'] = $this->session->data['shipping_methods'] ?? [];

        $data['comment'] = $this->session->data['comment'] ?? null;
        $data['cart_url'] = $this->url->link('checkout/cart', '', true);

        $this->response->setOutput($this->load->view('checkout/billmate/checkout', $data));
    }

    private function order()
    {
        $this->load->model('checkout/billmate/order');

        $order = $this->model_checkout_billmate_order->createOrder();

        $this->session->data['order_id'] = $order['order_id'];

        return $order;
    }

    private function checkout($order)
    {
        $this->load->model('checkout/billmate/helper');
        $this->load->model('checkout/billmate/order');
        $this->load->model('checkout/billmate/shipping');

        $billmate = new Billmate(
            $this->config->get('payment_billmate_checkout_merchant_id'),
            $this->config->get('payment_billmate_checkout_secret'),
            $this->config->get('payment_billmate_checkout_test_mode')
        );

        $checkout = new Checkout();

        $checkout->addCheckoutData('terms', $this->model_checkout_billmate_helper->getTermsUrl());
        $checkout->addCheckoutData('privacyPolicy', $this->model_checkout_billmate_helper->getPolicyUrl());
        $checkout->addCheckoutData('redirectOnSuccess', 'true');
        $checkout->addCheckoutData('companyView', $this->config->get('payment_billmate_checkout_b2b_mode') ? 'true' : 'false');

        $checkout->addPaymentData('orderid', $order['order_id']);
        $checkout->addPaymentData('currency', $this->session->data['currency']);
        $checkout->addPaymentData('language', $this->model_checkout_billmate_helper->getLanguage());
        $checkout->addPaymentData('country', $this->model_checkout_billmate_helper->getCountry());
        $checkout->addPaymentData('autoactivate', $this->config->get('payment_billmate_checkout_auto_activate') ? 'true' : 'false');
        $checkout->addPaymentData('logo', $this->config->get('payment_billmate_checkout_logo'));
        $checkout->addPaymentData('returnmethod', 'POST');
        $checkout->addPaymentData('accepturl', $this->url->link('checkout/billmate/accept', '', true));
        $checkout->addPaymentData('cancelurl', $this->url->link('checkout/billmate/checkout', '', true));
        $checkout->addPaymentData('callbackurl', $this->url->link('checkout/billmate/callback', '', true));

        $checkout->addPaymentInfo('yourreference', null);
        $checkout->addPaymentInfo('ourreference', $order['order_id']);

        $order['products'] = $this->model_checkout_order->getOrderProducts($order['order_id']);

        foreach ($order['products'] as $product) {
            $checkout->addArticle([
                'artnr'      => $product['model'],
                'title'      => $product['name'],
                'quantity'   => $product['quantity'],
                'aprice'     => $product['price'],
                'taxrate'    => $this->model_checkout_billmate_helper->getTaxRate($product['price'], $product['tax']),
                'withouttax' => $product['price'] * $product['quantity'],
                'discount'   => 0,
            ]);
        }

        $order['totals'] = $this->model_checkout_order->getOrderTotals($order['order_id']);

        $custom_totals = explode(',', $this->config->get('payment_billmate_checkout_custom_totals'));

        foreach ($order['totals'] as $total) {
            switch ($total['code']) {
                case 'credit':
                case 'klarna_fee':
                case 'sub_total':
                case 'tax':
                case 'total':
                    break;

                case 'shipping':
                    $checkout->addCart('Shipping', 'withouttax', $total['value']);
                    $checkout->addCart('Shipping', 'taxrate', $this->model_checkout_billmate_helper->getShippingTaxRate());
                    break;

                case 'coupon':
                    $checkout->addArticle([
                        'artnr'      => 'coupon',
                        'title'      => $total['title'],
                        'quantity'   => 1,
                        'aprice'     => $total['value'],
                        'taxrate'    => 25,
                        'withouttax' => $total['value'],
                        'discount'   => 0,
                    ]);
                    break;

                case 'voucher':
                    $checkout->addArticle([
                        'artnr'      => 'voucher',
                        'title'      => $total['title'],
                        'quantity'   => 1,
                        'aprice'     => $total['value'],
                        'taxrate'    => 0,
                        'withouttax' => $total['value'],
                        'discount'   => 0,
                    ]);
                    break;

                default:
                    if (in_array($total['code'], $custom_totals)) {
                         $checkout->addArticle([
                            'artnr'      => $total['code'],
                            'title'      => $total['title'],
                            'quantity'   => 1,
                            'aprice'     => $total['value'],
                            'taxrate'    => 0,
                            'withouttax' => $total['value'],
                            'discount'   => 0,
                        ]);
                    }

            }
        }

        if (!empty($this->config->get('payment_billmate_checkout_invoice_fee'))) {
            $handling_fee = $this->config->get('payment_billmate_checkout_invoice_fee');
            $tax_class_id = $this->config->get('payment_billmate_checkout_invoice_fee_tax_class_id');
            $tax_rate = $this->model_checkout_billmate_helper->getTaxRateById($tax_class_id);

            $checkout->addCart('Handling', 'withouttax', $handling_fee);
            $checkout->addCart('Handling', 'taxrate', $tax_rate);
        }

        $checkout->calculateCart();

        $response = $billmate->initCheckout($checkout);

        if (empty($response) || !empty($response['code'])) {
            http_response_code(500);
            exit($response['message'] ?? $this->language->get('error_checkout'));
        }

        if (!empty($response['url'])) {
            $this->model_checkout_billmate_order->updateCheckoutUrl($order['order_id'], $response['url']);
        }

        if (!empty($response['number'])) {
            $this->model_checkout_billmate_order->updateBillmateNumber($order['order_id'], $response['number']);
        }

        return $response;
    }

    private function cart()
    {
        foreach ($this->cart->getProducts() as $product) {
            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = '';
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
            }

            $products[] = [
                'thumb'    => $image,
                'name'     => $product['name'],
                'model'    => $product['model'],
                'quantity' => $product['quantity'],
                'price'    => $price ?? null,
                'total'    => $total ?? null,
                'href'     => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            ];
        }

        return $products ?? [];
    }

    private function totals()
    {
        // @todo Use custom model instead of duplicated code
        $this->load->model('setting/extension');

        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        $total_data = [
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        ];

        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $sort_order = [];

            $results = $this->model_setting_extension->getExtensions('total');

            $custom_totals = explode(',', $this->config->get('payment_billmate_checkout_custom_totals'));

            $valid_totals = array_merge($custom_totals, [
                'shipping', 'sub_total', 'tax', 'total',
                'credit', 'handling', 'low_order_fee',
                'coupon', 'reward', 'voucher',
            ]);

            $results = array_filter($results, function($item) use ($valid_totals) {
                return in_array($item['code'], $valid_totals);
            });

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

            $sort_order = [];

            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $totals);
        }

        $data = [];

        foreach ($totals as $total) {
            $data[] = [
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $this->session->data['currency'])
            ];
        }

        return $data;
    }

    // @todo Move to admin and models when admin is rebuilt
    private function database()
    {
        $query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME = 'billmate_checkout'");

        if (empty($query->row)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN `billmate_checkout` varchar(255) NULL COMMENT ''");
        }

        $query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME = 'billmate_number'");

        if (empty($query->row)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN `billmate_number` varchar(255) NULL COMMENT ''");
        }

        $query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . DB_PREFIX . "order' AND COLUMN_NAME = 'billmate_status'");

        if (empty($query->row)) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN `billmate_status` varchar(255) NULL COMMENT ''");
        }
    }
}
