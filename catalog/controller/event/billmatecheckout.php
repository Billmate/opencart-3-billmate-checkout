<?php
ini_set('display_errors', true);
class ControllerEventBillmatecheckout extends Controller {

    /**
     * @var DOMDocument
     */
    protected $domDocument;

    /**
     * @var ModelBillmateCheckout
     */
    protected $model_billmate_checkout;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/checkout');
    }

    public function replaceTotal(&$route, &$args, &$output) {
        $this->htmlContent = $output;
        $this->removePaymentConfirm();
        $this->appendBMCheckout();

        $output = $this->getDomDocument()->saveHTML();
    }

    /**
     * @return DOMDocument
     */
    protected function getDomDocument() {
        if (is_null($this->domDocument)) {
            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($this->htmlContent);
            libxml_clear_errors();
            $this->domDocument = $dom;
        }

        return $this->domDocument;
    }

    /**
     * @return $this
     */
    protected function removePaymentConfirm() {
        $dom = $this->getDomDocument();
        $paymentMethods = $dom->getElementById('collapse-payment-method');
        $nodePaymentMethods = $paymentMethods->parentNode;
        $nodePaymentMethods->parentNode->removeChild($nodePaymentMethods);
        $confirmBlock = $dom->getElementById('collapse-checkout-confirm');
        $nodeConfirm = $confirmBlock->parentNode;
        $nodeConfirm->parentNode->removeChild($nodeConfirm);
        return $this;
    }

    /**
     * @return $this
     */
    protected function appendBMCheckout() {
        $dom = $this->getDomDocument();
        $contentBlock = $dom->getElementById('content');
        $billmateCheckoutBlock = $dom->createDocumentFragment();
        $billmateCheckoutBlock->appendXML($this->getBMcheckoutContent());
        $contentBlock->appendChild($billmateCheckoutBlock);
        return $this;
    }

    protected function getBMcheckoutContent() {
        $data = $this->model_billmate_checkout->getCheckoutData();
        return $this->load->view('billmate/checkout', $data);
    }
}