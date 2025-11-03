<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\LmsTicketApplicationModel;
use IizunaLMS\Models\LmsTicketModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$lmsTicketId = $_GET['ltid'] ?? null;

if (empty($lmsTicketId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);

$teacher = TeacherLoginController::GetTeacherData();

$record = (new LmsTicketModel)->GetById($lmsTicketId);

if (empty($record)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);
else if ($record['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_NOT_PERMIT);

if ($record['status'] >= LmsTicket::STATUS_DELETE_BY_TEACHER) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);

$ticketType = (new LmsTicket())->GetTicketType($record['title_no']);
$record['name'] = $ticketType['name'];
$record['quantity'] = 0;

$applicationRecords = (new LmsTicketApplicationModel())->GetUndeletedApplicationList($lmsTicketId);

foreach ($applicationRecords as $applicationRecord) {
    $record['quantity'] += $applicationRecord['quantity'];
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('record', $record);
$smarty->assign('applicationRecords', $applicationRecords);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_lms_ticket_update.html');