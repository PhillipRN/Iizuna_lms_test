<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Datas\JsonQuizDelivery;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\JsonQuizzes\JsonQuizPushNotification;
use IizunaLMS\Models\JsonQuizDeliveryModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 登録・更新
if (isset($_POST['json_quiz_id']) && !empty($_POST['delivery'])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    $jsonQuizId = $params['json_quiz_id'];

    // 既に配信されているデータを取得し、登録、削除に分けて処理する
    $deliveryRecords = (new JsonQuizDeliveryModel())->GetsByKeyValue('json_quiz_id', $jsonQuizId);
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
    $JsonQuizDeliveryModel = new JsonQuizDeliveryModel();

    PDOHelper::GetPDO()->beginTransaction();
    // 新規追加
    if (!empty($addIds)) {
        $addArray = [];

        foreach ($addIds as $addId) {
            $addArray[] = new JsonQuizDelivery([
                'json_quiz_id' => $jsonQuizId,
                'lms_code_id' => $addId
            ]);
        }

        $addResult = $JsonQuizDeliveryModel->MultipleAdd($addArray);

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
        $deleteArray = [];

        foreach ($deleteIds as $deleteId) {
            $JsonQuizDelivery = new JsonQuizDelivery([
                'json_quiz_id' => $jsonQuizId,
                'lms_code_id' => $deleteId
            ]);

            $deleteResult = $JsonQuizDeliveryModel->Delete($JsonQuizDelivery);

            // 削除に失敗した場合は中断する
            if (!$deleteResult) break;
        }
    }

    if ($addResult && $deleteResult) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_QUIZ_DELIVERY_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_QUIZ_DELIVERY_PARAMETER_ERROR);
}