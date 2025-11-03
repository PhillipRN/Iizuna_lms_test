<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLmsCodeApplicationController;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$id = $_GET['id']?? 0;
$backPage = $_GET['backPage']?? 0;

if (empty($id)) {
    header('Location: ./lms_code_application_list.php');
    exit;
}

$Controller = new AdminLmsCodeApplicationController();
$record = $Controller->Get($id);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('record', $record);
$smarty->assign('backPage', $backPage);
$smarty->display('_lms_code_application_update.html');
