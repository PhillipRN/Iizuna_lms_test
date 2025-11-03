<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    StudentLoginController::RedirectLoginErrorPage();
    exit;
}

$quizId = $_GET['quiz_id'] ?? '';
if (empty($quizId)) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_INVALID_URL);

$student = StudentLoginController::GetStudentData();

$onigiriJsonQuiz = (new OnigiriJsonQuizLoader())->GetAvailableQuizForStudent($quizId, $student->id);

if (!empty($onigiriJsonQuiz['error'])) {
    DisplayErrorHelper::RedirectStudentErrorPage($onigiriJsonQuiz['error']);
}

$resultRecords = (new OnigiriJsonQuizResultModel())->GetsUserScore($student->id, [$quizId]);
$isAnswered = !empty($resultRecords);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('data', $onigiriJsonQuiz['data']);
$smarty->assign('isAnswered', $isAnswered);
$smarty->display('_onigiri_quiz_info.html');