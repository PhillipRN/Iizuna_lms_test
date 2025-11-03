<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Admin\LmsTickets\AdminLmsTicketRegister;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketApplicationRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketApplicationViewModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録
if (isset($_POST['teacher_id']) || isset($_POST['school_id'])) {
    $teacherId = $_POST['teacher_id'] ?? null;
    $schoolId = $_POST['school_id'] ?? null;
    $params = new RequestParamLmsTicketApplication();

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    $result = (!empty($teacherId))
            ? (new AdminLmsTicketRegister())->GrantLmsTicket($params, $teacherId)
            : (new AdminLmsTicketRegister())->GrantLmsTicketForSchool($params, $schoolId);

    if ($result == Error::ERROR_NONE) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_REGISTER_FAILED);
    }
}
else {
    PDOHelper::GetPDO()->rollBack();
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_INVALID_PARAMETER);
}