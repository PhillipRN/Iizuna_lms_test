<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Controllers\LoginController;

$LoginController = new LoginController();
$LoginController->Logout();
header('Location: ./login.php');
exit;