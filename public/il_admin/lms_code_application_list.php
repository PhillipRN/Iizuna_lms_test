<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLmsCodeApplicationController;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$currentPage = (isset($_GET['page'])) ? $_GET['page'] : 1;

$Controller = new AdminLmsCodeApplicationController();
$records = $Controller->GetLmsCodeApplicationList($currentPage);
$maxPageNum = $Controller->GetMaxPageNum();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('records', $records);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNum', $maxPageNum);
$smarty->display('_lms_code_application_list.html');
