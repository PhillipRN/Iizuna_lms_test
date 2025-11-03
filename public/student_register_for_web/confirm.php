<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Students\StudentRegister;

if (!SessionHelper::IssetStudentRegisterForWebData())
{
    header('Location: ./index.php');
    exit;
}

$params = [];
$errors = [];

if (!empty($_POST))
{
    $params = RequestHelper::GetPostParams();

    if (isset($params['submit']))
    {
        if (CSRFHelper::CheckPostKey())
        {
            $data = SessionHelper::GetStudentRegisterForWebData();

            $result = (new StudentRegister())->Register($data->ToArray());

            if (isset($result['error_code']))
            {
                $errors[] = ErrorMessage::GetMessage($result['error_code']);
            }

            if (empty($errors))
            {
                SessionHelper::UnsetStudentRegisterForWebData();
                SessionHelper::UnsetStudentRegisterForWebIsApp();
                header('Location: ./end.php');
                exit;
            }
        }
        // CSRFチェックエラー
        else
        {
            $errors[] = ErrorMessage::GetMessage(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_ILLEGAL_TRANSITION);
        }
    }
}

$params = SessionHelper::GetStudentRegisterForWebData();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('params', $params);
$smarty->assign('errors', $errors);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_confirm.html');
