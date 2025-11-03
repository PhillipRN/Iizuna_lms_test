<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizData;
use IizunaLMS\Onigiri\OnigiriJsonQuiz;
use IizunaLMS\Schools\OnigiriLearningRangeLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['lms_code_id'])) {
    $params = RequestHelper::GetPostParams();

    $rangeData = (new OnigiriLearningRangeLoader($params['lms_code_id']))->LoadForOnigiriQuizCreatePage();

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'rangeData' => $rangeData
    ]);
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_ERROR);
}

