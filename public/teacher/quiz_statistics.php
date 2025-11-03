<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\JsonQuizStatisticsController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$quizId = $_GET['quiz_id'] ?? 0;
$sort = $_GET['sort'] ?? null;

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->GetQuizById($quizId);

// 作成者が自分でない場合はエラー
if ($jsonQuiz['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(ERROR_JSON_QUIZ_PERMISSION);

$JsonQuizStatisticsController = new JsonQuizStatisticsController();
$pageData = null;
$statisticsData = null;

if ($JsonQuizStatisticsController->IsStatisticsData($quizId))
{
    $statisticsData = $JsonQuizStatisticsController->GetStatisticsData($quizId);

    $quizResults = $JsonQuizController->GetsResult($jsonQuiz['id'], 'DESC');
    $pageData = $JsonQuizStatisticsController->GetStatisticsPageData(
        json_decode($jsonQuiz['json'], true),
        $quizResults,
        $sort
    );
}

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->assign('id', $quizId);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('pageData', $pageData);
$smarty->assign('statisticsData', $statisticsData);
$smarty->assign('sort', $sort);
$smarty->display('_quiz_statistics.html');