<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Firebase\EBookUser;
use IizunaLMS\Firebase\OnigiriUser;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\LmsTickets\LmsTicket;

if (!TeacherLoginController::IsLogin()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);

if (isset($_POST['lms_code']))
{
    $lmsCode = $_POST['lms_code'];
    $titleNo = $_POST['title_no'];

    $records = [];

    if ($titleNo == LmsTicket::TITLE_NO_ONIGIRI)
    {
        $results = (new OnigiriUser())->GetOnigiriUserData($lmsCode);
        foreach ($results as $result)
        {
            $records[] = [
                'fullName' => $result['name'],
                'studentNumber' => $result['studentNumber'],
                'loginId' => '-',
                'activeSchoolCodes' => [$lmsCode]
            ];
        }
    }
    else
    {
        $records = (new EBookUser())->GetUserData($lmsCode);
    }

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'records' => $records
    ]);
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_USER_GET_INVALID_PARAMETER);
}