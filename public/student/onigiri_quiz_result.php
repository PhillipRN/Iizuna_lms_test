<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Models\OnigiriQuizModel;

$student = StudentLoginController::GetStudentData();

$id = $_GET['id'] ?? 0;

$onigiriJsonQuizResult = (new OnigiriJsonQuizResultModel)->GetById($id);
$onigiriJsonQuiz = (new OnigiriJsonQuizModel())->GetById($onigiriJsonQuizResult['onigiri_json_quiz_id']);

// 生徒自身のものか、先生モードの場合は作成した先生でない場合はエラー
$mode = $_GET['mode'] ?? '';
switch ($mode)
{
    // 先生かチェック
    case 't':
        if (!TeacherLoginController::IsLogin()) {
            header('Location: ./login.php');
            exit;
        }

        $teacher = TeacherLoginController::GetTeacherData();
        if ($teacher->id != $onigiriJsonQuiz['teacher_id']) {
            DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_JSON_QUIZ_STUDENT_RESULT_ACCESS_ERROR);
            exit;
        }

        break;

    // 自身かチェック
    default:
        if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
            StudentLoginController::RedirectLoginErrorPage();
            exit;
        }

        if ($onigiriJsonQuizResult['student_id'] != $student->id)
            DisplayErrorHelper::RedirectStudentErrorPage(Error::ERROR_ONIGIRI_JSON_QUIZ_RESULT_PERMISSION);
        break;
}

$questions = json_decode($onigiriJsonQuiz['json'], true);
$answers = json_decode($onigiriJsonQuizResult['answers_json'], true);

$wordIds = [];

foreach ($questions as $question) $wordIds[] = $question['id'];

$wordRecords = (new OnigiriQuizModel())->GetsByKeyInValues('id', $wordIds);

$words = [];
$records = [];
$score = 0;

for ($i=0; $i<count($wordRecords); $i++) {
    $wordRecord = $wordRecords[$i];

    $words[ $wordRecord['id'] ] = [
        'word' => $wordRecord['word'],
        'mean' => $wordRecord['mean']
    ];
}

for ($i=0; $i<count($questions); $i++) {
    $question = $questions[$i];
    $isCorrect = false;
    $answer = null;

    if (!empty($answers[$i])) {
        $isCorrect = ($answers[$i]['isCorrect']);
        $answer = $answers[$i]['answer'];
    }

    if ($isCorrect) ++$score;

    $word = $words[ $question['id'] ];

    $records[] = [
        'word' => $word['word'],
        'mean' => $word['mean'],
        'answer' => $answer,
        'isCorrect' => $isCorrect,
    ];
}

$data = [
    'title' => $onigiriJsonQuiz['title'],
    'total' => $onigiriJsonQuiz['total'],
    'score' => $score,
    'correctRate' => floor($score / $onigiriJsonQuiz['total'] * 10000) / 100,
];

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('jsonQuiz', $onigiriJsonQuiz);
$smarty->assign('data', $data);
$smarty->assign('records', $records);
$smarty->assign('isTeacher', $mode == 't' ? 1 : 0);
$smarty->display('_onigiri_quiz_result.html');