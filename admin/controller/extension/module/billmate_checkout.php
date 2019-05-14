<?php
class ControllerExtensionModuleBillmateCheckout extends Controller {

    const DEFAULT_MODULE_SETTINGS = [
        'module_billmate_checkout_status' => 0,
        'module_billmate_checkout_bm_id' => '',
        'module_billmate_checkout_secret' => '',
        'module_billmate_checkout_test_mode' => 1,
        'module_billmate_checkout_push_events' => 0,
        'module_billmate_checkout_order_status_id' => 15,
        'module_billmate_checkout_gdpr_link' => '',
        'module_billmate_checkout_privacy_policy_link' => '',
        'module_billmate_checkout_log_enabled' => 0
    ];

    const MODULE_CODE = 'module_billmate_checkout';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/module/billmate_checkout');
        $this->load->model('localisation/order_status');
        $this->load->model('setting/setting');
        $this->load->model('setting/event');
        $this->load->model('localisation/order_status');
        $this->load->model('setting/setting');
    }

    /**
     * @var array
     */
    protected $templateData = [];

    public function index() {

        $this->loadModels();
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $values = $this->request->post;
            $this->model_setting_setting->editSetting(self::MODULE_CODE, $values);
            $this->templateData['success_message'] = $this->language->get('text_change_settings_success');
        }

        $this->runEditModuleSettings();
    }

    protected function runEditModuleSettings() {
        $this->loadTemplateData()
            ->loadBaseBlocks()
            ->loadBreadcrumbs();
        $this->loadConfiguredValues();
        $this->document->setTitle($this->language->get('heading_title'));

        $templateData = $this->getTemplateData();
        $htmlOutput = $this->load->view('extension/module/bmcheckout/settings', $templateData);
        $this->response->setOutput($htmlOutput);
    }

    public function validate() {

        $this->config->set('module_billmate_checkout_bm_id', $this->request->post['module_billmate_checkout_bm_id']);
        $this->config->set('module_billmate_checkout_secret', $this->request->post['module_billmate_checkout_secret']);

        foreach($this->getOptionsNames() as $optionName ) {
            $this->config->set($optionName, $this->request->post[$optionName]);
        }

        return true;
    }

    public function install() {

        $this->model_setting_event->addEvent(
            'billmate_checkout_page',
            'catalog/view/checkout/checkout/after',
            'event/billmatecheckout/replaceTotal'
        );
        $this->model_setting_event->addEvent(
            'billmate_checkout_page_jscss',
            'catalog/controller/common/header/before',
            'event/billmateheader/addJsCss'
        );

        $this->model_setting_event->addEvent(
            'billmate_checkout_hash_validate',
            'catalog/controller/*/before',
            'event/billmatehash/validate'
        );

        $this->model_setting_setting->editSetting(self::MODULE_CODE, self::DEFAULT_MODULE_SETTINGS);
    }

    public function uninstall() {
        $this->model_setting_setting->deleteSetting(self::MODULE_CODE);
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page_jscss');
        $this->model_setting_event->deleteEventByCode('billmate_checkout_hash_validate');
    }

    /**
     * @return $this
     */
    protected function loadModels() {
        $this->load->language('extension/module/billmate_checkout');
        return $this;
    }

    /**
     * @return $this
     */
    protected function loadTemplateData() {
        $this->templateData['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->templateData['action'] = $this->url->link(
            'extension/module/billmate_checkout',
            'user_token=' . $this->session->data['user_token'],
            true
        );
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
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            )
        );

        $this->templateData['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/billmate_checkout',
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
    protected function getTemplateData() {
        return $this->templateData;
    }
}