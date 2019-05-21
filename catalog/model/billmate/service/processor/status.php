<?php
class ModelBillmateServiceProcessorStatus extends Model
{
    const BILLMATE_PAID_STATUS = 'Paid';

    const BILLMATE_CREATED_STATUS = 'Created';

    /**
     * @var Helperbm
     */
    protected $helperBillmate;

    /**
     * ModelBillmateServiceProcessorStatus constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('checkout/order');
        $this->load->model('billmate/service');
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return BillMate
     */
    public function getBMConnection()
    {
        return $this->getBillmateConfig()->getBillmateConnection();
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        $orderId = $this->request->request['order_id'];
        $orderData = $this->getOrderModel()->getOrder($orderId);
        return $orderData['payment_code'];
    }

    /**
     * @param $requestData array
     */
    public function cancelPayment($requestData)
    {
        $billmateConnection = $this->getBMConnection();
        $bmResponse = $billmateConnection->cancelPayment($requestData);

        if (isset($bmResponse['message'])) {
            $this->log->write($bmResponse['message']);
        }
    }

    /**
     * @param $requestData array
     */
    public function activatePayment($requestData)
    {
        $billmateConnection = $this->getBMConnection();
        $bmResponse = $billmateConnection->activatePayment($requestData);

        if (isset($bmResponse['message'])) {
            $this->log->write($bmResponse['message']);
        }
    }

    /**
     * @param $requestData array
     */
    public function creditPayment($requestData)
    {
        $billmateConnection = $this->getBillmateConnection();
        $bmRequestData['PaymentData']['partcredit'] = false;
        $bmResponse = $billmateConnection->creditPayment($requestData);

        if (isset($bmResponse['message'])) {
            $this->log->write($bmResponse['message']);
        }
    }

    /**
     * @param $orderId
     *
     * @return string | false
     */
    protected function getInvoiceId($orderId)
    {
        return $this->getBmService()->getInvoiceId($orderId);
    }

    /**
     * @return ModelBillmateBillmateService
     */
    protected function getBmService()
    {
        return $this->model_billmate_service;
    }

    /**
     * @param $orderId
     *
     * @return array|bool
     */
    protected function getRequestData($orderId)
    {
        $invoiceId = $this->getInvoiceId($orderId);
        if ($invoiceId) {
            return [
                'number' => $invoiceId
            ];
        }

        return false;
    }

    /**
     * @return Helperbm
     */
    private function getBillmateConfig()
    {
        return $this->helperBillmate;
    }

    /**
     * @return ModelSaleOrder
     */
    private function getOrderModel()
    {
        return $this->model_checkout_order;
    }
}