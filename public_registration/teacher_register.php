<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\TeacherController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\SchoolModel;


if (!isset($_SESSION[SESS_TEACHER_REGISTER_CHECK_KEY])) {
    $_SESSION[SESS_TEACHER_REGISTER_CHECK_KEY] = StringHelper::GetRandomString(13);
}

$TeacherController = new TeacherController();
$teacher = [];
$teacherBookList = [];
$teacherBookTempList = [];
$errorMessages = [];
$csvErrorMessages = [];
$schoolList = [];

// 登録
if (isset($_POST["submit"])) {
    $params = RequestHelper::GetPostParams(['submit']);

    // メールアドレスをログインIDにする
    $params['login_id'] = $params['mail'];

    $errors = $TeacherController->ValidateParameters($params, false);

    if (empty($params['privacy'])) $errors[] = ERROR_ADMIN_USER_REGISTER_NOT_CHECK_PRIVACY;
    if (empty($params['terms'])) $errors[] = ERROR_ADMIN_USER_REGISTER_NOT_CHECK_TERMS;

    if (count($errors) == 0)
    {
        $_SESSION[SESS_RESIST_USER_DATA] = $params;

        header('Location: ./teacher_register_confirm.php');
        exit;
    }

    $teacher = $params;
    $errorMessages = MessageHelper::GetErrorMessages($errors);
}
else if (isset($_GET['back']) && isset($_SESSION[SESS_RESIST_USER_DATA]))
{
    $teacher = $_SESSION[SESS_RESIST_USER_DATA];
}

if (!empty($teacher))
{
    $teacherBookTempList =
        (isset ($teacher["title_no"]) && !empty($teacher["title_no"]))
            ? $teacher["title_no"]
            : array();

    // school_zip の値がある場合は郵便番号から学校一覧を抽出する
    if (!empty($teacher['school_zip']))
    {
        $zip = str_replace('-', '', $teacher['school_zip']);
        $schoolList = (new SchoolModel())->GetsByKeyValue('zip', $zip);
    }
}

$BookLoader = new BookLoader();
$bookList = $BookLoader->GetAvailableBookListForApplicationLMS();
$ebooks = (new LmsTicket())->GetAvailableTicketTypesForApplication();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('teacher', $teacher);
$smarty->assign('errors', $errorMessages);
$smarty->assign('data', $_POST);
$smarty->assign('bookList', $bookList);
$smarty->assign('schoolList', $schoolList);
$smarty->assign('teacherBookList', $teacherBookList);
$smarty->assign('teacherBookTempList', $teacherBookTempList);
$smarty->assign('ebooks', $ebooks);
$smarty->assign('session_check_key', $_SESSION[SESS_TEACHER_REGISTER_CHECK_KEY]);
$smarty->display('_teacher_register.html');
