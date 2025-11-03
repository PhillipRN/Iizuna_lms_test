<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}
$teacher = TeacherLoginController::GetTeacherData();
$schoolId = $teacher->school_id;

$records = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->display('_group.html');