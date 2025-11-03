<?php
require_once (__DIR__ . '/../app/bootstrap.php');

// 暫定で登録画面に飛ばす
header('Location: ./teacher_register.php');
exit;

use IizunaLMS\Controllers\LogController;
use IizunaLMS\Controllers\LoginController;
use IizunaLMS\Helpers\SmartyHelper;


$LoginController = new LoginController();
if (!$LoginController->IsLoginAndTryReLogin()) {
    header('Location: ./login.php');
    exit;
}

$userId = LoginController::GetUserId();

// ログ
$LogController = new LogController();
$LogController->AddLogWithType($userId, null, LOG_TYPE_ACCESS_TOP);

$smarty = SmartyHelper::GetSmarty();
$smarty->display('_index.html');
