<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\RegistrationController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!isset($_POST['hash_key'])) {
    header('Location: ./registration.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$hashKey = $_POST['hash_key'];

// キーが有効か確認する
$RegistrationController = new RegistrationController();
$record = $RegistrationController->GetByHashKey($hashKey);

$errors = [];

if (empty($record)) {
    $errors[] = ERROR_REGISTRATION_KEY_NOT_FOUND;
}
elseif ($record['status'] == REGISTRATION_KEY_STATUS_REGISTERED) {
    $errors[] = ERROR_REGISTRATION_KEY_ALREADY_REGISTERED;
}
elseif ($record['status'] == REGISTRATION_KEY_STATUS_DISABLED) {
    $errors[] = ERROR_REGISTRATION_KEY_DISABLED;
}

$id = $record['id'];
$titleNo = $record['title_no'];

// 既に持っている書籍もエラー
$BookLoader = new BookLoader();
if ($BookLoader->IsRegisteredBook($teacher->id, $titleNo)) {
    $errors[] = ERROR_REGISTRATION_KEY_ALREADY_HAVING_BOOK;
}

if (count($errors) == 0)
{
    // 登録処理
    $RegistrationController->RegistrationBook($id, $titleNo, $teacher->id);
}

$book = $BookLoader->GetBook($titleNo);
$errorMessages = MessageHelper::GetErrorMessages($errors);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('book', $book);
$smarty->assign('errors', $errorMessages);
$smarty->assign('hashKey', $hashKey);
$smarty->display('_registration_regist.html');
