<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\RefreshTokenGenerator;
use IizunaLMS\Students\StudentAuthorization;

$params = RequestHelper::GetPostParams();
$loginId = $params['login_id'] ?? null;
$password = $params['password'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($loginId) || empty($password))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_REFRESH_TOKEN_GENERATE_INVALID_PARAMETER);
    exit;
}

$loginResult = StudentLoginController::Login($loginId, $password);

if (!empty($loginResult['error']))
{
    Error::ShowErrorJson($loginResult['error']);
    exit;
}

$student = $loginResult['student'];

// リフレッシュトークンを作成して返す
$refreshToken = (new RefreshTokenGenerator())->Generate();

$result = (new StudentAuthorization())->AddRefreshToken($student['id'], $refreshToken);

if (empty($result))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_REFRESH_TOKEN_GENERATE_FAILED);
    exit;
}

// リフレッシュトークンを返す
$result = [
    'result' => 'OK',
    'refresh_token' => $refreshToken
];

DisplayJsonHelper::ShowAndExit($result);