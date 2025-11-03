<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\JsonQuizzes\JsonQuizPushNotification;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizDelivery;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 登録・更新
if (isset($_POST['onigiri_json_quiz_id']) && !empty($_POST['delivery'])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    $onigiriJsonQuizId = $params['onigiri_json_quiz_id'];

    // 既に配信されているデータを取得し、登録、削除に分けて処理する
    $deliveryRecords = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $onigiriJsonQuizId);
    $deliveryLmsCodeIds = [];

    foreach ($deliveryRecords as $record) $deliveryLmsCodeIds[] = $record['lms_code_id'];

    $addIds = [];
    $deleteIds = [];

    foreach ($params['delivery'] as $deliveryId)
    {
        // 既に配信されているものは新規登録しない
        // また、既存IDの一覧から除外しておき、削除対象にもしないようにする
        if (in_array($deliveryId, $deliveryLmsCodeIds)) {
            for ($i=0; $i<count($deliveryLmsCodeIds); ++$i)
            {
                if ($deliveryLmsCodeIds[$i] == $deliveryId)
                {
                    unset($deliveryLmsCodeIds[$i]);
                    $deliveryLmsCodeIds = array_values($deliveryLmsCodeIds);
                    break;
                }
            }
        }
        else {
            $addIds[] = $deliveryId;
        }
    }

    // $deliveryLmsCodeIds 残っているものは削除対象にする
    $deleteIds = $deliveryLmsCodeIds;

    $addResult = true;
    $deleteResult = true;
    $OnigiriJsonQuizDeliveryModel = new OnigiriJsonQuizDeliveryModel();

    PDOHelper::GetPDO()->beginTransaction();
    // 新規追加
    if (!empty($addIds)) {
        $addResult = (new OnigiriJsonQuizDelivery())->Register($onigiriJsonQuizId, $addIds);

        if ($addResult) {
            try {
                (new JsonQuizPushNotification())->SendNotify($addIds);
            } catch (\GuzzleHttp\Exception\GuzzleException|\Google\Exception $e) {
                DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_QUIZ_DELIVERY_REGISTER_FAILED);
            }
        }
    }

    // 削除
    if (!empty($deleteIds)) {
        $deleteResult = (new OnigiriJsonQuizDelivery())->Delete($onigiriJsonQuizId, $deleteIds);
    }

    if ($addResult && $deleteResult) {
        PDOHelper::GetPDO()->commit();

        if (ONIGIRI_NOTIFICATION_FLAG && !empty($addIds)) {
            $lmsCodes = (new LmsCodeModel())->GetsByKeyInValues('id', $addIds);

            foreach ($lmsCodes as $lmsCode) {
                $url = WWW_ROOT_URL . "/student/onigiri_quiz_info.php?quiz_id={$onigiriJsonQuizId}";
                $result = (new OnigiriJsonQuizDelivery())->SendQuizNotification($url, $lmsCode['lms_code'], $onigiriJsonQuizId, $lmsCode['id']);

                if (!empty($result['error'])) {
                    PDOHelper::GetPDO()->rollBack();
                    DisplayJsonHelper::ShowErrorAndExit($result['error']);
                }
            }
        }

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_DELIVERY_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_DELIVERY_PARAMETER_ERROR);
}