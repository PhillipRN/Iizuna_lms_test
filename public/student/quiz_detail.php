<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_NOT_LOGIN);
    exit;
}

$student = StudentLoginController::GetStudentData();

$JsonQuizController = new JsonQuizController();

$quizId = $_GET['quiz_id'] ?? '';
if (empty($quizId)) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_INVALID_URL);

// TODO 自身が受けれるテストじゃない場合エラー

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->Get($quizId);

$isExpired = (strtotime($jsonQuiz['expire_date']) < time()) ? 1 : 0;

if (empty($jsonQuiz)) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_NO_DATA);

$results = $JsonQuizController->GetsUsersResult($jsonQuiz['id'], $student->id);

// みんなの結果取得
$summary = $JsonQuizController->GetResultSummaryByJsonQuizId($jsonQuiz['id']);
if (empty($summary)) {
    $summary = [
        'average' => 0,
        'highest_score' => 0,
        'lowest_score' => 0,
    ];
}
else {
    // 百分率を戻す
    $summary['average'] = (!empty($summary['average'])) ? $summary['average'] / 100 : 0;
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('quiz_id', $quizId);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('data', json_decode($jsonQuiz['json'], true));
$smarty->assign('total', $jsonQuiz['max_score']);
$smarty->assign('isVertical', $jsonQuiz['language_type'] == 1);
$smarty->assign('results', $results);
$smarty->assign('summary', $summary);
$smarty->assign('expireDateTimeStamp', strtotime($jsonQuiz['expire_date']) * 1000);
$smarty->assign('timeLimit', $jsonQuiz['time_limit'] * 60000);
$smarty->assign('isExpired', $isExpired);
$smarty->display('_quiz_detail.html');