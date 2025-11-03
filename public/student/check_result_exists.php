<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_NOT_LOGIN);
    exit;
}

$id = $_GET['id'] ?? 0;

$JsonQuizController = new JsonQuizController();
$jsonQuizResult = $JsonQuizController->GetResultById($id);

$response = [
    'exists' => !empty($jsonQuizResult) && is_array($jsonQuizResult),
    'error' => Error::ERROR_NONE
];

DisplayJsonHelper::ShowAndExit($response);
