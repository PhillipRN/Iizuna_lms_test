<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketModel;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['ltid']))
{
    $lmsTicketId = $_POST['ltid'];

    $teacher = TeacherLoginController::GetTeacherData();

    $record = (new LmsTicketModel())->GetById($lmsTicketId);

    if (empty($record)) DisplayJsonHelper::ShowAndExit(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);
    else if ($record['teacher_id'] != $teacher->id) DisplayJsonHelper::ShowAndExit(Error::ERROR_TEACHER_LMS_TICKET_NOT_PERMIT);

    if ((new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_DELETE_BY_TEACHER)) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_DELETE_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_DELETE_INVALID_PARAMETER);
}