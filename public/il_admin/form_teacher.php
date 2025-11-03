<?php
global $page;
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Models\TeacherModel;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

if (isset($_POST['school_id'])) {
    $schoolId = $_POST['school_id'];

    $records = (new TeacherModel())->GetsBySchoolId($schoolId);

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'records' => $records
    ]);
}