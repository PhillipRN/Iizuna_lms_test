<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\StudentCodeViewModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

if (!$teacher->is_juku) {
    header('Location: ./index.php');
    exit;
}

$currentPage = $_GET['page'] ?? 1;

$StudentCodeModel = new StudentCodeViewModel();
$records = $StudentCodeModel->GetsByTeacherId($teacher->id, $currentPage);
$maxPageNumber = $StudentCodeModel->GetMaxPageNumber($teacher->id);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNumber', $maxPageNumber);
$smarty->display('_student_code.html');