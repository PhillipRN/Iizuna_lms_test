<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderDeleter;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['folder_id']))
{
    $teacher = TeacherLoginController::GetTeacherData();
    $result = (new OnigiriJsonQuizFolderDeleter())->DeleteById($teacher->id, $_POST['folder_id']);

    if (empty($result['error'])) {
        DisplayJsonHelper::ShowAndExit($result);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_DELETE_NOT_PERMIT);
}