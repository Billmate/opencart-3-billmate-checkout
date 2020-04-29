<?php

/**
 * Class ControllerExtensionPaymentBillmateInfo
 */
class ControllerExtensionPaymentBillmateInfo extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/payment/billmate_checkout');
    }

    /**
     * @return string
     */
    public function index()
    {
        $data['warning_url_message'] = $this->language->get('warning_url_message');
        $data['url_contains_port'] = $this->isStoreUrlContainsPort();
        $data['callback_urls'] = $this->getCallbackUrls();

        return $this->load->view('extension/module/bmcheckout/info', $data);
    }

    /**
     * @return array|false|int|string|null
     */
    protected function isStoreUrlContainsPort()
    {
        $storeUrl = $this->getCatalogStoreUrl();
        $port = parse_url($storeUrl, PHP_URL_PORT);
        return !empty($port);
    }

    /**
     * @return array
     */
    protected function getCallbackUrls()
    {
        $urlProvider = $this->getUrlProvider();
        return [
            'accepturl' => $urlProvider->link(
                'billmatecheckout/accept',
                '',
                $this->request->server['HTTPS']
            ),
            'cancelurl' => $urlProvider->link(
                'billmatecheckout/cancel',
                '',
                $this->request->server['HTTPS']
            ),
            'callbackurl' => $urlProvider->link(
                'billmatecheckout/callback',
                '',
                $this->request->server['HTTPS']
            )
        ];
    }

    /**
     * @return Url
     */
    protected function getUrlProvider()
    {
        $storeUrl = $this->getCatalogStoreUrl();
        return new Url(
            $storeUrl,
            $this->config->get('config_secure')
        );
    }

    /**
     * @return string
     */
    protected function getCatalogStoreUrl()
    {
        return $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG;
    }
}