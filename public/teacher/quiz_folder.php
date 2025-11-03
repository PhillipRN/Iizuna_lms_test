<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizFolderController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$JsonQuizFolderController = new JsonQuizFolderController($teacher->school_id);
$folderListHtml = $JsonQuizFolderController->CreateFolderListHtml();
$folderListOptions = $JsonQuizFolderController->CreateFolderListOptions();

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('teacherId', $teacher->id);
$smarty->assign('folderListHtml', $folderListHtml);
$smarty->assign('folderListOptions', $folderListOptions);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_quiz_folder.html');