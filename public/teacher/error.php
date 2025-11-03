<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;

if (empty($_SESSION[DisplayErrorHelper::SESS_ERROR_MESSAGE]))
{
    header('Location: ./index.php');
    exit;
}

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->assign('error', $_SESSION[DisplayErrorHelper::SESS_ERROR_MESSAGE]);
$smarty->display('_error.html');