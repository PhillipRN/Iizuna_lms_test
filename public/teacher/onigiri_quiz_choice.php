<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Schools\OnigiriLearningRangeLoader;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$currentLmsCodeId = $_GET['lcid'] ?? null;

$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($teacher->school_id);

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name']
    ];
}

if (empty($currentLmsCodeId)) {
    foreach ($schoolGroups as $schoolGroup) {
        if (!empty($schoolGroup['is_school'])) {
            $currentLmsCodeId = $schoolGroup['lms_code_id'];
            break;
        }
    }
}

$stages = (new OnigiriLearningRangeLoader($currentLmsCodeId))->LoadForOnigiriQuizChoicePage();
$genreNames = OnigiriLearningRangeLoader::GenerateGenreNames($stages);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->assign('stages', $stages);
$smarty->assign('genreNames', json_encode($genreNames));
$smarty->assign('noStages', empty($stages) ? 1 : 0);
$smarty->display('_onigiri_quiz_choice.html');