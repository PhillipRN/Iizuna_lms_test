<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\StudentRegister;

Error::ShowErrorJson(Error::ERROR_STUDENT_REGISTER_FAILED_AUTHORIZATION_KEY);
exit;

//$params = RequestHelper::GetPostJsonParams();
//
//// ebook_user_id と明確に分けるため、名称を変更する
//$params['onigiri_user_id'] = $params['user_id'];
//unset($params['user_id']);
//
//// 必要なパラメータがない場合はエラー
//if (empty($params['lms_code']) || empty($params['onigiri_user_id']))
//{
//    Error::ShowErrorJson(Error::ERROR_STUDENT_REGISTER_INVALID_PARAMETER);
//    exit;
//}
//
//$result = (new StudentRegister())->Register($params);
//
//if (isset($result['error_code']))
//{
//    Error::ShowErrorJson($result['error_code']);
//    exit;
//}
//
//// 認証キー返す
//$result = [
//    'result' => 'OK',
//    'authorization_key' => $result['authorization_key']
//];
//
//DisplayJsonHelper::ShowAndExit($result);