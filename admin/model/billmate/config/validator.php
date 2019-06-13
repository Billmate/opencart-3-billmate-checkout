<?php
class ModelBillmateConfigValidator extends Model
{
    /**
     * @var string
     */
    protected $error = '';

    /**
     * ModelBillmateConfigValidator constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/payment/billmate_checkout');
        $this->helperBillmate  = new Helperbm($registry);
    }

    /**
     * @return bool
     */
    public function isConnectionValid()
    {
        $accountInfo = $this->getBMAccountInfo();

        if(isset($accountInfo['message'])) {
            $this->setError(
                utf8_encode($accountInfo['message'])
            );
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getBMAccountInfo()
    {
        $bmConnection = $this->getBmConnection();
        $connetionData = $this->getHelper()->getDefConnectionData();
        return $bmConnection->getAccountInfo($connetionData);
    }

    /**
     * @return Billmate
     */
    protected function getBmConnection()
    {
        return $this->getHelper()->getBillmateConnection();
    }

    /**
     * @return Helperbm
     */
    public function getHelper()
    {
        return $this->helperBillmate;
    }

    /**
     * @param string $message
     */
    public function setError($message)
    {
        $prevMessage = $this->language->get('error_billmate_connection');
        $this->language->set(
            'error_billmate_connection',
            $prevMessage . $message
        );
    }
}