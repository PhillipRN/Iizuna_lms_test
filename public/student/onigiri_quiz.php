<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin() && !TeacherLoginController::IsLogin()) {
    StudentLoginController::RedirectLoginErrorPage();
    exit;
}

$isPreview = $_GET['preview'] ?? 0;
$quizId = $_GET['quiz_id'] ?? '';
if (empty($quizId)) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_INVALID_URL);

$onigiriJsonQuiz = [];

if (StudentLoginController::IsLoginOrTryAutoLogin())
{
    $student = StudentLoginController::GetStudentData();
    $onigiriJsonQuiz = (new OnigiriJsonQuizLoader())->GetAvailableQuizForStudent($quizId, $student->id);
}
else
{
    $teacher = TeacherLoginController::GetTeacherData();
    $onigiriJsonQuiz = (new OnigiriJsonQuizLoader())->GetAvailableQuizForSchool($quizId, $teacher->school_id);
}

if (!empty($onigiriJsonQuiz['error'])) {
    DisplayErrorHelper::RedirectStudentErrorPage($onigiriJsonQuiz['error']);
}

$data = $onigiriJsonQuiz['data'];
$questions = json_decode($data['json'], true);

$answers = [];
$questionTypes = [];
foreach ($questions as $question) {
    $answers[] = $question['answer'];
    $questionTypes[] = $question['type'];
}
$answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);
$questionTypesJson = json_encode($questionTypes, JSON_UNESCAPED_UNICODE);

$currentTime = time();
$isExpired = false;

if (!empty($data['expire_date']))
{
    $isExpired = (strtotime($data['expire_date']) < $currentTime);
}


$countDownSeconds = PeriodHelper::CalculateCountDownSeconds(
    $currentTime,
    $data['expire_date'],
    $data['time_limit']
);

// int32 の値を超える場合は setTimeout できないので0にして制限なし扱いにする
// setTimeOut ではミリセカンドなので x1000 した値で判定する
if ($countDownSeconds * 1000 > 2147483647) $countDownSeconds = 0;

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('data', $data);
$smarty->assign('is_expired', $isExpired ? 1 : 0);
$smarty->assign('questions', $questions);
$smarty->assign('answersJson', $answersJson);
$smarty->assign('questionTypesJson', $questionTypesJson);
$smarty->assign('isPreview', $isPreview);
$smarty->assign('countDownSeconds', $countDownSeconds);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz.html');