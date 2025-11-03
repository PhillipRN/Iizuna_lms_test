<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\TeacherController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Mails\TeacherRegisterMail;
use IizunaLMS\Models\SchoolModel;


$UserController = new TeacherController();
$teacher = [];
$teacherBookList = [];
$teacherBookTempList = [];
$errorMessages = [];
$csvErrorMessages = [];

if (!isset($_SESSION[SESS_RESIST_USER_DATA]))
{
    header('Location: ./teacher_register.php');
    exit;
}

$params = $_SESSION[SESS_RESIST_USER_DATA];
$teacher = $params;
$teacherBookTempList =
    (isset ($params["title_no"]) && !empty($params["title_no"]))
        ? $params["title_no"]
        : array();

// 登録
if (isset($_POST["submit"])) {
    // メールアドレスをログインIDにする
    $params['login_id'] = $params['mail'];

    $errors = $UserController->ValidateParameters($params, false);

    if (count($errors) == 0)
    {
        $result = $UserController->AddUser($params);

        if ($result == ERROR_NONE)
        {
            // メール送信
            (new TeacherRegisterMail())->Send($params);

            $_SESSION[SESS_RESIST_STATUS] = REGISTER_STATUS_REGISTERED;

            header('Location: ./teacher_register_result.php');
            exit;
        }
        else
        {
            $errors[] = $result;
        }
    }

    $errorMessages = MessageHelper::GetErrorMessages($errors);
}

$BookLoader = new BookLoader();
$bookList = $BookLoader->GetAvailableBookListForApplicationLMS();
$ebooks = (new LmsTicket())->GetAvailableTicketTypesForApplication();

$isSelectEnglish = false;
$isSelectJapanese = false;

foreach ($teacherBookTempList as $titleNo)
{
    if ($titleNo <  20000) $isSelectEnglish = true;
    if ($titleNo >= 20000) $isSelectJapanese = true;
}

$schoolList = (new SchoolModel())->GetsAll();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('teacher', $teacher);
$smarty->assign('errors', $errorMessages);
$smarty->assign('data', $_POST);
$smarty->assign('bookList', $bookList);
$smarty->assign('schoolList', $schoolList);
$smarty->assign('teacherBookList', $teacherBookList);
$smarty->assign('teacherBookTempList', $teacherBookTempList);
$smarty->assign('ebooks', $ebooks);
$smarty->assign('isSelectEnglish', $isSelectEnglish);
$smarty->assign('isSelectJapanese', $isSelectJapanese);
$smarty->display('_teacher_register_confirm.html');
