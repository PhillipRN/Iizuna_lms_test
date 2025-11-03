<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Schools\OnigiriLearningRangeLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$schoolId = $teacher->school_id;

$data = [];
$errors = [];

$currentLmsCodeId = $_GET['lcid'] ?? null;

$schoolGroups = [];

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name']
    ];
}

if (empty($currentLmsCodeId) && !empty($schoolGroups)) {
    $currentLmsCodeId = $schoolGroups[0]['lms_code_id'];
}

$data = (new OnigiriLearningRangeLoader($currentLmsCodeId))->LoadForUpdatePage();

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('data', $data);
$smarty->assign('errors', $errors);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->display('_onigiri_learning_range.html');