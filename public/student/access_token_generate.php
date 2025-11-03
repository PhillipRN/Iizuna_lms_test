<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\AccessTokenGenerator;
use IizunaLMS\Students\StudentAuthorization;

$params = RequestHelper::GetPostParams();
$refreshToken = $params['refresh_token'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($refreshToken))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_TOKEN_GENERATE_INVALID_PARAMETER);
    exit;
}

// リフレッシュトークンチェック
$StudentAuthorization = new StudentAuthorization();

$record = $StudentAuthorization->GetRefreshTokenEffectiveRecord($refreshToken);

if (empty($record))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_TOKEN_GENERATE_REFRESH_TOKEN_NOT_FOUND);
    exit;
}

// アクセストークンを作成して返す
$accessToken = (new AccessTokenGenerator())->Generate();

$result = $StudentAuthorization->AddAccessToken($record['student_id'], $accessToken);

if (empty($result))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_TOKEN_GENERATE_FAILED);
    exit;
}

$result = [
    'result' => 'OK',
    'access_token' => $accessToken
];

DisplayJsonHelper::ShowAndExit($result);