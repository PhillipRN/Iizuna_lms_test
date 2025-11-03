<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

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

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録
if (isset($_POST['id'])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    $id = $params['id'];

    $record = (new LmsTicketApplicationViewModel)->GetById($id);

    $lmsTicketId = $record['lms_ticket_id'];
    $lmsTicketApplicationId = $record['id'];

    if (empty($record)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_INVALID_PARAMETER);

    // LMSチケットが削除されている場合はエラー
    else if ($record['lms_ticket_status'] >= LmsTicket::STATUS_DELETE_BY_TEACHER) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_ALREADY_DELETED);

    // LMSチケット申請がキャンセルされている場合はエラー
    else if ($record['lms_ticket_application_status'] >= LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_ALREADY_DELETED);

    // まだ無効状態のLMSチケットの場合は有効に変更する
    if ($record['lms_ticket_status'] == LmsTicket::STATUS_DISABLE)
    {
        $result = (new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_ENABLE);
        if (!$result)
        {
            PDOHelper::GetPDO()->rollBack();
            DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_UPDATE_FAILED);
        }
    }

    // 申請を承認する
    $result = (new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_APPROVED);

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_UPDATE_FAILED);
    }
}
else {
    PDOHelper::GetPDO()->rollBack();
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_INVALID_PARAMETER);
}