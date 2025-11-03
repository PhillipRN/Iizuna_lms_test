<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\EBook\Route\RouteController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;

if (empty($_GET['m'])) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_MODE_UNKNOWN);
}

RouteController::Routing($_GET['m']);