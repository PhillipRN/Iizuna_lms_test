<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Students\StudentSchool;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$studentId = $_GET['id'] ?? null;

if (empty($studentId)) {
    DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_STUDENT_EDIT_INVALID_PARAMETER);
}

$student = (new StudentModel())->GetById($studentId);

if (empty($student)) {
    DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_STUDENT_EDIT_NO_DATA);
}

if (!(new StudentSchool())->Check($studentId, $teacher->school_id)) {
    DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_STUDENT_EDIT_INVALID_SCHOOL);
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('data', $student);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_student_edit.html');