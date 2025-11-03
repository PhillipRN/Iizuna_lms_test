<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;

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

    $originalRecord = (new OnigiriJsonQuizModel())->GetById($quizId);

    if (empty($originalRecord))
    {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_NO_DATA);
    }

    if (empty($params['title'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_TITLE);
    }

    if (!empty($params['open_date']) && !empty($params['expire_date']))
    {
        $date1 = new \DateTime($params['open_date']);
        $date2 = new \DateTime($params['expire_date']);

        if ($date1 >= $date2) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_INVALID_TERMS);
    }

    $teacher = TeacherLoginController::GetTeacherData();

    $originalRecord['title'] = $params['title'];
    $originalRecord['teacher_id'] = $teacher->id;
    $originalRecord['open_date'] = (!empty($params['open_date'])) ? $params['open_date'] : PeriodHelper::PERIOD_OPEN_DATE;
    $originalRecord['expire_date'] = (!empty($params['expire_date'])) ? $params['expire_date'] : PeriodHelper::PERIOD_EXPIRE_DATE;

    if (isset ($originalRecord['id'])) unset($originalRecord['id']);
    if (isset ($originalRecord['create_date'])) unset($originalRecord['create_date']);
    if (isset ($originalRecord['update_date'])) unset($originalRecord['update_date']);

    PDOHelper::GetPDO()->beginTransaction();
    $result = (new OnigiriJsonQuizModel())->Add($originalRecord);

    if (!$result) {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_RECORD_FAILED);
    }

    PDOHelper::GetPDO()->commit();
    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK'
    ]);
}

DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_PARAMETER_ERROR);