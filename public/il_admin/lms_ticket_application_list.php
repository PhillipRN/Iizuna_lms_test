<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Admin\LmsTickets\AdminLmsTicketLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$currentPage = (isset($_GET['page'])) ? $_GET['page'] : 1;

$AdminLmsTicketsLoader = new AdminLmsTicketLoader();
$records = $AdminLmsTicketsLoader->GetTicketListByPage($currentPage);
$maxPageNum = $AdminLmsTicketsLoader->GetMaxPageNum();
$ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('records', $records);
$smarty->assign('ticketTypes', $ticketTypes);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('maxPageNum', $maxPageNum);
$smarty->display('_lms_ticket_application_list.html');
