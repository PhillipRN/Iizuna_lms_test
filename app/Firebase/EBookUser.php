<?php

namespace IizunaLMS\Firebase;

class EBookUser
{
    /**
     * @param $lmsCode
     * @return mixed
     */
    public function GetUserData($lmsCode)
    {
        $curl = curl_init();
        $apiKey = DENSHI_SHOSEKI_API_KEY;

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_URL => DENSHI_SHOSEKI_API . "/getUsersByLmsCode?apiKey={$apiKey}&lmsCode={$lmsCode}"
        ));

        $json = curl_exec($curl);
        curl_close($curl);

        return json_decode($json, true);
    }

    /**
     * @param $loginId
     * @param $lmsCode
     * @return mixed
     */
    public function DeleteUserCode($loginId, $lmsCode)
    {
        $curl = curl_init();
        $apiKey = DENSHI_SHOSEKI_API_KEY;

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_URL => DENSHI_SHOSEKI_API . "/deleteLmsCodeForUsersWithId?apiKey={$apiKey}&lmsCode={$lmsCode}&loginId={$loginId}"
        ));

        $output = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return $responseCode;
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