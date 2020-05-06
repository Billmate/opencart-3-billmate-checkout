<?php

/**
 * Class ModelBillmateStateModifier
 */
class ModelBillmateStateModifier extends Model
{
    /**
     * @var Helperbm
     */
    protected $helperBillmate;

    /**
     * ModelBillmateStateModifier constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('billmate/service/factory');
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @param $orderId
     * @param $newStatusId
     */
    public function updateBmService($orderId, $newStatusId)
    {
        if ($this->isStatusAllowedToProcess($newStatusId)) {
            $requestModel = $this->createServiceRequestModel($newStatusId);
            $requestModel->process($orderId);
        }
    }

    /**
     * @param $statusId
     *
     * @return ModelPaymentServiceProcessorInterface
     */
    private function createServiceRequestModel($statusId)
    {
        return $this->serviceFactory()->getProcessor($statusId);
    }

    /**
     * @param $newStatusId
     *
     * @return bool
     */
    private function isStatusAllowedToProcess($newStatusId)
    {
        $allowedStatuses = $this->getBmHelper()->getAllowedStatuses();
        return in_array($newStatusId, $allowedStatuses);
    }

    /**
     * @return ModelPaymentServiceFactory
     */
    private function serviceFactory()
    {
        return $this->model_billmate_service_factory;
    }

    /**
     * @return Helperbm
     */
    protected function getBmHelper()
    {
        return $this->helperBillmate;
    }
}
