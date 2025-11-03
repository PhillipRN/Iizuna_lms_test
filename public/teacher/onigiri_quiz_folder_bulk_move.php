<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizSearchParams;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderController;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$currentPage = (!empty($_GET['page'])) ? $_GET['page'] : 1;
$searchParams = new OnigiriJsonQuizSearchParams($_GET);

$limit = 1000;

$OnigiriJsonQuizLoader = new OnigiriJsonQuizLoader();
$records = $OnigiriJsonQuizLoader->GetsBySchoolId($teacher->school_id, $currentPage, $limit, $searchParams);
$maxPageNumber = $OnigiriJsonQuizLoader->GetMaxPageNumberBySchoolId($teacher->school_id, $limit, $searchParams);

// LMSコード
$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$teachers = (new TeacherController())->GetSchoolTeachers($teacher->school_id);

// フォルダ取得
$JsonQuizFolderController = new OnigiriJsonQuizFolderController($teacher->school_id);
$currentFolder = $JsonQuizFolderController->GetFolder($searchParams->parent_folder_id);
$folderListHtml = $JsonQuizFolderController->CreateFolderListHtml(true);
$childrenFolders = $JsonQuizFolderController->GetChildrenFolders($currentFolder['id']);
$folderListOptions = $JsonQuizFolderController->CreateFolderListOptions($searchParams->parent_folder_id);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('teacherId', $teacher->id);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('records', $records);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNumber', $maxPageNumber);
$smarty->assign('searchParams', $searchParams);
$smarty->assign('teachers', $teachers);
$smarty->assign('currentFolder', $currentFolder);
$smarty->assign('folderListHtml', $folderListHtml);
$smarty->assign('childrenFolders', $childrenFolders);
$smarty->assign('folderListOptions', $folderListOptions);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz_folder_bulk_move.html');