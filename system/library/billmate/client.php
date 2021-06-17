<?php

namespace Billmate;

use Exception;

class Client
{
    public const PLUGIN_VERSION = '1.2.1';
    public const API_VERSION = '2.2.2';
    public const API_HOST = 'https://api.billmate.se';

    private $credentials = [];

    public function __construct($id, $key, $test = false)
    {
        $this->credentials = [
            'id'         => $id,
            'key'        => $key,
            'hash'       => null,
            'version'    => self::API_VERSION,
            'client'     => $this->buildPluginVersion(),
            'language'   => 'sv',
            'serverdata' => $_SERVER ?? null,
            'time'       => microtime(true),
            'test'       => $test,
        ];
    }

    public function __call($method, $args)
    {
        return (count($args)) ? $this->call($method, $args[0]) : null;
    }

    public function call($method, $params)
    {
        $this->setHash($params);

        $payload = [
            'credentials' => $this->credentials,
            'data'        => $params,
            'function'    => $method,
        ];

        $response = $this->request($payload);

        return $this->verify($response);
    }

    protected function request(array $payload)
    {
        $payload = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_HOST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            sprintf('Content-Type: %s', 'application/json'),
            sprintf('Content-Length: %s', strlen($payload)),
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        } elseif (empty($response)) {
            throw new Exception('Communication Error');
        }

        curl_close($ch);

        return $response;
    }

    private function setHash($data)
    {
        $this->credentials['hash'] = $this->buildHash($data);

        return true;
    }

    private function verify($response)
    {
        $data = is_array($response) ? $response : json_decode($response, true);

        if (empty($data)) {
            throw new Exception('Verification error. Response is empty.');
        }

        if (!is_array($data)) {
            $data['credentials'] = json_decode($data['credentials'], true);
            $data['data'] = json_decode($data['data'], true);
        }

        if (empty($data['credentials']) || empty($data['credentials']['hash'])) {
            return $data;
        }

        $hash = $this->buildHash($data['data']);

        if ($data['credentials']['hash'] !== $hash) {
            throw new Exception('Verification error. Hashes do not match.');
        }

        return $data['data'] ?? [];
    }

    public function buildHash($data)
    {
        return hash_hmac('sha512', json_encode($data), $this->credentials['key']);
    }

    private function buildPluginVersion()
    {
        return sprintf('OpenCart v.%s with API v.%s', VERSION, self::PLUGIN_VERSION);
    }
}
