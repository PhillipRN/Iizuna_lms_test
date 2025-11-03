<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Schools\LmsCodeApplication;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? 0;
if (empty($quizId)) DisplayErrorHelper::RedirectTeacherErrorPage(ERROR_JSON_QUIZ_INVALID_URL);

$jsonQuiz = (new JsonQuizController())->Get($quizId);
if (empty($jsonQuiz)) DisplayErrorHelper::RedirectTeacherErrorPage(ERROR_JSON_QUIZ_NO_DATA);

$teacher = TeacherLoginController::GetTeacherData();

$schoolAndGroups = SchoolGroupLoader::GetSchoolAndGroups($teacher->school_id);

// 配信済み判定チェック
$deliveryRecords = (new JsonQuizDeliveryModel())->GetsByKeyValue('json_quiz_id', $quizId);
$deliveryLmsCodeIds = [];

foreach ($deliveryRecords as $record) $deliveryLmsCodeIds[] = $record['lms_code_id'];

$records = [];
foreach ($schoolAndGroups as $key => $schoolAndGroup)
{
    if ($schoolAndGroup['paid_application_status'] != LmsCodeApplication::STATUS_ALLOWED) continue;

    $schoolAndGroup['is_delivery'] = in_array($schoolAndGroup['lms_code_id'], $deliveryLmsCodeIds);

    $records[] = $schoolAndGroup;
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('jsonQuiz', $jsonQuiz);
$smarty->assign('records', $records);
$smarty->display('_quiz_delivery.html');