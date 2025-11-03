<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\TeacherBookModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$bookList = (new TeacherBookModel())->GetBooksWithNameByTeacherId($teacher->id);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('bookList', $bookList);
$smarty->display('_registration.html');
