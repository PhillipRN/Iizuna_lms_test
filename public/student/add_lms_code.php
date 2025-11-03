<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!StudentLoginController::IsLoginOrTryAutoLogin()) {
    header('Location: ./login_error.php');
    exit;
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('isApp', StudentLoginController::IsApp());
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_add_lms_code.html');