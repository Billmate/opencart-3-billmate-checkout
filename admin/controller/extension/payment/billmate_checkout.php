<?php
class ControllerExtensionPaymentBillmateCheckout extends Controller {

    const REQUEST_METHOD = 'POST';

    const DEFAULT_MODULE_SETTINGS = [
        'payment_billmate_checkout_status' => 0,
        'payment_billmate_checkout_bm_id' => '',
        'payment_billmate_checkout_secret' => '',
        'payment_billmate_checkout_test_mode' => 1,
        'payment_billmate_checkout_invoice_message' => 1,
        'payment_billmate_checkout_push_events' => 0,
        'payment_billmate_checkout_activate_status_id' => 2,
    /*    'payment_billmate_checkout_cancel_status_id' => 7,
        'payment_billmate_checkout_credit_status_id' => 11,*/
        'payment_billmate_checkout_order_status_id' => 15,
        'payment_billmate_checkout_gdpr_link' => '',
        'payment_billmate_checkout_privacy_policy_link' => '',
        'payment_billmate_checkout_log_enabled' => 0,
        'payment_billmate_checkout_invoice_fee' => 0,
        'payment_billmate_checkout_inv_fee_tax' => 0,
    ];

    const MODULE_CODE = 'payment_billmate_checkout';

    /**
     * @var ModelBillmateConfigValidator
     */
    protected $configValidator;

    /**
     * ControllerExtensionModuleBillmateCheckout constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/payment/billmate_checkout');
        $this->load->model('localisation/order_status');
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');
        $this->load->model('setting/setting');
        $this->load->model('billmate/config/validator');
        $this->load->model('billmate/payment/bmsetup');
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @var array
     */
    protected $templateData = [];

    public function index()
    {
        if ($this->request->server['REQUEST_METHOD'] == self::REQUEST_METHOD) {
            if ($this->isValidData()) {
                $this->saveRequestedOptions();
                $values = $this->request->post;
                $this->model_setting_setting->editSetting(self::MODULE_CODE, $values);
                $this->templateData['success_message'] = $this->language->get('text_change_settings_success');
            } else {
                $this->config->set('payment_billmate_checkout_status', 0);
            }
        }

        $this->document->addScript('view/javascript/bm-options.js');
        $this->document->addStyle('view/stylesheet/billmatecheckout.css');
        $this->runEditModuleSettings();
    }

    protected function runEditModuleSettings()
    {
        $this->loadTemplateData()
            ->loadBaseBlocks()
            ->loadBreadcrumbs();
        $this->loadConfiguredValues();
        $this->document->setTitle($this->language->get('heading_title'));

        $templateData = $this->getTemplateData();
        $htmlOutput = $this->load->view('extension/module/bmcheckout/settings', $templateData);
        $this->response->setOutput($htmlOutput);
    }

    /**
     * @return bool
     */
    protected function isValidData()
    {
        $this->config->set('payment_billmate_checkout_bm_id', $this->request->post['payment_billmate_checkout_bm_id']);
        $this->config->set('payment_billmate_checkout_secret', $this->request->post['payment_billmate_checkout_secret']);

        $isValid = $this->getConfigValidator()->isConnectionValid();
        if (!$isValid) {
            $this->templateData['error_message'] = $this->language->get('error_billmate_connection');
            return false;
        }

        return true;
    }

    protected function saveRequestedOptions()
    {
        foreach($this->getOptionsNames() as $optionName ) {
            $this->config->set($optionName, $this->request->post[$optionName]);
        }
    }

    public function install()
    {
        $this->getBMPaymentSetup()->registerEvents();
        $this->model_setting_setting->editSetting(self::MODULE_CODE, self::DEFAULT_MODULE_SETTINGS);
    }

    public function uninstall()
    {
        $this->getBMPaymentSetup()->unregisterEvents();
        $this->model_setting_setting->deleteSetting(self::MODULE_CODE);
    }

    /**
     * @return $this
     */
    protected function loadTemplateData() {
        $this->templateData['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->templateData['action'] = $this->url->link(
            'extension/payment/billmate_checkout',
            'user_token=' . $this->session->data['user_token'],
            true
        );
        $this->templateData['invoice_fee_block'] = $this->load->controller('extension/payment/billmate_invoice_fee');
        $this->templateData['info_block'] = $this->load->controller('extension/payment/billmate_info');
        return $this;
    }

    /**
     * @return $this
     */
    protected function loadBaseBlocks()
    {
        $this->templateData['header'] = $this->load->controller('common/header');
        $this->templateData['column_left'] = $this->load->controller('common/column_left');
        $this->templateData['footer'] = $this->load->controller('common/footer');
        return $this;
    }

    /**
     * @return $this
     */
    protected function loadConfiguredValues() {
        foreach($this->getOptionsNames() as $optionName ) {
            $this->templateData[$optionName] = $this->config->get($optionName);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function loadBreadcrumbs() {

        $this->templateData['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/dashboard',
                'user_token=' . $this->session->data['user_token'],
                true
            )
        );

        $this->templateData['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=payment',
                true
            )
        );

        $this->templateData['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/payment/billmate_checkout',
                'user_token=' . $this->session->data['user_token'],
                true
            )
        );

        return $this;
    }

    /**
     * @return array
     */
    protected function getOptionsNames()
    {
        return array_keys(self::DEFAULT_MODULE_SETTINGS);
    }

    /**
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * @return ModelBillmateConfigValidator
     */
    protected function getConfigValidator()
    {
        if (is_null($this->configValidator)) {
            $this->configValidator = $this->model_billmate_config_validator;
        }
        return $this->configValidator;
    }

    /**
     * @return ModelBillmatePaymentBmsetup
     */
    protected function getBMPaymentSetup()
    {
        return $this->model_billmate_payment_bmsetup;
    }
}