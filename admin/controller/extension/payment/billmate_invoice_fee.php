<?php

/**
 * Class ControllerExtensionPaymentBillmateInfo
 */
class ControllerExtensionPaymentBillmateInvoiceFee extends ControllerExtensionPaymentBillmateCheckout
{
    /**
     * ControllerExtensionPaymentBillmateInvoiceFee constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('localisation/tax_class');
    }

    /**
     * @return string
     */
    public function index()
    {
        $this->loadTaxClasses()
            ->loadConfiguredValues();
        $templateData = $this->getTemplateData();
        return $this->load->view(
            'extension/module/bmcheckout/options/invoice_fee',
            $templateData
        );
    }

    /**
     * @return $this
     */
    protected function loadTaxClasses()
    {
        $this->templateData['tax_classes'] = $this->getTaxClasses();
        return $this;
    }

    /**
     * @return array
     */
    protected function getTaxClasses()
    {
        return $this->model_localisation_tax_class->getTaxClasses();
    }
}