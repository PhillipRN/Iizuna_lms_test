<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Admin\LmsTickets\AdminLmsTicketLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\EBook\EbookSchool;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\SchoolViewModel;
use IizunaLMS\Schools\School;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$isUpdate = (isset($_GET['id']));
$school = [];
$ebookStatuses = [];
$ticketRecords = [];
$ticketGroupRecords = [];

if ($isUpdate) {
    $record = (new SchoolViewModel())->GetById($_GET['id']);
    $school = School::ConvertRecordToHtmlParameters($record);

    $ebookStatuses = (new EbookSchool())->GetBookStatusesBySchoolId($_GET['id'])['result'];

    $AdminLmsTicketLoader = new AdminLmsTicketLoader();
    $ticketRecords = $AdminLmsTicketLoader->GetSchoolTeacherTicketList($school['id']);
    $ticketGroupRecords = $AdminLmsTicketLoader->GetSchoolsTicketGroupList($school['id']);
}

$ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('isUpdate', $isUpdate);
$smarty->assign('school', $school);
$smarty->assign('ebookStatuses', $ebookStatuses);
$smarty->assign('ticketRecords', $ticketRecords);
$smarty->assign('ticketGroupRecords', $ticketGroupRecords);
$smarty->assign('ticketTypes', $ticketTypes);
$smarty->display('_school.html');
