<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Students\StudentLmsCodeLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    header('Location: ./login_error.php');
    exit;
}

$student = StudentLoginController::GetStudentData();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->assign('data', $student);
$smarty->display('_profile_change.html');