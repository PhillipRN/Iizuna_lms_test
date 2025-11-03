<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Onigiri\OnigiriJsonQuizRegister;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_NOT_LOGIN);
    exit;
}

$student = StudentLoginController::GetStudentData();

$result = (new OnigiriJsonQuizRegister())->RegisterResult($student->id, RequestHelper::GetPostParams());

if (!empty($result['error'])) DisplayJsonHelper::ShowErrorAndExit($result['error']);

CSRFHelper::ReleaseKey();

DisplayJsonHelper::ShowAndExit([
    'result' => 'OK',
    'result_id' => $result['result_id']
]);
