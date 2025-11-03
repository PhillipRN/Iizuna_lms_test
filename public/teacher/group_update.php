<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\LmsCodeApplicationModel;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$lmsCodeId = $_GET['lcid'] ?? null;

if (empty($lmsCodeId)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_SCHOOL_GROUP_DATA_IS_NONE);

$teacher = TeacherLoginController::GetTeacherData();
$schoolId = $teacher->school_id;

$records = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$record = null;

foreach ($records as $tempRecord) {
    if ($tempRecord['lms_code_id'] == $lmsCodeId) {
        $record = $tempRecord;
        break;
    }
}

if (empty($record)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_SCHOOL_GROUP_DATA_IS_NONE);

$applicationRecords = (new LmsCodeApplicationModel())->GetsByKeyValues(
    ['lms_code_id'],
    [$lmsCodeId],
    [],
    ['id' => 'desc']);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('record', $record);
$smarty->assign('applicationRecords', $applicationRecords);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_group_update.html');