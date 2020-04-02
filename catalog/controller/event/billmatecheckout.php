<?php
class ControllerEventBillmatecheckout extends Controller {

    /**
     * @var DOMDocument
     */
    protected $domDocument;

    /**
     * @var string
     */
    protected $htmlContent;

    /**
     * @var array
     */
    protected $removeBlockSelectors = [
        'collapse-payment-method',
        'collapse-checkout-confirm'
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
        $this->load->model('billmate/checkout');
    }

    public function replaceTotal(&$route, &$args, &$output) {

        if (!$this->helperBillmate->isBmCheckoutEnabled()) {
            return;
        }

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
        foreach ($this->removeBlockSelectors as $blockId) {
            $removeBlock = $dom->getElementById($blockId);
            if($removeBlock) {
                $nodeRemoveBlock = $removeBlock->parentNode;
                $nodeRemoveBlock->parentNode->removeChild($nodeRemoveBlock);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function appendBMCheckout() {
        $dom = $this->getDomDocument();
        $contentBlock = $dom->getElementById('content');

        $billmateCheckoutBlock = $dom->createDocumentFragment();
        $billmateCheckoutBlock->appendXML(utf8_encode($this->getBMCheckoutContent()));

        $chapter = $contentBlock->getElementsByTagName('div')->item(0);
        $contentBlock->removeChild($chapter);

        $contentBlock->appendChild($billmateCheckoutBlock);
        
        $html = $dom->saveHTML();
        $startTrigger = "<script type=\"text/javascript\"><!--";
        $endTrigger = "//--&gt;";
        $startpos = strpos($html, $startTrigger);
        $endpos = strpos($html, $endTrigger);
        if ($startpos !== false && $endpos !== false && $endpos > $startpos) {
            $length = $endpos - $startpos + strlen($endTrigger);
            $html = substr_replace($html, "", $startpos, $length);
        }
        $dom->loadHTML($html);

        return $this;
    }

    /**
     * @return string
     */
    protected function getBMCheckoutContent()
    {
        $data = $this->getBillmateCheckoutModel()->getCheckoutData();
        return $this->load->view('billmate/checkout', $data);
    }

    /**
     * @return ModelBillmateCheckout
     */
    protected function getBillmateCheckoutModel()
    {
        return $this->model_billmate_checkout;
    }
}