<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();
$records = (new LmsTicketLoader())->GetTicketList($teacher->id);
$ticketData = (new LmsTicketLoader())->GetTeachersTicketHierarchy($teacher->id);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('ticketTypes', $ticketTypes);
$smarty->assign('currentYear', date('Y'));
$smarty->assign('records', $records);
$smarty->assign('ticketData', $ticketData);
$smarty->assign('ticketDataJson', json_encode($ticketData));
$smarty->assign('onigiriTitleNo', LmsTicket::TITLE_NO_ONIGIRI);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_lms_ticket.html');