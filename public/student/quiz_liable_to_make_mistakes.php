<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizStringHelper;

const LIABLE_THRESHOLD = 50;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    Error::ShowErrorJson(Error::ERROR_STUDENT_ACCESS_NOT_LOGIN);
    exit;
}

$quizId = $_GET['quiz_id'] ?? '';
if (empty($quizId)) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_INVALID_URL);

if (!empty($assignment['errors'])) DisplayErrorHelper::RedirectStudentErrorPage(ERROR_JSON_QUIZ_PERMISSION);

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->Get($quizId);
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

$correctAnswerRates = json_decode($summary['correct_answer_rates_json'], true);

// 間違えやすい問題集計
$questions = [];

// まず有効な大問を集計するために一回回す
$validDaimonNo = [];
foreach ($data['questions'] as $question)
{
    if ($question['question_type'] == 'page_break_item') continue;

    $questionId = $question['question_id'];

    if (isset($correctAnswerRates[$questionId]) && $correctAnswerRates[$questionId] >= LIABLE_THRESHOLD) continue;

    $validDaimonNo[$question['daimon_no']] = 1;
}

// 有効な大問と問題を詰める
foreach ($data['questions'] as $question)
{
    if ($question['question_type'] == 'page_break_item' &&
        !isset( $validDaimonNo[$question['daimon_no']] )
    ) continue;

    if ($question['question_type'] != 'page_break_item' &&
        isset($correctAnswerRates[ $question['question_id'] ]) &&
        $correctAnswerRates[ $question['question_id'] ] >= LIABLE_THRESHOLD
    ) continue;

    $questions[] = $question;
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('quiz_id', $quizId);
$smarty->assign('title', $jsonQuiz['title']);
$smarty->assign('questions', $questions);
$smarty->assign('isVertical', $jsonQuiz['language_type'] == 1);
$smarty->assign('correctAnswerRates', json_decode($summary['correct_answer_rates_json'], true));
$smarty->display('_quiz_liable_to_make_mistakes.html');