<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Controllers\LoginController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Helpers\MessageHelper;

$errors = [];

if (isset($_POST["submit"]))
{
    $LoginController = new LoginController();

    $user_id = $_POST["id"];
    $password = $_POST["password"];

    $errors = $LoginController->ValidateLoginParameters($user_id, $password);

    if (count($errors) == 0)
    {
        $result = $LoginController->Login($user_id, $password);

        if ($result == ERROR_NONE)
        {
            header('Location: ./index.php');
            exit;
        }
        else
            {
            $errors[] = MessageHelper::GetErrorMessage($result);
        }
    }
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('errors', $errors);
$smarty->assign('data', $_POST);
$smarty->display('_login.html');
