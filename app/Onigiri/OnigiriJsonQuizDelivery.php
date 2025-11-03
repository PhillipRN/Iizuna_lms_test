<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizDeliveryData;

class OnigiriJsonQuizDelivery
{
    /**
     * @param $onigiriJsonQuizId
     * @param $addIds
     * @return bool
     */
    public function Register($onigiriJsonQuizId, $addIds)
    {
        $addArray = [];

        foreach ($addIds as $addId) {
            $addArray[] = new OnigiriJsonQuizDeliveryData([
                'onigiri_json_quiz_id' => $onigiriJsonQuizId,
                'lms_code_id' => $addId
            ]);
        }

        return (new OnigiriJsonQuizDeliveryModel())->MultipleAdd($addArray);
    }

    /**
     * @param $onigiriJsonQuizId
     * @param $deleteIds
     * @return bool
     */
    public function Delete($onigiriJsonQuizId, $deleteIds)
    {
        $result = true;

        $OnigiriJsonQuizDeliveryModel = new OnigiriJsonQuizDeliveryModel();

        foreach ($deleteIds as $deleteId) {
            $OnigiriJsonQuizDeliveryData = new OnigiriJsonQuizDeliveryData([
                'onigiri_json_quiz_id' => $onigiriJsonQuizId,
                'lms_code_id' => $deleteId
            ]);

            $record = $OnigiriJsonQuizDeliveryModel->Get($OnigiriJsonQuizDeliveryData);

            // 指定したレコードがない場合も失敗とする
            if (empty($record)) {
                $result = false;
                break;
            }

            $noticeId = $record['notice_id'] ?? '';

            $result = $OnigiriJsonQuizDeliveryModel->Delete($OnigiriJsonQuizDeliveryData);

            // 削除に失敗した場合は中断する
            if (!$result) {
                $result = false;
                break;
            }

            // お知らせ削除
            if (ONIGIRI_NOTIFICATION_FLAG && !empty($noticeId))
            {
                $resultDeleteNotice = (new OnigiriFirebaseNotification())->DeleteNotice($noticeId);
                // お知らせの削除の同期は取れないため、失敗しても中断しない
            }
        }

        return $result;
    }

    /**
     * @param $onigiriJsonQuizId
     * @param $lmsCodeId
     * @return array|string[]
     */
    public function DeleteNoticeOnly($onigiriJsonQuizId, $lmsCodeId)
    {
        $OnigiriJsonQuizDeliveryModel = new OnigiriJsonQuizDeliveryModel();

        $OnigiriJsonQuizDeliveryData = new OnigiriJsonQuizDeliveryData([
            'onigiri_json_quiz_id' => $onigiriJsonQuizId,
            'lms_code_id' => $lmsCodeId
        ]);

        $record = $OnigiriJsonQuizDeliveryModel->Get($OnigiriJsonQuizDeliveryData);
        $noticeId = $record['notice_id'] ?? '';

        // 指定したレコードがない場合は失敗とする
        if (empty($record)) {
            return ['error' => ERROR::ERROR_ONIGIRI_QUIZ_DELIVERY_DATA_IS_NONE];
        }

        $OnigiriJsonQuizDeliveryData = new OnigiriJsonQuizDeliveryData([
            'onigiri_json_quiz_id' => $onigiriJsonQuizId,
            'lms_code_id' => $lmsCodeId,
            'notice_id' => ''
        ]);

        $result = (new OnigiriJsonQuizDeliveryModel())->UpdateNoticeId($OnigiriJsonQuizDeliveryData);

        // 更新に失敗した場合は失敗とする
        if (!$result) {
            return ['error' => ERROR::ERROR_ONIGIRI_QUIZ_DELIVERY_UPDATE_FAILED];
        }

        // お知らせ削除
        if (ONIGIRI_NOTIFICATION_FLAG && !empty($noticeId))
        {
            $resultDeleteNotice = (new OnigiriFirebaseNotification())->DeleteNotice($noticeId);
            // お知らせの削除の同期は取れないため、失敗しても中断しない
        }

        return ['result' => 'OK'];
    }

    /**
     * @param $url
     * @param $lmsCode
     * @param $onigiriJsonQuizId
     * @param $lmsCodeId
     * @return array|string[]
     */
    public function SendQuizNotification($url, $lmsCode, $onigiriJsonQuizId, $lmsCodeId)
    {
        $jsonQuiz = (new OnigiriJsonQuizLoader())->GetDisplayDataById($onigiriJsonQuizId);
        $term = $this->MakeTerm($jsonQuiz['open_date'], $jsonQuiz['expire_date']);

        $result = (new OnigiriFirebaseNotification())->SendQuizNotice($url, $term, $lmsCode);

        if (!empty($result['error'])) {
            return $result;
        }

        $OnigiriJsonQuizDeliveryData = new OnigiriJsonQuizDeliveryData([
            'onigiri_json_quiz_id' => $onigiriJsonQuizId,
            'lms_code_id' => $lmsCodeId,
            'notice_id' => $result['noticeId']
        ]);

        $result = (new OnigiriJsonQuizDeliveryModel())->UpdateNoticeId($OnigiriJsonQuizDeliveryData);

        if (!$result) {
            return ['error' => ERROR::ERROR_ONIGIRI_QUIZ_DELIVERY_NOTIFICATION_ID_REGIST_FAILER];
        }
        else {
            return ['result' => 'OK'];
        }
    }

    /**
     * @param $openDate
     * @param $expireDate
     * @return string
     */
    private function MakeTerm($openDate, $expireDate): string
    {
        if (empty($openDate) && empty($expireDate)) return 'テスト期間は無制限です。';

        $openDateString   = (!empty($openDate))   ? date('n/j H:i', strtotime($openDate))   : '';
        $expireDateString = (!empty($expireDate)) ? date('n/j H:i', strtotime($expireDate)) : '';

        if      (!empty($openDateString) &&  empty($expireDateString)) return "テスト期間は {$openDateString} からです。";
        else if ( empty($openDateString) && !empty($expireDateString)) return "テスト期間は {$expireDateString} までです。";
        else                                                           return "テスト期間は {$openDateString} から {$expireDateString} までです。";
    }
}