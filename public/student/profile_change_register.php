<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Requests\RequestParamStudentChangePassword;
use IizunaLMS\Requests\RequestParamStudentChangeProfile;
use IizunaLMS\Students\ChangePassword;
use IizunaLMS\Students\ChangeProfile;

$params = [];
$errors = [];

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!StudentLoginController::IsLogin() || empty($_POST)) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}
else {
    $ChangeProfile = new ChangeProfile();

    $student = StudentLoginController::GetStudentData();

    $params = new RequestParamStudentChangeProfile();
    $errorCodes = $ChangeProfile->CheckValidateParameters($student->id, $params);

    if (empty($errorCodes)) {


        if (!$ChangeProfile->Update($student->id, $params))
        {
            $errorCodes[] = Error::ERROR_STUDENT_CHANGE_PROFILE_FAILED;
        }
        else {
            // ブラウザフラグは残したままセッションを更新する
            $updatedStudent = (new StudentModel())->GetById($student->id);

            if (StudentLoginController::IsApp()) {
                $updatedStudent[StudentLoginController::KEY_IS_APP] = true;
            }

            SessionHelper::SetLoginStudentData($updatedStudent);
        }
    }
}

$result = [];

if (empty($errorCodes)) {
    $result = [
        'result' => 'OK'
    ];
}
else {
    $errorMessage = implode('<br />', ErrorMessage::GetMessages($errorCodes));
    $result = [
        'error' => [
            'message' => $errorMessage
        ]
    ];
}

DisplayJsonHelper::ShowAndExit($result);