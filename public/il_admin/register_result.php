<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$statuses = array();

if (isset($_SESSION[SESS_RESIST_STATUS]))
{
    if (is_array($_SESSION[SESS_RESIST_STATUS]))
    {
        // statusesを1次元配列に変更
        foreach ($_SESSION[SESS_RESIST_STATUS] as $row => $rowStatus)
        {
            $prefix = "(" . ($row + 1) . "人目) ";
            $statuses[] = $prefix . MessageHelper::GetMessage($rowStatus);
        }
    }
    else
    {
        $statuses[] = MessageHelper::GetMessage($_SESSION[SESS_RESIST_STATUS]);
    }

    unset($_SESSION[SESS_RESIST_STATUS]);
}

$AdminUserController = new AdminTeacherController();
$userList = $AdminUserController->GetTeachers();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('statuses', $statuses);
$smarty->display('_register_result.html');

