<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\OSDetectHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizStringHelper;

// 生徒か先生でない場合はエラー
$mode = (isset($_GET['mode']) && $_GET['mode'] == 't') ? 't' : '';
switch ($mode)
{
    case 't':
        if (!TeacherLoginController::IsLogin()) {
            header('Location: ./login.php');
            exit;
        }
        break;

    default:
        if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
            header('Location: ./login_error.php');
            exit;
        }
        break;
}

$id = $_GET['id'] ?? 0;

$JsonQuizController = new JsonQuizController();
$jsonQuizResult = $JsonQuizController->GetResultById($id);

// TODO デバッグ
if (empty($jsonQuizResult) || !is_array($jsonQuizResult))
{
    $student = StudentLoginController::GetStudentData();
    error_log('$jsonQuizResult is empty or not an array. ' . "id:$id student_id: " . $student->id . ' HTTP_REFERER: '. $_SERVER['HTTP_REFERER']);
    DisplayErrorHelper::RedirectStudentErrorPage(Error::ERROR_JSON_QUIZ_NO_DATA);
    exit;
}

$jsonQuiz = $JsonQuizController->GetQuizById($jsonQuizResult['json_quiz_id']);

// 生徒自身のものか、先生モードの場合は作成した先生でない場合はエラー
switch ($mode)
{
    // 先生かチェック
    case 't':
        $teacher = TeacherLoginController::GetTeacherData();
        if ($teacher->id != $jsonQuiz['teacher_id']) {
            DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_JSON_QUIZ_STUDENT_RESULT_ACCESS_ERROR);
            exit;
        }

        break;

    // 自身かチェック
    default:
        $student = StudentLoginController::GetStudentData();

        if ($jsonQuizResult['student_id'] != $student->id)
            DisplayErrorHelper::RedirectStudentErrorPage(Error::ERROR_JSON_QUIZ_PERMISSION);
        break;
}

$data = json_decode($jsonQuiz['json'], true);
$answers = json_decode($jsonQuizResult['answers_json'], true);

foreach ($data['questions'] as $key => $question)
{
    if (!isset($question['question_id'])) continue;

    $data['questions'][$key]['user_answer'] = $answers[ $question['question_id'] ]['answer'] ?? '';
}

$caQuestion = [];
foreach ($answers as $key => $answer)
{
    if ($answer['isCorrect']) $caQuestion[] = $key;
}

// みんなの結果取得
$summary = $JsonQuizController->GetResultSummaryByJsonQuizId($jsonQuiz['id']);
if (empty($summary)) {
    $summary = [
        'correct_answer_rates_json' => '{}',
    ];
}

// TODO まとめる
$questionNo = 1;
for ($i=0; $i<count($data['questions']); ++$i)
{
    $question = $data['questions'][$i];

    if ($question['question_type'] == 'page_break_item') continue;

    for ($j=0; $j<count($data['questions'][$i]['answers']); ++$j)
    {
        $myValue = JsonQuizStringHelper::ReplaceUnneededTagsAndWhiteSpace($data['questions'][$i]['answers'][$j]['answer_text']);
        $data['questions'][$i]['answers'][$j]['value'] = $myValue;
    }

    $data['questions'][$i]['question_no'] = $questionNo;
    ++$questionNo;
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('jsonQuiz', $jsonQuiz);
$smarty->assign('data', $data);
$smarty->assign('isVertical', $jsonQuiz['language_type'] == 1);
$smarty->assign('getScore', $jsonQuizResult['score']);
$smarty->assign('CAQuestion', $caQuestion);
$smarty->assign('isTeacher', $mode == 't' ? 1 : 0);
$smarty->assign('correctAnswerRates', json_decode($summary['correct_answer_rates_json'], true));
$smarty->assign('isIOS', OSDetectHelper::IsIOS());
$smarty->display('_quiz_result.html');
