<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CookieHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Students\AutoLoginTokenGenerator;
use IizunaLMS\Students\StudentAuthorization;

$params = [];
$errors = [];

if (!empty($_POST))
{
    $params = RequestHelper::GetPostParams();

    $isAutoLogin = !empty($params['auto_login']);

    $result = StudentLoginController::LoginFromWeb($params['login_id'], $params['password']);

    if ($result == Error::ERROR_NONE)
    {
        if ($isAutoLogin) {
            $autoLoginToken = (new AutoLoginTokenGenerator())->Generate();

            // Cookie に保存する
            CookieHelper::SetAutoLoginToken($autoLoginToken);

            $student = StudentLoginController::GetStudentData();
            $addAutoLoginTokenResult = (new StudentAuthorization())->AddAutoLoginToken($student->id, $autoLoginToken);

            if (empty($addAutoLoginTokenResult))
            {
                $errors[] = ErrorMessage::GetMessage($addAutoLoginTokenResult);
            }
        }

        if (empty($errors)) {
            if (SessionHelper::IssetStudentBeforeLoginUrl()) {
                $url = SessionHelper::GetStudentBeforeLoginUrl();
                SessionHelper::UnsetStudentBeforeLoginUrl();

                header("Location: {$url}");
            }
            else {
                header('Location: index.php');
            }
            exit;
        }
    }
    else {
        $errors[] = ErrorMessage::GetMessage($result);
    }
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('params', $params);
$smarty->assign('errors', $errors);
$smarty->display('_login.html');