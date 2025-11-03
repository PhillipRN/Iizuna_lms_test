<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\TeacherSmartyHelper;

$data = [];
$errors = [];

if (!empty($_POST))
{
    foreach ($_POST as $key => $val)
    {
        $data[$key] = $val;
    }

    $result = TeacherLoginController::Login($data['login_id'], $data['password']);

    if ($result == Error::ERROR_NONE)
    {
        header('Location: index.php');
        exit;
    }
    else
    {
        $errors[] = ErrorMessage::GetMessage($result);
    }
}

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->assign('data', $data);
$smarty->assign('errors', $errors);
$smarty->display('_login.html');