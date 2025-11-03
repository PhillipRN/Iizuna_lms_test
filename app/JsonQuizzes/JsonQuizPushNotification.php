<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Firebase\CloudMessaging;
use IizunaLMS\Messages\Message;
use IizunaLMS\Models\StudentFcmTokenModel;
use IizunaLMS\Models\StudentLmsCodeModel;

class JsonQuizPushNotification
{
    /**
     * @param $lmsCodeIds
     * @return void
     * @throws \Google\Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function SendNotify($lmsCodeIds)
    {
        $fcm = new CloudMessaging();
        $messageData = Message::GetMessageData()['push_notification'];

        $studentsLmsCodes = (new StudentLmsCodeModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);

        $studentIds = [];
        foreach ($studentsLmsCodes as $studentsLmsCode) $studentIds[] = $studentsLmsCode['student_id'];

        if (empty($studentIds)) return;

        $studentFcmTokens = (new StudentFcmTokenModel())->GetsByKeyInValues('student_id', $studentIds);

        foreach ($studentFcmTokens as $studentFcmToken) {
            $result = $fcm->SendByToken(
                $messageData['title'],
                $messageData['body'],
                $studentFcmToken['fcm_token'],
            );

            if (!empty($result['error'])) {
                switch($result['error']['code'])
                {
                    case 404:
                        // エラーカウントアップ
                        (new StudentFcmTokenModel())->IncrementFailedCount($studentFcmToken['student_id'], $studentFcmToken['fcm_token']);
                        break;

                    default:
                        error_log(json_encode($result));
                        break;
                }
            }
        }
    }
}