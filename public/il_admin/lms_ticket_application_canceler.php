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

if (isset($_POST['ltaid']))
{
    $lmsTicketApplicationId = $_POST['ltaid'];

    $record = (new LmsTicketApplicationViewModel())->GetById($lmsTicketApplicationId);

    if (empty($record)) DisplayJsonHelper::ShowAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_NO_DATA);

    // 申請を取り消す
    if ((new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_CANCELLED_BY_ADMINISTRATOR)) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_UPDATE_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_INVALID_PARAMETER);
}