<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Students\RegisterStudentCode;

// 必要に応じてクラスを追加

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (!TeacherLoginController::IsLogin())
{
    header('Location: ./login.php');
    exit;
}

if (isset($_POST['student_ids']) && isset($_POST['lms_code_id']))
{
    $teacher = TeacherLoginController::GetTeacherData();
    $studentIds = is_array($_POST['student_ids']) ? $_POST['student_ids'] : [$_POST['student_ids']];
    $lmsCodeId = $_POST['lms_code_id'];

    // コード登録処理を実行
    $result = (new RegisterStudentCode())->Register($studentIds, $lmsCodeId);

    if ($result['success']) {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK',
            'registeredCount' => $result['registeredCount'],
            'skippedCount' => $result['skippedCount']
        ]);
    } else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_CODE_FAILED);
    }
}
else
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_CODE_PARAMETER_ERROR);
}