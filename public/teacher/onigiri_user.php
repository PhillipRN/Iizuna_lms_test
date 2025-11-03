<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Firebase\OnigiriUser;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$schoolId = $teacher->school_id;
$schoolGroups = [];

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'lms_code' => $ticket['lms_code'],
        'name' => $ticket['name']
    ];
}

$currentLmsCodeId = $_GET['lcid'] ?? null;
$lmsCode = '';

foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $lmsCode = $schoolGroup['lms_code'];
        break;
    }
}

$records = (!empty($lmsCode)) ? (new OnigiriUser())->GetOnigiriUserData($lmsCode) : [];

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->display('_onigiri_user.html');