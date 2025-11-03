<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\SmartyHelper;

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('error', $_SESSION[DisplayErrorHelper::SESS_ERROR_MESSAGE]);
$smarty->display('_error.html');