<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\EBook\TeacherEBookLoader;
use IizunaLMS\Helpers\MessageHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\SchoolModel;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 削除処理
else if (isset($_GET["del"]))
{
    $AdminTeacherController = new AdminTeacherController();
    $AdminTeacherController->DeleteTeacher($_GET["del"]);
    $_SESSION[SESS_RESIST_STATUS] = REGISTER_STATUS_DELETED;
    header('Location: ./teacher_list.php');
    exit;
}

$AdminTeacherController = new AdminTeacherController();
$isUpdate = (isset($_GET["id"]));
$teacher = [];
$teacherBookList = [];
$errorMessages = [];
$csvErrorMessages = [];

// CSVアップロード時の処理
if (isset($_FILES["csvfile"]["tmp_name"]) && is_uploaded_file($_FILES["csvfile"]["tmp_name"]))
{
    $result = $AdminTeacherController->UploadCsvFile(
        $_FILES["csvfile"]["tmp_name"],
        $_FILES["csvfile"]["name"]);

    if (!empty($result["registerErrors"]))
    {
        // エラーメッセージを1次元配列に変更
        foreach ($result["registerErrors"] as $row => $rowErrors)
        {
            foreach ($rowErrors as $rowError)
            {
                $prefix = "(" . ($row + 1) . "人目) ";
                $csvErrorMessages[] = $prefix . MessageHelper::GetErrorMessage($rowError);
            }
        }
    }

    if (!empty($result["csvErrors"]))
    {
        $csvErrorMessages = array_merge($csvErrorMessages, MessageHelper::GetErrorMessages($result["csvErrors"]));
    }

    if (empty($csvErrorMessages))
    {
        if (isset($result["statuses"]) && !empty($result["statuses"]))
        {
            $_SESSION[SESS_RESIST_STATUS] = $result["statuses"];
        }
        else
        {
            $_SESSION[SESS_RESIST_STATUS] = REGISTER_STATUS_REGISTERED;
        }

        header('Location: ./register_result.php');
        exit;
    }
}

// 登録・更新
else if (isset($_POST["login_id"])) {
    $params = array();
    foreach ($_POST as $key => $val)
    {
        if ($key == "submit") continue;
        $params[$key] = $val;
    }

    $isCheckPassword = !$isUpdate;

    $errors = $AdminTeacherController->ValidateLoginParameters($params, $isCheckPassword);

    if (count($errors) == 0)
    {
        $result = ($isUpdate)
            ? $AdminTeacherController->UpdateTeacherAndRegistTeacherBooks($params)
            : $AdminTeacherController->AddTeacherAndRegistTeacherBooks($params);

        if ($result == ERROR_NONE)
        {
            $_SESSION[SESS_RESIST_STATUS] = REGISTER_STATUS_REGISTERED;

            if ($isUpdate) {
                header('Location: ./teacher_register.php?id=' . $params["id"]);
            }
            else
            {
                header('Location: ./teacher_list.php');
            }
            exit;
        }
        else
        {
            $errors[] = $result;
        }
    }

    $teacher = $params;
    $teacherBookList =
        (isset ($params["title_no"]) && !empty($params["title_no"]))
            ? $params["title_no"]
            : array();

    $errorMessages = MessageHelper::GetErrorMessages($errors);
}

$BookLoader = new BookLoader();
$bookList = $BookLoader->GetSortTitlenoBookList();
$ebooks = (new LmsTicket())->GetAvailableTicketTypesForApplication();

if ($isUpdate && empty($teacher)) {
    $teacher = $AdminTeacherController->GetById($_GET["id"]);
    $teacherBookList = $BookLoader->GetTeacherBookTitleNos($teacher["id"]);
    $teacher['teacher_ebook'] = (new TeacherEBookLoader())->GetTeacherBookTitleNos($teacher["id"]);
}

$isRegistered = false;

if (isset($_SESSION[SESS_RESIST_STATUS]) &&
    $_SESSION[SESS_RESIST_STATUS] == REGISTER_STATUS_REGISTERED)
{
    $isRegistered = true;
    unset($_SESSION[SESS_RESIST_STATUS]);
}

$schoolList = (new SchoolModel())->GetsAll();

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('isUpdate', $isUpdate);
$smarty->assign('isRegistered', $isRegistered);
$smarty->assign('teacher', $teacher);
$smarty->assign('teacherBookList', $teacherBookList);
$smarty->assign('errors', $errorMessages);
$smarty->assign('csvErrors', $csvErrorMessages);
$smarty->assign('data', $_POST);
$smarty->assign('bookList', $bookList);
$smarty->assign('schoolList', $schoolList);
$smarty->assign('ebooks', $ebooks);
$smarty->display('_teacher_register.html');
