<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Requests\RequestParamStudentChangePassword;
use IizunaLMS\Students\ChangePassword;

$params = [];
$errors = [];

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!StudentLoginController::IsLogin() || empty($_POST)) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_ILLEGAL_TRANSITION);
}
else {
    $ChangePassword = new ChangePassword();

    $params = new RequestParamStudentChangePassword();
    $errorCodes = $ChangePassword->CheckValidateParameters($params);

    if (empty($errorCodes)) {
        $student = StudentLoginController::GetStudentData();

        $password = StringHelper::GetHashedString($params->password);

        if (!$ChangePassword->UpdatePassword($student->id, $password))
        {
            $errorCodes[] = Error::ERROR_STUDENT_CHANGE_PASSWORD_FAILED;
        }
        else {
            // 更新に成功したらセッションのパスワード更新フラグも変更する
            $student = SessionHelper::GetLoginStudentData();
            $student['is_change_password'] = 0;

            SessionHelper::SetLoginStudentData($student);
        }
    }
}

$result = [];

if (empty($errorCodes)) {
    $result = [
        'result' => 'OK'
    ];
}
else {
    $errorMessage = implode('<br />', ErrorMessage::GetMessages($errorCodes));
    $result = [
        'error' => [
            'message' => $errorMessage
        ]
    ];
}

DisplayJsonHelper::ShowAndExit($result);