<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;

$AdminLoginController = new AdminLoginController();
$AdminLoginController->Logout();
header('Location: ./login.php');
exit;