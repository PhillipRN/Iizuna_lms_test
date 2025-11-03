<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Schools\SchoolLoader;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$page = $_GET['page'] ?? 1;
$keyWord = '';

if (isset($_GET['key_word']))
{
    $keyWord = str_replace('　', ' ', $_GET['key_word']) ?? '';
}

$result = SchoolLoader::GetSchool($keyWord, $page);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('schoolList', $result['records']);
$smarty->assign('maxPageNum', $result['maxPageNum']);
$smarty->assign('currentPage', $page);
$smarty->assign('keyWord', $keyWord);
$smarty->display('_school_list.html');
