<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\AppHelper;

$loginToken = $_GET['lt'] ?? null;

// 必要なパラメータがない場合はエラー
if (empty($loginToken))
{
    // パラメータがない場合はあえて何も出力しない
    exit;
}

$os = $_GET['os'] ?? 0;
$version = $_GET['v'] ?? 0;
AppHelper::SetAppOSAndVersion($os, $version);

$error = StudentLoginController::LoginByToken($loginToken);

if ($error == Error::ERROR_NONE)
{
    header('Location: ./index.php');
    exit;
}
else
{
    Error::ShowErrorJson($error);
}