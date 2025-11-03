<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$lmsTicketId = $_GET['ltid'] ?? null;

if (empty($lmsTicketId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);

$teacher = TeacherLoginController::GetTeacherData();

$record = (new LmsTicketLoader)->GetTicket($lmsTicketId);

if (empty($record)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_DATA_IS_NONE);
else if ($record['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_TEACHER_LMS_TICKET_NOT_PERMIT);

$groups = (new LmsTicketLoader)->GetTicketGroups($lmsTicketId);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('record', $record);
$smarty->assign('groups', $groups);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_lms_ticket_group.html');