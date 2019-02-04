<?php
class Helperbm
{
    const SESSION_HASH_CODE = 'billmate_checkout_hash';

    /**
     * HelperBillmate constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        $this->config = $registry->get('config');
        $this->session = $registry->get('session');
    }

    /**
     * @return Billmate
     */
    public function getBillmateConnection()
    {
        $id = $this->getBillmateId();
        $secret = $this->getBillmateSecret();
        $isTestMode = $this->isChekcoutTestMode();
        $billmateConnection = new Billmate($id, $secret, true, $isTestMode);
        return $billmateConnection;
    }

    /**
     * @return int
     */
    public function getBillmateId()
    {
        return $this->config->get('module_billmate_checkout_bm_id');
    }

    /**
     * @return string
     */
    public function getBillmateSecret()
    {
        return $this->config->get('module_billmate_checkout_secret');
    }

    /**
     * @return bool
     */
    public function isChekcoutTestMode()
    {
        return (bool)$this->config->get('module_billmate_checkout_test_mode');
    }

    /**
     * @param $hash string
     */
    public function setSessionBmHash($hash)
    {
        $this->session->data[self::SESSION_HASH_CODE] = $hash;
    }

    /**
     * @return string
     */
    public function getSessionBmHash()
    {
        if (isset($this->session->data[self::SESSION_HASH_CODE])) {
            return $this->session->data[self::SESSION_HASH_CODE];
        }
        return '';
    }

    public function resetSessionBmHash()
    {
        if (isset($this->session->data[self::SESSION_HASH_CODE])) {
            unset($this->session->data[self::SESSION_HASH_CODE]);
        }
    }

    public function log($data)
    {
        $log = new Log('billmate_checkout.log');
        $log->write($data);
    }
}