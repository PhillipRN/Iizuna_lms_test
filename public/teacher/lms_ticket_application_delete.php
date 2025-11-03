<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketApplicationRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketApplicationViewModel;

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['ltaid']))
{
    $lmsTicketApplicationId = $_POST['ltaid'];

    $teacher = TeacherLoginController::GetTeacherData();

    $record = (new LmsTicketApplicationViewModel())->GetById($lmsTicketApplicationId);

    if (empty($record)) DisplayJsonHelper::ShowAndExit(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_DATA_IS_NONE);
    else if ($record['teacher_id'] != $teacher->id) DisplayJsonHelper::ShowAndExit(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_NOT_PERMIT);

    if ((new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER)) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_DELETE_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_DELETE_INVALID_PARAMETER);
}