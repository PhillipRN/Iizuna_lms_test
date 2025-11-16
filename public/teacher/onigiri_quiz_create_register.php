<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizData;
use IizunaLMS\Onigiri\OnigiriJsonQuiz;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

$isManual = $_POST["manual"] ?? 0;

if (isset($_POST["time_limit"])) {
    $params = RequestHelper::GetPostParams();

    if (empty($params['title'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_TITLE);
    }

    $quizData = null;

    // マニュアルモード
    if ($isManual) {
        $quizData = (new OnigiriJsonQuiz())->CreateManualMode($params['word_ids'], $params['types'], $params['open_date'], $params['expire_date']);
    }

    // おまかせモード
    else if (isset($_POST["lms_code_id"])) {

        $record = (new LmsCodeModel())->GetById($params['lms_code_id']);

        $type = $params['type'] ?? 'random';
        $stageRanges = $params['stage'] ?? [];
        $stages = [];

        foreach ($stageRanges as $stageRange) {
            $tmpData = explode('#', $stageRange);

            if (isset($tmpData[0])) {
                $stages[] = $tmpData[0];
            }
        }

        $quizData = (new OnigiriJsonQuiz())->Create($record['lms_code'], $stages, $params['total'], $type, $params['open_date'], $params['expire_date']);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_ERROR);
    }

    // Note: error の形式で中身が返ってきているので、そのまま出力する
    if (!empty($quizData['error'])) DisplayJsonHelper::ShowAndExit($quizData);

    $json = json_encode($quizData);

    // json にパース失敗している場合はエラー
    if (empty($json)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_RECORD_FAILED);

    $teacher = TeacherLoginController::GetTeacherData();
    $onigiriJsonQuizData = null;

    if ($isManual) {
        $onigiriJsonQuizData = new OnigiriJsonQuizData([
            'parent_folder_id' => $params['parent_folder_id'],
            'teacher_id' => $teacher->id,
            'title' => $params['title'],
            'json' => $json,
            'total' => count($params['word_ids']),
            'open_date' => $params['open_date'],
            'expire_date' => $params['expire_date'],
            'time_limit' => $params['time_limit'],
            'is_manual_mode' => 1
        ]);
    }
    else {
//        $range_stage = implode('_', $params['stage']);

        $stageRanges = $params['stage'] ?? [];
        $ranges = [];

        foreach ($stageRanges as $stageRange) {
            $tmpData = explode('#', $stageRange);

            if (isset($tmpData[1])) {
                $ranges[] = $tmpData[1];
            }
        }

        $onigiriJsonQuizData = new OnigiriJsonQuizData([
            'parent_folder_id' => $params['parent_folder_id'],
            'teacher_id' => $teacher->id,
            'range_lms_code_id' => $params['lms_code_id'],
            'ranges' => implode(',', $ranges),
            'title' => $params['title'],
            'type' => $params['type'],
            'json' => $json,
            'total' => $params['total'],
            'open_date' => $params['open_date'],
            'expire_date' => $params['expire_date'],
            'time_limit' => $params['time_limit'],
        ]);
    }

    $result = false;

    if (empty($params['id']))
    {
        $result = (new OnigiriJsonQuizModel())->Add($onigiriJsonQuizData);
    }
    else
    {
        $onigiriJsonQuizData->id = $params['id'];
        $result = (new OnigiriJsonQuizModel())->Update($onigiriJsonQuizData);
    }

    if (!$result) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_RECORD_FAILED);
    }

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK'
    ]);
}

DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_ERROR);
