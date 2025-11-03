<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizDeliveryData;
use IizunaLMS\Onigiri\OnigiriJsonQuizDeleter;
use IizunaLMS\Onigiri\OnigiriJsonQuizDelivery;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// TODO 自分の作成したクイズかどうかチェック

if (isset($_POST['quiz_id']) && isset($_POST['lms_code_id']))
{
    $OnigiriJsonQuizDeliveryData = new OnigiriJsonQuizDeliveryData([
        'onigiri_json_quiz_id' => $_POST['quiz_id'],
        'lms_code_id' => $_POST['lms_code_id']
    ]);

    $result = (new OnigiriJsonQuizDelivery())->DeleteNoticeOnly($_POST['quiz_id'], $_POST['lms_code_id']);

    if (empty($result['error'])) {
        DisplayJsonHelper::ShowAndExit($result);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_DELETE_INVALID_PARAMETER);
}