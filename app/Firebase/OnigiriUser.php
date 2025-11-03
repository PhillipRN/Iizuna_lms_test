<?php

namespace IizunaLMS\Firebase;

class OnigiriUser
{
    public function GetOnigiriUserData($lmsCode)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_URL => ONIGIRI_USER_API . "getUsersBySchoolCode?schoolCode={$lmsCode}"
        ));

        $json = curl_exec($curl);
        curl_close($curl);

        return json_decode($json, true);
    }

    /**
     * @return string[]
     */
    private function GetHeader()
    {
        return [
            'Content-Type: application/json',
            'Accept-Charset: UTF-8'
        ];
    }
}