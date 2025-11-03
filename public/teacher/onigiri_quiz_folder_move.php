<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Onigiri\OnigiriJsonQuizMover;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['quiz_id']))
{
    $quizId = $_POST['quiz_id'];
    $parentFolderId = $_POST['parent_folder_id'];

    $teacher = TeacherLoginController::GetTeacherData();
    $result = (new OnigiriJsonQuizMover())->MoveQuiz($teacher->id, $quizId, $parentFolderId);

    if (empty($result['error'])) {
        DisplayJsonHelper::ShowAndExit($result);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_MOVE_QUIZ_INVALID_PARAMETER);
}