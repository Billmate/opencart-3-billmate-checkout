<?php
class ControllerEventBillmateheader extends Controller {

    /**
     * @var array
     */
    protected $allowedJsRoutes = [
        'checkout/checkout'
    ];

     /**
     * @var array
     */
    protected $allowedCssRoutes = [
        'checkout/checkout'
    ];

    /**
     * @var HelperBillmate
     */
    protected $helperBillmate;

    /**
     * ControllerEventBillmatecheckout constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
    }

    public function addJsCss($route, $data)
    {
        if(!$this->helperBillmate->isBmCheckoutEnabled()) {
            return;
        }

        if ($this->isAllowedToShowJs()) {
            $this->document->addScript('catalog/view/javascript/billmate/bm-checkout.js');
        }

        if ($this->isAllowedToShowCss()) {
            $this->document->addStyle('catalog/view/theme/default/stylesheet/billmate/bm-checkout.css');
        }
    }

    /**
     * @return bool
     */
    protected function isAllowedToShowJs()
    {
        $requestRoute = $this->getRequestRoute();
        return in_array($requestRoute, $this->allowedJsRoutes);
    }

    /**
     * @return bool
     */
    protected function isAllowedToShowCss()
    {
        $requestRoute = $this->getRequestRoute();
        return in_array($requestRoute, $this->allowedCssRoutes);
    }

    /**
     * @return string
     */
    protected function getRequestRoute()
    {
        if (isset($this->request->get['route'])) {
            return $this->request->get['route'];
        }
        return '';
    }
}