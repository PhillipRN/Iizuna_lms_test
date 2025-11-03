<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Firebase\EBookUser;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;

if (!TeacherLoginController::IsLogin()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);

if (isset($_POST['login_id']) && isset($_POST['lms_code']))
{
    $loginId = $_POST['login_id'];
    $lmsCode = $_POST['lms_code'];

    // Firebase から削除
    $responseCode = (new EBookUser())->DeleteUserCode($loginId, $lmsCode);

    $result = [];

    if ($responseCode == 200)
    {
        // カウントダウン出来たらする
        (new LmsTicketGroupRegister())->TryCountDownByLmsCode($lmsCode);

        $result = ['result' => 'OK'];
    }
    else
    {
        $result = [
            'error' => [
                'code' => $responseCode,
                'message' => 'エラーが発生しました。'
            ]
        ];
    }
    DisplayJsonHelper::ShowAndExit($result);
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_USER_DELETE_INVALID_PARAMETER);
}