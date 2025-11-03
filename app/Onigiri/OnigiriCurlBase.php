<?php

namespace IizunaLMS\Onigiri;

class OnigiriCurlBase
{
    protected $curl;

    public function __construct() {
        $this->InitializeCurl();
    }

    public function __destruct() {
        curl_close($this->curl);
        unset($this->curl);
    }

    protected function InitializeCurl()
    {
        $this->curl = curl_init();
        $this->SetBaseCurlOpt();
    }

    protected function SetBaseCurlOpt()
    {
        curl_setopt_array($this->curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
        ));

        if (defined('ONIGIRI_BASIC_AUTH_FLAG') && ONIGIRI_BASIC_AUTH_FLAG)
        {
            curl_setopt($this->curl, CURLOPT_USERPWD, ONIGIRI_BASIC_AUTH_USER . ':' . ONIGIRI_BASIC_AUTH_PASSWORD);
        }
    }

    protected function GetHeader()
    {
        return [
            'Accept-Charset: UTF-8'
        ];
    }

    /**
     * @param $url
     * @param $postData
     * @return mixed
     */
    protected function CurlExecAndGetDecodedResult($url, $postData)
    {
        curl_setopt_array($this->curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
        ));

        $response = curl_exec($this->curl);
        return json_decode($response, true);
    }
}