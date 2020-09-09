<?php
/**
 * Billmate
 *
 * Billmate API - PHP Class
 *
 * LICENSE: This source file is part of Billmate API, that is fully owned by Billmate AB
 * This is not open source. For licensing queries, please contact Billmate AB at info@billmate.se.
 *
 * @category Billmate
 * @package Billmate
 * @author Billmate Support <support@billmate.se>
 * @copyright 2013-2020 Billmate AB
 * @license Proprietary and fully owned by Billmate AB
 * @link http://www.billmate.se
 */
class Billmate {

    const PLUGIN_VERSION = '1.1.6';

    var $ID = "";
    var $KEY = "";
    var $URL = "api.billmate.se";
    var $MODE = "CURL";
    var $SSL = true;
    var $TEST = false;
    var $DEBUG = false;
    var $REFERER = false;

    public function __construct($id,$key,$ssl=true,$test=false,$debug=false,$referer=array()){
        $this->ID = $id;
        $this->KEY = $key;
        defined('BILLMATE_SERVER') || define('BILLMATE_SERVER',  "2.0.6" );
        defined('BILLMATE_LANGUAGE') || define('BILLMATE_LANGUAGE',  "" );
        $this->SSL = $ssl;
        $this->DEBUG = $debug;
        $this->TEST = $test;
        $this->REFERER = $referer;
    }

    /**
     * @param $name
     * @param $args
     *
     * @return array|mixed|void
     */
    public function __call($name,$args)
    {
        if (count($args)==0) {
            return;
        }
        return $this->call($name,$args[0]);
    }

    public function call($function,$params)
    {
        $values = array(
            "credentials" => array(
                "id"=>$this->ID,
                "hash"=>$this->hash(json_encode($params)),
                "version" => BILLMATE_SERVER,
                "client" => $this->getClientSign(),
                "serverdata" => array_merge($_SERVER,$this->REFERER),
                "time" => microtime(true),
                "test" => $this->TEST?"1":"0",
                "language" => BILLMATE_LANGUAGE
            ),
            "data"=> $params,
            "function"=>$function,
        );
        $this->out("CALLED FUNCTION",$function);
        $this->out("PARAMETERS TO BE SENT",$values);
        switch ($this->MODE) {
            case "CURL":
                $response = $this->curl(json_encode($values));
                break;
        }
        return $this->verify_hash($response);
    }

    /**
     * @return string
     */
    public function getClientSign()
    {
        return 'OpenCart:' . VERSION . ' PLUGIN:' . self::PLUGIN_VERSION;
    }

    protected function verify_hash($response)
    {
        $response_array = is_array($response)?$response:json_decode($response,true);
        if (!$response_array && !is_array($response)) {
            return $response;
        }
        if (is_array($response)) {
            $response_array['credentials'] = json_decode($response['credentials'], true);
            $response_array['data'] = json_decode($response['data'],true);
        }

        if (isset($response_array["credentials"])) {
            $hash = $this->hash(json_encode($response_array["data"]));
            if ($response_array["credentials"]["hash"] == $hash) {
                return $response_array["data"];
            } else {
                return [
                    "code" => 9511,
                    "message" => "Verification error",
                    "hash" => $hash,
                    "hash_received" => $response_array["credentials"]["hash"]
                ];
            }
        }
        return array_map("utf8_decode",$response_array);
    }

    /**
     * @param $parameters
     *
     * @return mixed|string
     */
    protected function curl($parameters)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http".($this->SSL?"s":"")."://".$this->URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);

        // Use cacert.pem to make sure server has the latest ssl certs
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__.'/cacert.pem');

        $vh = $this->SSL?((function_exists("phpversion") && function_exists("version_compare") && version_compare(phpversion(),'5.4','>=')) ? 2 : true):false;
        if ($this->SSL) {
            if (function_exists("phpversion") && function_exists("version_compare")) {
                $cv = curl_version();
                if (version_compare(phpversion(),'5.4','>=') || version_compare($cv["version"],'7.28.1','>=')) {
                    $vh = 2;
                } else {
                    $vh = true;
                }
            } else {
                $vh = true;
            }
        } else {
            $vh = false;
        }

        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $vh);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($parameters))
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $curlerror = curl_error($ch);
            return json_encode(array("code"=>9510,"message"=>htmlentities($curlerror)));
        }else {
            curl_close($ch);
        }

        if (strlen($data) == 0) {
            return json_encode(array("code" => 9510,"message" => htmlentities("Communication Error")));
        }
        return $data;
    }

    /**
     * @param $args
     *
     * @return string
     */
    public function hash($args)
    {
        $this->out("TO BE HASHED DATA",$args);
        return hash_hmac('sha512',$args,$this->KEY);
    }

    /**
     * @param $name
     * @param $out
     */
    protected function out($name,$out)
    {
        if (!$this->DEBUG) {
            return;
        }

        print "$name: '";
        if(is_array($out) or  is_object($out)) print_r($out);
        else print $out;
        print "'\n";
    }
}