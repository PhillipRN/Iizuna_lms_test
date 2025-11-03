<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\AppleHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Students\StudentHomeLoader;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    header('Location: ./login_error.php');
    exit;
}

$student = StudentLoginController::GetStudentData();

if ($student->is_change_password) {
    header('Location: ./change_password.php');
    exit;
}

$data = (new StudentHomeLoader())->GetData($student->id);

$isApplicationForApple = false;
if (StudentLoginController::IsApp() && AppleHelper::IsApplicationForApple())
{
    $isApplicationForApple = true;
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('studentName', $student->name);
$smarty->assign('data', $data);
$smarty->assign('isApp', StudentLoginController::IsApp());
$smarty->assign('enableLoginStudent', StudentLoginController::EnableLoginStudent());
$smarty->assign('isApplicationForApple', $isApplicationForApple);
$smarty->display('_index.html');