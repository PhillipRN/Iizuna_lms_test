<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Helpers\SmartyHelper;

unset($_SESSION[SESS_RESIST_STATUS]);

$smarty = SmartyHelper::GetSmarty();
$smarty->display('_teacher_register_result.html');
