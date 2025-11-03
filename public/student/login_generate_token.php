<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\LoginTokenGenerator;
use IizunaLMS\Students\StudentAuthorization;

$params = RequestHelper::GetPostParams();
$accessToken = $params['access_token'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($accessToken))
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

// ログイントークンを作成して返す
$loginToken = (new LoginTokenGenerator())->Generate();

$result = $StudentAuthorization->AddLoginToken($record['student_id'], $loginToken);

if (empty($result))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_LOGIN_TOKEN_GENERATE_FAILED);
    exit;
}

$result = [
    'result' => 'OK',
    'login_token' => $loginToken
];

DisplayJsonHelper::ShowAndExit($result);