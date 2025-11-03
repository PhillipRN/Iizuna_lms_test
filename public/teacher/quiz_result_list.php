<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizStudentLoader;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\StudentLmsCodeModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? 0;

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->GetQuizById($quizId);

// TODO 自分のLMSコードでない場合はエラー

// LMSコードに所属している生徒一覧を取得する
$students = (new JsonQuizStudentLoader())->GetStudentsById($jsonQuiz['id']);

$records = $JsonQuizController->GetsResult($jsonQuiz['id'], 'DESC');

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('max_score', $jsonQuiz['max_score']);
$smarty->assign('students', $students);
$smarty->assign('records', $records);
$smarty->display('_quiz_result_list.html');