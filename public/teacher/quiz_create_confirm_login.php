<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$json = json_decode('{"questions":[{"question_type":"page_break_item","question_text":"これはログイン確認用のテストです。","pick_count":1},{"question_text":"ログインできていますか？","answers":[{"answer_text":"はい","weight":100}],"points_possible":1,"question_id":1,"question_type":"multiple_choice_question","other_answers":[]}],"total":1}', true);

$teacher = TeacherLoginController::GetTeacherData();

$params = [
    'titleNo' => 0,
    'total' => 1,
    'time_limit' => 1
];

$result = (new JsonQuizController())->Add(
    $teacher->id,
    'ログイン確認テスト',
    $params,
    0,
    $json
);

if (empty($result['error'])) {
    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK'
    ]);
}
else {
    DisplayJsonHelper::ShowErrorAndExit($result['error']);
}
