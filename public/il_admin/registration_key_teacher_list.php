<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\RegistrationController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$currentPage = (isset($_GET['page'])) ? $_GET['page'] : 1;

$RegistrationController = new RegistrationController();
$registrationKeyList = $RegistrationController->GetUserList($currentPage);
$maxPageNum = $RegistrationController->GetUserListMaxPageNum();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('registrationKeyList', $registrationKeyList);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNum', $maxPageNum);
$smarty->display('_registration_key_teacher_list.html');
