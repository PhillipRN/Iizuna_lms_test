<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Schools\OnigiriLearningRangeRegister;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST["lms_code_id"])) {
    $data = RequestHelper::GetPostParams();

    (new OnigiriLearningRangeRegister($data['lms_code_id'], $data['learning_range']))->Update();

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK'
    ]);
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_LEARNING_RANGE_PARAMETER_ERROR);
}