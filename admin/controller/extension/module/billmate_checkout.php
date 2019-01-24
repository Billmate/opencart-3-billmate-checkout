<?php
class ControllerExtensionModuleBillmateCheckout extends Controller {

    const DEFAULT_MODULE_SETTINGS = [
        'module_billmate_checkout_status' => 0,
        'module_billmate_checkout_bm_id' => '',
        'module_billmate_checkout_secret' => '',
        'module_billmate_checkout_test_mode' => 1,
        'module_billmate_checkout_order_status_id' => 15,
        'module_billmate_checkout_gdpr_link' => ''
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
        $this->config->set('module_billmate_checkout_status', $this->request->post['module_billmate_checkout_status']);
        $this->config->set('module_billmate_checkout_bm_id', $this->request->post['module_billmate_checkout_bm_id']);
        $this->config->set('module_billmate_checkout_secret', $this->request->post['module_billmate_checkout_secret']);
        $this->config->set('module_billmate_checkout_test_mode', $this->request->post['module_billmate_checkout_test_mode']);
        $this->config->set('module_billmate_checkout_order_status_id', $this->request->post['module_billmate_checkout_order_status_id']);
        $this->config->set('module_billmate_checkout_gdpr_link', $this->request->post['module_billmate_checkout_gdpr_link']);
         return true;
    }

    public function install() {

        $this->model_setting_event->addEvent(
            'billmate_checkout_page',
            'catalog/view/checkout/checkout/after',
            'event/billmatecheckout/replaceTotal'
        );

        $this->model_setting_setting->editSetting(self::MODULE_CODE, self::DEFAULT_MODULE_SETTINGS);
    }

    public function uninstall() {
        $this->model_setting_setting->deleteSetting(self::MODULE_CODE);
        $this->model_setting_event->deleteEventByCode('billmate_checkout_page');
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
        $this->templateData['module_billmate_checkout_status'] = $this->config->get('module_billmate_checkout_status');
        $this->templateData['module_billmate_checkout_bm_id'] = $this->config->get('module_billmate_checkout_bm_id');
        $this->templateData['module_billmate_checkout_secret'] = $this->config->get('module_billmate_checkout_secret');
        $this->templateData['module_billmate_checkout_test_mode'] = $this->config->get('module_billmate_checkout_test_mode');
        $this->templateData['module_billmate_checkout_order_status_id'] = $this->config->get('module_billmate_checkout_order_status_id');
        $this->templateData['module_billmate_checkout_gdpr_link'] = $this->config->get('module_billmate_checkout_gdpr_link');
        return $this;
    }

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
    protected function getTemplateData() {
        return $this->templateData;
    }
}