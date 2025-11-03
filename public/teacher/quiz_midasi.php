<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\MidasiNoController;
use IizunaLMS\Controllers\TeacherLoginController;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST["titleNo"]))
{
    $MidasiNoController = new MidasiNoController();
    $result = $MidasiNoController->CreateMidasiNo($_POST["titleNo"]);

    header('Content-Type: application/text');
    echo json_encode($result);
}