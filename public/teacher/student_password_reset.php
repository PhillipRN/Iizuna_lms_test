<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Students\ChangePassword;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin())
{
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['student_id']))
{
    $result = (new ChangePassword())->ResetPassword($_POST['student_id']);

    if ($result) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_PASSWORD_RESET_REGISTER_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_PASSWORD_RESET_PARAMETER_ERROR);
}
