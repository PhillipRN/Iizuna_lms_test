<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketGroup;
use IizunaLMS\Models\LmsTicketApplicationModel;
use IizunaLMS\Models\LmsTicketGroupModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\LmsTicketModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$lmsTicketGroupId = $_GET['ltgid'] ?? null;

if (empty($lmsTicketGroupId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_GROUP_DATA_IS_NONE);

$teacher = TeacherLoginController::GetTeacherData();

$record = (new LmsTicketGroupViewModel())->GetById($lmsTicketGroupId);

if (empty($record)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_GROUP_DATA_IS_NONE);
else if ($record['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_GROUP_NOT_PERMIT);

if ($record['status'] >= LmsTicketGroup::STATUS_DELETE_BY_TEACHER) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_GROUP_DATA_IS_NONE);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('record', $record);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_lms_ticket_group_update.html');