<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\JsonQuizFolderController;
use IizunaLMS\Controllers\TeacherController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Datas\JsonQuizSearchParams;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizLoader;
use IizunaLMS\Teachers\TeacherLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$data = (new TeacherLoader())->GetById($teacher->id);

$currentPage = (!empty($_GET['page'])) ? $_GET['page'] : 1;
$searchParams = new JsonQuizSearchParams($_GET);

$JsonQuizLoader = new JsonQuizLoader();
$records = $JsonQuizLoader->GetsBySchoolId($teacher->school_id, $currentPage, PageHelper::PAGE_LIMIT, $searchParams);
$maxPageNumber = $JsonQuizLoader->GetMaxPageNumberBySchoolId($teacher->school_id, PageHelper::PAGE_LIMIT, $searchParams);

// 書籍リスト取得
$BookLoader = new BookLoader();
$bookList = $BookLoader->GetAvailableBookList($teacher->id);

$teachers = (new TeacherController())->GetSchoolTeachers($teacher->school_id);

// フォルダ取得
$JsonQuizFolderController = new JsonQuizFolderController($teacher->school_id);
$currentFolder = $JsonQuizFolderController->GetFolder($searchParams->parent_folder_id);
$folderListHtml = $JsonQuizFolderController->CreateFolderListHtml(true);
$childrenFolders = $JsonQuizFolderController->GetChildrenFolders($currentFolder['id']);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->assign('data', $data);
$smarty->assign('teacherId', $teacher->id);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNumber', $maxPageNumber);
$smarty->assign('searchParams', $searchParams);
$smarty->assign('bookList', $bookList);
$smarty->assign('teachers', $teachers);
$smarty->assign('currentFolder', $currentFolder);
$smarty->assign('folderListHtml', $folderListHtml);
$smarty->assign('childrenFolders', $childrenFolders);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_index.html');