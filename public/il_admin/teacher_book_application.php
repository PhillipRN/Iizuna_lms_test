<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherBookApplicationController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$currentPage = (isset($_GET['page'])) ? $_GET['page'] : 1;

$AdminTeacherBookApplicationController = new AdminTeacherBookApplicationController();
$records = $AdminTeacherBookApplicationController->Gets($currentPage);
$maxPageNum = $AdminTeacherBookApplicationController->GetMaxPageNum();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('records', $records);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNum', $maxPageNum);
$smarty->display('_teacher_book_application.html');
