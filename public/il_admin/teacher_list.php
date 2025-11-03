<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$isRegistered = false;
$isDeleted = false;

if (isset($_SESSION[SESS_RESIST_STATUS]))
{
    switch ($_SESSION[SESS_RESIST_STATUS])
    {
    case REGISTER_STATUS_REGISTERED:
        $isRegistered = true;
        break;

    case REGISTER_STATUS_DELETED:
        $isDeleted = true;
        break;
    }

    unset($_SESSION[SESS_RESIST_STATUS]);
}

$AdminUserController = new AdminTeacherController();
$teacherList = [];
$isEOnigiri = $_GET['e_onigiri'] ?? 0;

if ($isEOnigiri) {
    $teacherList = $AdminUserController->GetOnigiriTeachers();
} else {
    $teacherList = $AdminUserController->GetTeachers();
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('teacherList', $teacherList);
$smarty->assign('isRegistered', $isRegistered);
$smarty->assign('isDeleted', $isDeleted);
$smarty->assign('isEOnigiri', $isEOnigiri);
$smarty->display('_teacher_list.html');

