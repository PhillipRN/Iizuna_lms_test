<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Teachers\TeacherLoader;
use IizunaLMS\Errors\Error;

if (!AdminLoginController::IsLogin()) {
    exit;
}

if (isset($_POST['login_id']))
{
    $loginId = $_POST['login_id'];
    $id = $_POST['id'];
    $result = [
        'result' => true
    ];

    $isRegistered = (new TeacherLoader())->IsRegisteredLoginId($loginId, $id);

    if ($isRegistered)
    {
        $result['result'] = false;
        $result['error'] = [
            'code' => Error::ERROR_TEACHER_REGISTERED_LOGIN_ID,
            'message' => ErrorMessage::GetMessage(Error::ERROR_TEACHER_REGISTERED_LOGIN_ID)
        ];
    }

    header('Content-Type: application/text');
    echo json_encode($result);
}
