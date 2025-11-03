<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
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

// 有効期限関係なくレコードを取得
$record = $StudentAuthorization->GetAuthorizationKeyRecord($authorizationKey);

if (empty($record))
{
    Error::ShowErrorJson(Error::ERROR_STUDENT_AUTHORIZATION_NOT_FOUND);
    exit;
}

// 削除しないレコード以外を削除
if (empty($record['is_not_delete']))
{
    // 登録済みの認証キーを削除する
    $deleteResult = $StudentAuthorization->DeleteAuthorizationKeyRecord($authorizationKey);
}

$result = [
    'result' => 'OK'
];

DisplayJsonHelper::ShowAndExit($result);