<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\RefreshTokenGenerator;
use IizunaLMS\Students\StudentAuthorization;

$params = RequestHelper::GetPostParams();
$authorizationKey = $params['authorization_key'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($authorizationKey))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_AUTHORIZATION_INVALID_PARAMETER);
    exit;
}

$StudentAuthorization = new StudentAuthorization();

$record = $StudentAuthorization->GetAuthorizationKeyEffectiveRecord($authorizationKey);

if (empty($record))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_AUTHORIZATION_NOT_FOUND);
    exit;
}

// 認証キーがあった場合はリフレッシュトークンを作成して返す
$refreshToken = (new RefreshTokenGenerator())->Generate();

$result = $StudentAuthorization->AddRefreshToken($record['student_id'], $refreshToken);

if (empty($result))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_AUTHORIZATION_FAILED);
    exit;
}

// リフレッシュトークンを返す
$result = [
    'result' => 'OK',
    'refresh_token' => $refreshToken
];

DisplayJsonHelper::ShowAndExit($result);