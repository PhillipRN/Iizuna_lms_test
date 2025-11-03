<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\RegistrationController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ./registration_key_list.php');
    exit;
}

$id = $_GET['id'];

$RegistrationController = new RegistrationController();
$result = $RegistrationController->DisabledRecord($id);

$errors = [];

if ($result == ERROR_NONE)
{
}
else
{
    $errors[] = $result;
}

$errorMessages = MessageHelper::GetErrorMessages($errors);

// 結果表示用データを集める
$record = $RegistrationController->GetById($id);
$titleNo = $record['title_no'];
$hashKey = $record['hash_key'];

$BookLoader = new BookLoader();
$book = $BookLoader->GetBook($record['title_no']);

// 描画
$smarty = SmartyHelper::GetSmarty();
$smarty->assign('bookName', $book['name']);
$smarty->assign('hashKey', $hashKey);
$smarty->display('_registration_key_disable.html');
