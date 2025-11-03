<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\SmartyHelper;

StudentLoginController::Logout();

$smarty = SmartyHelper::GetSmarty();
$smarty->display('_logout.html');