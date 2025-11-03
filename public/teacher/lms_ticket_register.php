<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

$teacher = TeacherLoginController::GetTeacherData();

if (isset($_POST["title_no"])) {
    $result = false;

    $params = new RequestParamLmsTicketApplication();

    PDOHelper::GetPDO()->beginTransaction();

    $error = (new LmsTicketRegister())->AddNewLmsTicket($teacher->id, $teacher->school_id, $params);

    if ($error == Error::ERROR_NONE) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit($error);
    }
}
else if (isset($_POST['ltid'])) {
    $lmsTicketId = $_POST['ltid'];

    $params = new RequestParamLmsTicketApplication();
    $params->lms_ticket_id = $lmsTicketId;

    PDOHelper::GetPDO()->beginTransaction();

    $error = (new LmsTicketRegister())->AddApplication($params);

    if ($error == Error::ERROR_NONE) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit($error);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED);
}