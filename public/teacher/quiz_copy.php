<?php
global $quizId;
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Controllers\TestController;
use IizunaLMS\Datas\JsonQuizOption;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\JsonQuizOptionModel;
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

    if (empty($params['quiz_id'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_COPY_INVALID_PARAMETER);
    }

    $teacher = TeacherLoginController::GetTeacherData();
    $quizId = $params['quiz_id'];

    PDOHelper::GetPDO()->beginTransaction();

    $JsonQuizController = new JsonQuizController();
    $result = $JsonQuizController->Copy($quizId, $params, $teacher->id);

    if ($result['error'] == Error::ERROR_NONE)
    {
        PDOHelper::GetPDO()->commit();
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else
    {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}

DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_COPY_INVALID_PARAMETER);