<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;

TeacherLoginController::Logout();

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->display('_logout.html');