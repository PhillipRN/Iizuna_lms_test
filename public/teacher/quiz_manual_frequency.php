<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\QuestionController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\RequestHelper;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST["titleNo"]))
{
    $params = RequestHelper::GetPostParams();
    $titleNo = $params["titleNo"];

    $QuestionController = new QuestionController();
    $result = $QuestionController->GetFrequencyData($titleNo);

    header('Content-Type: application/text');
    echo json_encode($result);
}