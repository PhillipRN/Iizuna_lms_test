<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizSearchParams;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderController;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;
use IizunaLMS\Onigiri\OnigiriJsonQuizResultLoader;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$currentPage = (!empty($_GET['page'])) ? $_GET['page'] : 1;
$searchParams = new OnigiriJsonQuizSearchParams($_GET);

$OnigiriJsonQuizLoader = new OnigiriJsonQuizLoader();
$records = $OnigiriJsonQuizLoader->GetsBySchoolId($teacher->school_id, $currentPage, PageHelper::PAGE_LIMIT, $searchParams);
$maxPageNumber = $OnigiriJsonQuizLoader->GetMaxPageNumberBySchoolId($teacher->school_id, PageHelper::PAGE_LIMIT, $searchParams);

// LMSコード
$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$currentLmsCodeId = $_GET['lcid'] ?? null;

if (empty($currentLmsCodeId)) {
    foreach ($schoolGroups as $schoolGroup) {
        if (!empty($schoolGroup['is_school'])) {
            $currentLmsCodeId = $schoolGroup['lms_code_id'];
            break;
        }
    }
}

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name']
    ];
}

$currentGroupName = null;
foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $currentGroupName = $schoolGroup['name'];
        break;
    }
}

// Result
$OnigiriJsonQuizResultLoader = new OnigiriJsonQuizResultLoader();
$maxResultPageNumber = $OnigiriJsonQuizResultLoader->GetMaxPageNumber($currentLmsCodeId);

$currentResultPage = $_GET['resultPage'] ?? 1;
$resultRecords = $OnigiriJsonQuizResultLoader->GetResultPageData($currentLmsCodeId, $currentResultPage);

$teachers = (new TeacherController())->GetSchoolTeachers($teacher->school_id);

// フォルダ取得
$JsonQuizFolderController = new OnigiriJsonQuizFolderController($teacher->school_id);
$currentFolder = $JsonQuizFolderController->GetFolder($searchParams->parent_folder_id);
$folderListHtml = $JsonQuizFolderController->CreateFolderListHtml(true);
$childrenFolders = $JsonQuizFolderController->GetChildrenFolders($currentFolder['id']);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('teacherId', $teacher->id);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->assign('currentGroupName', $currentGroupName);
$smarty->assign('records', $records);
$smarty->assign('resultRecords', $resultRecords);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('currentResultPage', $currentResultPage);
$smarty->assign('maxPageNumber', $maxPageNumber);
$smarty->assign('maxResultPageNumber', $maxResultPageNumber);
$smarty->assign('searchParams', $searchParams);
$smarty->assign('teachers', $teachers);
$smarty->assign('currentFolder', $currentFolder);
$smarty->assign('folderListHtml', $folderListHtml);
$smarty->assign('childrenFolders', $childrenFolders);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz.html');