<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Requests\RequestParamStudentRegisterForWeb;
use IizunaLMS\Students\StudentRegisterForWeb;

$params = [];
$errors = [];

if (empty($_POST)) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_ILLEGAL_TRANSITION);
}
else {
    $params = new RequestParamStudentRegisterForWeb();
    $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
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