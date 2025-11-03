<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\OSDetectHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin() && !TeacherLoginController::IsLogin()) {
    StudentLoginController::RedirectLoginErrorPage();
    exit;
}

// TODO 先生か生徒なのかで処理を分ける

$isPreview = $_GET['preview'] ?? 0;
$quizId = $_GET['quiz_id'] ?? '';
if (empty($quizId)) DisplayErrorHelper::RedirectStudentErrorPage(Error::ERROR_JSON_QUIZ_INVALID_URL);

// TODO 期限外判定
if (false) DisplayErrorHelper::RedirectStudentErrorPage(Error::ERROR_JSON_QUIZ_NOT_RELEASE_OR_PERMISSION);

$JsonQuizController = new JsonQuizController();

$loadData = [];
if (StudentLoginController::IsLoginOrTryAutoLogin())
{
    $student = StudentLoginController::GetStudentData();
    $loadData = (new JsonQuizLoader())->GetAvailableQuizForStudent($quizId, $student->id);
}
else
{
    $teacher = TeacherLoginController::GetTeacherData();
    $loadData = (new JsonQuizLoader())->GetAvailableQuizForSchool($quizId, $teacher->school_id);
}

if (!empty($loadData['error'])) {
    DisplayErrorHelper::RedirectStudentErrorPage($loadData['error']);
}

$jsonQuiz = $loadData['data'];


$isAnswered = false;

if (!$isPreview)
{
    $student = StudentLoginController::GetStudentData();

    $results = $JsonQuizController->GetsUsersResult($jsonQuiz['id'], $student->id);
    $isAnswered = (!empty($results));
}

$currentTime = time();
$isExpired = false;

if (!empty($jsonQuiz['expire_date']))
{
    $isExpired = (strtotime($jsonQuiz['expire_date']) < $currentTime);
}

$countDownSeconds = PeriodHelper::CalculateCountDownSeconds(
    $currentTime,
    $jsonQuiz['expire_date'],
    $jsonQuiz['time_limit']
);

$data = json_decode($jsonQuiz['json'], true);
$questionNo = 1;
for ($i=0; $i<count($data['questions']); ++$i)
{
    $question = $data['questions'][$i];

    if ($question['question_type'] == 'page_break_item') continue;

    $data['questions'][$i]['question_no'] = $questionNo;
    ++$questionNo;
}

// int32 の値を超える場合は setTimeout できないので0にして制限なし扱いにする
// setTimeOut ではミリセカンドなので x1000 した値で判定する
if ($countDownSeconds * 1000 > 2147483647) $countDownSeconds = 0;

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('quiz_id', $quizId);
$smarty->assign('is_expired', $isExpired ? 1 : 0);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('data', $data);
$smarty->assign('isVertical', $jsonQuiz['language_type'] == 1);
$smarty->assign('isPreview', $isPreview);
$smarty->assign('isAnswered', $isAnswered);
$smarty->assign('countDownSeconds', $countDownSeconds);
$smarty->assign('isIOS', OSDetectHelper::IsIOS());
$smarty->display('_quiz.html');