<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\Controllers\RegistrationController;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\SmartyHelper;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacherId = (isset($_GET['teacher_id'])) ? $_GET['teacher_id'] : "";
$titleNo = (isset($_GET['title_no'])) ? $_GET['title_no'] : "";

if (isset($_SESSION[SESS_RESIST_DATA])) unset($_SESSION[SESS_RESIST_DATA]);

if (isset($_POST['title_no'])) {
    $params = array();

    foreach ($_POST as $key => $val)
    {
        if ($key == 'submit') continue;
        $params[$key] = $val;
    }

    $RegistrationController = new RegistrationController();
    $errors = $RegistrationController->ValidateRegisrationParameters($params);

    if (count($errors) == 0)
    {
        $titleNo = $params['title_no'];
        $teacherId  = (isset($params['teacher_id'])) ? $params['teacher_id'] : "";
        $hashKey = $RegistrationController->GenerateHashKey();
        $result = $RegistrationController->AddRecord($titleNo, $hashKey);

        if ($result == ERROR_NONE && !empty($teacherId))
        {
            $record = $RegistrationController->GetByHashKey($hashKey);
            $result = $RegistrationController->SetRegistrationKeyId($teacherId, $titleNo, $record['id']);
        }

        // 登録に成功したらセッションに詰めて移動
        if ($result == ERROR_NONE)
        {
            $_SESSION[SESS_RESIST_DATA] = [
                'teacher_id' => $teacherId,
                'title_no' => $titleNo,
                'hash_key' => $hashKey,
            ];
            header('Location: ./registration_key_result.php');
            exit;
        }
        else
        {
            $errors[] = $result;
        }
    }

    if (isset($_POST['teacher_id'])) {
        $teacherId = $_POST['teacher_id'];
        $titleNo = $_POST['title_no'];
    }

    $errorMessages = MessageHelper::GetErrorMessages($errors);
}

// 書籍リスト取得
$BookLoader= new BookLoader();
$bookList = $BookLoader->GetBookList();

$AdminUserController = new AdminTeacherController();

$teacher = ($teacherId) ? $AdminUserController->GetById($teacherId) : [];
$book = ($titleNo) ? $BookLoader->GetBook($titleNo) : [];

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('bookList', $bookList);
$smarty->assign('teacherId', $teacherId);
$smarty->assign('titleNo', $titleNo);
$smarty->assign('teacher', $teacher);
$smarty->assign('book', $book);
$smarty->display('_registration_key_regist.html');
