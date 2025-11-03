<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Students\StudentSchool;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

$teacher = TeacherLoginController::GetTeacherData();

// 更新
if (isset($_POST['id'])) {
    $params = RequestHelper::GetPostParams();
    $studentId = $params['id'];

    if (empty($studentId)) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_EDIT_INVALID_PARAMETER);
    }

    $StudentModel = new StudentModel();
    $student = $StudentModel->GetById($studentId);

    if (empty($student)) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_EDIT_NO_DATA);
    }

    if (!(new StudentSchool())->Check($studentId, $teacher->school_id)) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_EDIT_INVALID_SCHOOL);
    }

    PDOHelper::GetPDO()->beginTransaction();

    $result = $StudentModel->Update([
        'id' => $studentId,
        'name' => $params['name'],
        'student_number' => $params['student_number'],
    ]);

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_EDIT_UPDATE_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_EDIT_INVALID_PARAMETER);
}