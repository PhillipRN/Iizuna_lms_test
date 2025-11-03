<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\Models\LmsTicketGroupModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Requests\RequestParamLmsTicketGroup;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (isset($_POST['lms_ticket_id'])) {
    $params = new RequestParamLmsTicketGroup();

    PDOHelper::GetPDO()->beginTransaction();

    $error = (new LmsTicketGroupRegister())->CheckAndCreateLmsCodeAndAdd($params);

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
else if (isset($_POST['ltgid'])) {
    $lmsTicketGroupId = $_POST['ltgid'];

    $teacher = TeacherLoginController::GetTeacherData();

    $record = (new LmsTicketGroupViewModel())->GetById($lmsTicketGroupId);

    if (empty($record)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_GROUP_DATA_IS_NONE);
    else if ($record['teacher_id'] != $teacher->id) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_GROUP_NOT_PERMIT);

    $params = new RequestParamLmsTicketGroup();
    $params->lms_ticket_id = $record['lms_ticket_id'];
    $errors = (new LmsTicketGroupRegister())->CheckValidateUpdateParameters($lmsTicketGroupId, $record, $params);
    if (!empty($errors)) DisplayJsonHelper::ShowErrorAndExit($errors[0]);

    PDOHelper::GetPDO()->beginTransaction();

    $data = $params->ToArray();
    $data['id'] = $lmsTicketGroupId;

    $result = (new LmsTicketGroupModel())->Update($data);

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_GROUP_ADD_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER);
}