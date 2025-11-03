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
if (isset($_POST["quiz_id"])) {
    $params = RequestHelper::GetPostParams();
    $quizId = $params['quiz_id'];

    $originalRecord = (new OnigiriJsonQuizModel())->GetAndResultNumById($quizId);

    if (empty($originalRecord))
    {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_NO_DATA);
    }

    if (empty($params['title'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_TITLE);
    }

    // パラメータ設定
    $isManual = $originalRecord['is_manual_mode'];

    $originalJsonData = json_decode($originalRecord['json'], true);

    $params['lms_code_id'] = $originalRecord['range_lms_code_id'];
    $params['type'] = $originalRecord['type'];
    $params['total'] = $originalRecord['total'];
    $params['time_limit'] = $originalRecord['time_limit'];
    $params['ranges'] = $originalRecord['ranges'];

    $stages = [];

    if (!empty($params['ranges']))
    {
        $stages = explode(',', $params['ranges']);
    }

    $params['word_ids'] = [];
    $params['types'] = [];

    foreach ($originalJsonData as $quiz)
    {
        $params['word_ids'][] = $quiz['id'];
        $params['types'][] = $quiz['type'];
    }

    $quizData = null;

    // マニュアルモード
    if ($isManual) {
        $quizData = (new OnigiriJsonQuiz())->CreateManualMode($params['word_ids'], $params['types'], $params['open_date'], $params['expire_date']);
    }

    // おまかせモード
    else if (isset($params["lms_code_id"])) {

        $record = (new LmsCodeModel())->GetById($params['lms_code_id']);
        $type = $params['type'] ?? 'random';

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
        $onigiriJsonQuizData = new OnigiriJsonQuizData([
            'teacher_id' => $teacher->id,
            'range_lms_code_id' => $params['lms_code_id'],
            'ranges' => $params['ranges'],
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