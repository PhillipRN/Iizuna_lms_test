<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Students\DeleteStudent;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin())
{
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['student_id']))
{
    $teacher = TeacherLoginController::GetTeacherData();
    $result = (new DeleteStudent())->Delete($_POST['student_id'], $teacher->school_id);

    if ($result) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_DELETE_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_DELETE_PARAMETER_ERROR);
}
