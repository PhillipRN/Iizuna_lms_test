<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeApplicationModel;
use IizunaLMS\Models\SchoolGroupModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 更新
if (isset($_POST["id"])) {
    $params = RequestHelper::GetPostParams();

    $id = $params['id'];

    $SchoolGroupModel = new SchoolGroupModel();

    $record = $SchoolGroupModel->GetById($id);

    if (empty($record))
    {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_REGISTER_FAILED);
    }

    PDOHelper::GetPDO()->beginTransaction();
    $result = (new SchoolGroupModel())->DeleteByKeyValue('id', $id);

    $resultLmsCodeApplication = (new LmsCodeApplicationModel())->DeleteByKeyValue('lms_code_id', $record['lms_code_id']);

    if ($result && $resultLmsCodeApplication) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_PARAMETER_ERROR);
}