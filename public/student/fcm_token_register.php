<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\StudentFcmTokenModel;
use IizunaLMS\Students\Datas\StudentFcmTokenData;
use IizunaLMS\Students\StudentAuthorization;

$params = RequestHelper::GetPostParams();
$accessToken = $params['access_token'] ?? null;
$fcmToken = $params['fcm_token'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($accessToken) || empty($fcmToken))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_INVALID_PARAMETER);
    exit;
}

$StudentAuthorization = new StudentAuthorization();
$record = $StudentAuthorization->GetAccessTokenEffectiveRecord($accessToken);

if (empty($record))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_TOKEN_NOT_FOUND);
    exit;
}

$studentId = $record['student_id'];
$StudentFcmTokenModel = new StudentFcmTokenModel();

// すでに登録済みの FCM トークンを取得
$registeredTokens = $StudentFcmTokenModel->GetsByKeyValues(
    ['student_id', 'fcm_token'],
    [$studentId, $fcmToken]);

// 同じ FCM トークンが登録されていた場合は有効期限を更新する
if (!empty($registeredTokens))
{
    $result = $StudentFcmTokenModel->UpdateExpireDate($studentId, $fcmToken, date('Y-m-d H:i:s', strtotime('+2 month')));
    if (empty($result))
    {
        Error::ShowErrorJson(Error::ERROR_STUDENT_UPDATE_FCM_TOKEN_FAILED);
        exit;
    }
}
// 未登録の場合は新規登録する
else
{
    $studentFcmTokenData = new StudentFcmTokenData([
        'student_id' => $studentId,
        'fcm_token' => $fcmToken,
        'expire_date' => date('Y-m-d H:i:s', strtotime('+2 month'))
    ]);

    $result = $StudentFcmTokenModel->Add($studentFcmTokenData);
    if (empty($result))
    {
        Error::ShowErrorJson(Error::ERROR_STUDENT_ADD_FCM_TOKEN_FAILED);
        exit;
    }
}

$result = [
    'result' => 'OK'
];

DisplayJsonHelper::ShowAndExit($result);