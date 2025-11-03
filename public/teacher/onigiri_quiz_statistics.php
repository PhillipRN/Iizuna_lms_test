<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizStatisticsLoader;
use IizunaLMS\Onigiri\OnigiriJsonQuizType;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? 0;
$sort = $_GET['sort'] ?? null;

$jsonQuiz = (new OnigiriJsonQuizModel())->GetById($quizId);
$teacher = TeacherLoginController::GetTeacherData();

// 作成者が自分でない場合はエラー
if ($jsonQuiz['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(ERROR_JSON_QUIZ_PERMISSION);

$OnigiriJsonQuizStatisticsController = new OnigiriJsonQuizStatisticsLoader();
$pageData = null;
$statisticsData = null;

if ($OnigiriJsonQuizStatisticsController->IsStatisticsData($quizId))
{
    $statisticsData = $OnigiriJsonQuizStatisticsController->GetStatisticsData($quizId);

    $quizResults = (new OnigiriJsonQuizResultModel())->GetsByKeyValues(
        ['onigiri_json_quiz_id', 'is_first_result'],
        [$jsonQuiz['id'], 1],
        [],
        ['id' => 'DESC']
    );

    $pageData = $OnigiriJsonQuizStatisticsController->GetStatisticsPageData(
        json_decode($jsonQuiz['json'], true),
        $quizResults,
        $sort
    );
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('id', $quizId);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('pageData', $pageData);
$smarty->assign('statisticsData', $statisticsData);
$smarty->assign('sort', $sort);
$smarty->display('_onigiri_quiz_statistics.html');