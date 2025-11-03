<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_NOT_LOGIN);
    exit;
}

$student = StudentLoginController::GetStudentData();

$JsonQuizController = new JsonQuizController();
$result = $JsonQuizController->RegisterResult($student->id, RequestHelper::GetPostParams());

DisplayJsonHelper::ShowAndExit($result);
