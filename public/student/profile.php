<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Students\StudentLmsCodeLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    header('Location: ./login_error.php');
    exit;
}

$student = StudentLoginController::GetStudentData();
$lmsCodes = (new StudentLmsCodeLoader())->GetsByStudentId($student->id);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('data', $student);
$smarty->assign('lmsCodes', $lmsCodes);
$smarty->display('_profile.html');