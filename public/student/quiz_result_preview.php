<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\OSDetectHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizStringHelper;
use IizunaLMS\Models\TeacherModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$jsonQuizId = $_GET['quiz_id'] ?? 0;

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->GetQuizById($jsonQuizId);

$jsonQuizOwner = (new TeacherModel())->GetById($jsonQuiz['teacher_id']);

$teacher = TeacherLoginController::GetTeacherData();
if ($teacher->school_id != $jsonQuizOwner['school_id']) {
    echo "error";
    DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_JSON_QUIZ_STUDENT_RESULT_ACCESS_ERROR);
    exit;
}

$data = json_decode($jsonQuiz['json'], true);

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

    // answer_text 内のタグを取り除く
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
//$smarty->assign('getScore', $jsonQuizResult['score']);
//$smarty->assign('CAQuestion', $caQuestion);
//$smarty->assign('isTeacher', $mode == 't' ? 1 : 0);
$smarty->assign('correctAnswerRates', json_decode($summary['correct_answer_rates_json'], true));
$smarty->assign('isIOS', OSDetectHelper::IsIOS());
$smarty->display('_quiz_result_preview.html');