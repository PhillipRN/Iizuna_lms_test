<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\ChapterController;
use IizunaLMS\Controllers\TeacherLoginController;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST["titleNo"]))
{
    $ChapterController = new ChapterController();
    $result = $ChapterController->CreateChapter($_POST["titleNo"]);

    header('Content-Type: application/text');
    echo json_encode($result);
}