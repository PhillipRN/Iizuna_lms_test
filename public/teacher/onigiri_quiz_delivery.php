<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizLoader;
use IizunaLMS\Schools\LmsCodeApplication;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? 0;
if (empty($quizId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_JSON_QUIZ_INVALID_URL);

$jsonQuiz = (new OnigiriJsonQuizLoader())->GetAndResultNumById($quizId);
if (empty($jsonQuiz)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_JSON_QUIZ_NO_DATA);

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$schoolAndGroups = SchoolGroupLoader::GetSchoolAndGroups($teacher->school_id);

// 配信済み判定チェック
$deliveryRecords = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $quizId);

// 配信可能な学校とグループのみを取得
$records = [];
foreach ($schoolAndGroups as $key => $schoolAndGroup)
{
    if ($schoolAndGroup['paid_application_status'] != LmsCodeApplication::STATUS_ALLOWED || empty($schoolAndGroup['is_paid'])) continue;

    $records[] = [
        'lms_code_id' => $schoolAndGroup['lms_code_id'],
        'name' => $schoolAndGroup['name'],
        'teacher_name_1' => $schoolAndGroup['teacher_name_1'],
        'teacher_name_2' => $schoolAndGroup['teacher_name_2'],
        'lms_code' => $schoolAndGroup['lms_code'],
        'is_delivery' => false,
        'notice_id' => ''
    ];
}

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);

// 配信可能な学校とグループをマージする
foreach ($ticketData as $ticket)
{
    $records[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name'],
        'teacher_name_1' => $teacher->name_1,
        'teacher_name_2' => $teacher->name_2,
        'lms_code' => $ticket['lms_code'],
        'is_delivery' => false,
        'notice_id' => ''
    ];
}

foreach ($records as $key => $record)
{
    $isDelivery = false;
    $noticeId = '';
    foreach ($deliveryRecords as $deliveryRecord)
    {
        if ($record['lms_code_id'] == $deliveryRecord['lms_code_id'])
        {
            $isDelivery = true;
            $noticeId = $deliveryRecord['notice_id'];
            break;
        }
    }

    $record['is_delivery'] = $isDelivery;
    $record['notice_id'] = $noticeId;

    $records[ $key ] = $record;
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('jsonQuiz', $jsonQuiz);
$smarty->assign('records', $records);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz_delivery.html');