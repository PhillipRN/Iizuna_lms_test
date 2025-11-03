<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderController;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$JsonQuizFolderController = new OnigiriJsonQuizFolderController($teacher->school_id);
$folderListHtml = $JsonQuizFolderController->CreateFolderListHtml();
$folderListOptions = $JsonQuizFolderController->CreateFolderListOptions();

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('teacherId', $teacher->id);
$smarty->assign('folderListHtml', $folderListHtml);
$smarty->assign('folderListOptions', $folderListOptions);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz_folder.html');