<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Messages\Message;

class OnigiriFirebaseNotification
{
    /**
     * @param $url
     * @param $term
     * @param $lmsCode
     * @return array
     */
    public function SendQuizNotice($url, $term, $lmsCode)
    {
        $messageBody = Message::GetMessageData()['onigiri']['quiz_notification'];
        $messageBody = preg_replace('/##url##/', $url, $messageBody);
        $messageBody = preg_replace('/##term##/', $term, $messageBody);

        // お知らせ送信
        $notificationResult = $this->SendNotice($messageBody, $lmsCode);

        if (empty($notificationResult['status']) || intval($notificationResult['status']) != 200)
        {
            return ['error' => ERROR::ERROR_ONIGIRI_QUIZ_DELIVERY_NOTIFICATION_FAILER];
        }

        if (empty($notificationResult['payload']) || empty($notificationResult['payload']['noticeId']))
        {
            return ['error' => ERROR::ERROR_ONIGIRI_QUIZ_DELIVERY_NOTIFICATION_ID_NOT_FOUND];
        }

        return [
            'result' => 'OK',
            'noticeId' => $notificationResult['payload']['noticeId']
        ];
    }

    /**
     * @param $message
     * @param $lmsCode
     * @return mixed
     */
    public function SendNotice($message, $lmsCode)
    {
        $curl = curl_init();

        $postData = [
            'schoolCode' => $lmsCode,
            'message' => $message
        ];

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_URL => ONIGIRI_NOTIFICATION_API . "createNoticeForSchoolCode",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData, JSON_UNESCAPED_UNICODE),
        ));

        $json = curl_exec($curl);
        curl_close($curl);

        return json_decode($json, true);
    }

    /**
     * @param $noticeId
     * @return mixed
     */
    public function DeleteNotice($noticeId)
    {
        $curl = curl_init();

        $postData = [
            'noticeId' => $noticeId
        ];

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $this->GetHeader(),
            CURLOPT_RETURNTRANSFER => true,  // curl_execの結果を文字列で返す
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_URL => ONIGIRI_NOTIFICATION_API . "deleteNotice",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData, JSON_UNESCAPED_UNICODE),
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