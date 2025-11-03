<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\AppHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Requests\RequestParamStudentRegisterForWeb;
use IizunaLMS\Students\StudentRegisterForWeb;

$params = [];
$errors = [];
$isApp = false;

if (!empty($_POST))
{
    $params = new RequestParamStudentRegisterForWeb();
    $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
    $errors = ErrorMessage::GetMessages($errorCodes);

    if (empty($errors))
    {
        SessionHelper::SetStudentRegisterForWebData($params);

        header('Location: ./confirm.php');
        exit;
    }
}
else if (SessionHelper::IssetStudentRegisterForWebData())
{
    $params = SessionHelper::GetStudentRegisterForWebData();
    $isApp = SessionHelper::GetStudentRegisterForWebIsApp();
}
else
{
    $isApp = !empty($_GET['is_app']);
    SessionHelper::SetStudentRegisterForWebIsApp($isApp);

    if ($isApp)
    {
        $os = $_GET['os'] ?? 0;
        $version = $_GET['v'] ?? 0;
        AppHelper::SetAppOSAndVersion($os, $version);
    }
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('params', $params);
$smarty->assign('errors', $errors);
$smarty->assign('isApp', $isApp);
$smarty->display('_index.html');
