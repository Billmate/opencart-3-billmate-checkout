<?php
class ModelBillmateServiceFactory extends Model
{
    /**
     * @var Helperbm
     */
    protected $helperBillmate;

    /**
     * ModelBillmateServiceFactory constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @param $statusId
     * @return ModelBillmateServiceProcessorStatusInterface
     */
    public function getProcessor($statusId)
    {
        $processorsMap = $this->getProcessorsMap();
        if (isset($processorsMap[$statusId])) {
            $typeProcess = $processorsMap[$statusId];
            $this->load->model('billmate/service/processor/' . $typeProcess);
            $modelCode='model_billmate_service_processor_'. $typeProcess;
            return $this->{$modelCode};
        }
        throw new \Exception(
            'Error: Could not load service processor for status ' . $statusId . '!'
        );
    }

    /**
     * @return array
     */
    public function getProcessorsMap()
    {
        return $this->getBillmateConfig()->getProcessorsMap();
    }

    /**
     * @return Helperbm
     */
    private function getBillmateConfig()
    {
        return $this->helperBillmate;
    }
}