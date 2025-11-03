<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    header('Location: ./login_error.php');
    exit;
}

$student = StudentLoginController::GetStudentData();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('isChangePassword', $student->is_change_password);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_change_password.html');