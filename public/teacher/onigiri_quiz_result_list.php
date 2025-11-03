<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? null;
$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id) || empty($quizId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$jsonQuiz = (new OnigiriJsonQuizModel())->GetAndResultNumById($quizId);
$records = (new OnigiriJsonQuizLoader())->GetResultsById($quizId);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('total', $jsonQuiz['total']);
$smarty->display('_onigiri_quiz_result_list.html');