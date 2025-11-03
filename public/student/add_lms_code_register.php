<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Requests\RequestParamStudentAddLmsCode;
use IizunaLMS\Students\AddLmsCode;

$params = [];
$errors = [];

if (!StudentLoginController::IsLogin() || empty($_POST)) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_ILLEGAL_TRANSITION);
}
else {
    $AddLmsCode = new AddLmsCode();
    $student = StudentLoginController::GetStudentData();

    $params = new RequestParamStudentAddLmsCode();
    $errorCodes = $AddLmsCode->CheckValidateParameters($student->id, $params);

    if (empty($errorCodes)) {

        if (!$AddLmsCode->AddLmsCode($student->id, $params))
        {
            $errorCodes[] = Error::ERROR_STUDENT_ADD_LMS_CODE;
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
        'error' => $errorMessage
    ];
}

DisplayJsonHelper::ShowAndExit($result);