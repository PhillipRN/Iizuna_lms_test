<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\JsonQuizzes\JsonQuizMover;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['parent_folder_id']))
{
    $parentFolderId = $_POST['parent_folder_id'];
    $quizIds = $_POST['quiz_ids'];

    $teacher = TeacherLoginController::GetTeacherData();
    $JsonQuizMover = new JsonQuizMover();

    if (empty($quizIds))
    {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_FOLDER_BULK_MOVE_QUIZ_INVALID_PARAMETER);
    }

    $result = $JsonQuizMover->BulkMove($teacher->id, $quizIds, $parentFolderId);

    if (empty($result['error'])) {
        DisplayJsonHelper::ShowAndExit($result);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_FOLDER_BULK_MOVE_QUIZ_INVALID_PARAMETER);
}