<?php
class ControllerExtensionPaymentBillmateCheckout extends Controller
{
    private $error = [];

    public function index()
    {
        $this->load->model('setting/setting');

        $this->load->language('extension/payment/billmate_checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_billmate_checkout', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        // Make upgrade a bit easier...
        if (!$this->config->get('payment_billmate_checkout_merchant_id') && $this->config->get('payment_billmate_checkout_bm_id')) {
            $this->config->set('payment_billmate_checkout_merchant_id', $this->config->get('payment_billmate_checkout_bm_id'));
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/billmate_checkout', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/payment/billmate_checkout', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL');

        $fields = [
            'merchant_id'              => '',
            'secret'                   => '',
            'terms_id'                 => null,
            'policy_id'                => null,
            'default_status_id'        => 5,
            'denied_status_id'         => 8,
            'auto_activate'            => false,
            'logo'                     => '',
            'invoice_fee'              => 0,
            'invoice_fee_tax_class_id' => 0,
            'b2b_mode'                 => false,
            'test_mode'                => true,
            'debug_mode'               => true,
            'success_page'             => true,
            'custom_totals'            => false,
            'status'                   => true,
        ];

        foreach ($fields as $field => $default) {
            if (isset($this->request->post['payment_billmate_checkout_' . $field])) {
                $data['payment_billmate_checkout_' . $field] = $this->request->post['payment_billmate_checkout_' . $field];
            } elseif ($this->config->has('payment_billmate_checkout_' . $field)) {
                $data['payment_billmate_checkout_' . $field] = $this->config->get('payment_billmate_checkout_' . $field);
            } else {
                $data['payment_billmate_checkout_' . $field] = $default;
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['error_payment_billmate_checkout_merchant_id'])) {
            $data['error_merchant_id'] = $this->error['error_payment_billmate_checkout_merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }

        if (isset($this->error['error_payment_billmate_checkout_secret'])) {
            $data['error_secret'] = $this->error['error_payment_billmate_checkout_secret'];
        } else {
            $data['error_secret'] = '';
        }

        $this->load->model('localisation/order_status');
        $this->load->model('localisation/tax_class');
        $this->load->model('catalog/information');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['tax_classes'] =  $this->model_localisation_tax_class->getTaxClasses();
        $data['information_pages'] =  $this->model_catalog_information->getInformations();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/billmate_checkout', $data));
    }

    public function install() {
        if ($this->user->hasPermission('modify', 'marketplace/extension')) {
            $this->load->model('extension/payment/billmate_checkout');

            $this->model_extension_payment_billmate_checkout->install();
        }
    }

    public function uninstall() {
        if ($this->user->hasPermission('modify', 'marketplace/extension')) {
            $this->load->model('extension/payment/billmate_checkout');

            $this->model_extension_payment_billmate_checkout->uninstall();
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/billmate_checkout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_billmate_checkout_merchant_id']) {
            $this->error['error_payment_billmate_checkout_merchant_id'] = $this->language->get('error_merchant_id');
        }

        if (!$this->request->post['payment_billmate_checkout_secret']) {
            $this->error['error_payment_billmate_checkout_secret'] = $this->language->get('error_secret');
        }

        return !$this->error;
    }
}
