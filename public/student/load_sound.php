<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Controllers\TeacherLoginController;

if ((!StudentLoginController::IsLogin() && !TeacherLoginController::IsLogin()) || empty($_GET['id'])) {
    http_response_code(403);
    exit;
}

$fileName = $_GET['id'] . '.mp3';
$filePath = __DIR__ . '/../../app/Assets/Sounds/' . $fileName;

header('Content-Type: audio/mpeg');
header('Content-Disposition:attachment;filename = "' . $fileName . '"');
header('Content-Length: '.filesize($filePath));
echo file_get_contents($filePath);
exit;