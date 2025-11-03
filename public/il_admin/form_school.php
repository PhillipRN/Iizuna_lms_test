<?php
global $page;
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Schools\SchoolLoader;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

if (isset($_POST['key_word'])) {
    $keyWord = str_replace('　', ' ', $_POST['key_word']) ?? '';
    $result = SchoolLoader::GetSchool($keyWord);

    $records = (empty($result['records'])) ? [] : $result['records'];

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'records' => $records
    ]);
}