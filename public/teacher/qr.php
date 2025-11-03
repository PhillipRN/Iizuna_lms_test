<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\SchoolGroupViewModel;
use IizunaLMS\Models\SchoolViewModel;
use IizunaLMS\Models\StudentCodeViewModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (empty($_GET['school_id']) && empty($_GET['group_ids']) && empty($_POST['student_code_ids']) && empty($_POST['ticket_group_ids'])) {
    DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_QR_PARAMETER_ERROR);
    exit;
}

$records = [];

if (!empty($_GET['school_id'])) {
    $school = (new SchoolViewModel())->GetById($_GET['school_id']);

    $records[] = [
        'name' => $school['name'],
        'lms_code' => $school['lms_code']
    ];
}

if (!empty($_GET['group_ids'])) {
    $groups = (new SchoolGroupViewModel())->GetsByKeyInValues('id', $_GET['group_ids'], ['id'=>'ASC']);

    foreach ($groups as $group) {
        $records[] = [
            'name' => $group['name'],
            'lms_code' => $group['lms_code']
        ];
    }
}

if (!empty($_POST['student_code_ids'])) {
    $studentCodes = (new StudentCodeViewModel())->GetsByKeyInValues('id', $_POST['student_code_ids'], ['id'=>'ASC']);

    foreach ($studentCodes as $studentCode) {
        $records[] = [
            'name' => $studentCode['name'],
            'lms_code' => $studentCode['lms_code']
        ];
    }
}

if (!empty($_POST['ticket_group_ids'])) {
    $ticketGroupCodes = (new LmsTicketGroupViewModel())->GetsByKeyInValues('id', $_POST['ticket_group_ids'], ['id'=>'ASC']);

    foreach ($ticketGroupCodes as $ticketGroupCode) {
        $records[] = [
            'name' => $ticketGroupCode['name'],
            'lms_code' => $ticketGroupCode['lms_code']
        ];
    }
}

$smarty = TeacherSmartyHelper::GetSmarty();
$smarty->assign('records', $records);
$smarty->display('_qr.html');