<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!isset($_SESSION[SESS_RESIST_DATA]))
{
    header('Location: ./registration_key_regist.php');
    exit;
}

$teacherId = $_SESSION[SESS_RESIST_DATA]['teacher_id'];
$titleNo = $_SESSION[SESS_RESIST_DATA]['title_no'];
$hashKey = $_SESSION[SESS_RESIST_DATA]['hash_key'];

$BookLoader = new BookLoader();
$book = $BookLoader->GetBook($titleNo);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('teacherId', $teacherId);
$smarty->assign('bookName', $book['name']);
$smarty->assign('hashKey', $hashKey);
$smarty->display('_registration_key_result.html');
