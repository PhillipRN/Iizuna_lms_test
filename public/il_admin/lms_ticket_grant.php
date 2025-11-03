<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Admin\LmsTickets\AdminLmsTicketLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherBookApplicationController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\SchoolViewModel;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$backApplicationListPage = $_GET['back_application_list_page'] ?? null;
$schoolId = $_GET['school_id'] ?? null;

$ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();

$school = [];
if (!empty($schoolId)) {
    $school = (new SchoolViewModel())->GetById($schoolId);
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('ticketTypes', $ticketTypes);
$smarty->assign('currentYear', date('Y'));
$smarty->assign('backApplicationListPage', $backApplicationListPage);
$smarty->assign('school', $school);
$smarty->display('_lms_ticket_grant.html');
