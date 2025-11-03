<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeApplicationModel;
use IizunaLMS\Models\SchoolGroupModel;
use IizunaLMS\Schools\LmsCodeApplication;

if (!TeacherLoginController::IsLogin()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);

// 登録
if (isset($_POST["application_amount"])) {
    $params = RequestHelper::GetPostParams();

    $lmsCodeId = $params['lcid'] ?? null;
    if (empty($lmsCodeId)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_PARAMETER_ERROR);

    $schoolGroupRecord = (new SchoolGroupModel())->GetByKeyValue('lms_code_id', $lmsCodeId);
    if (empty($schoolGroupRecord) || empty($schoolGroupRecord['is_paid'])) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_DATA_IS_NONE);

    PDOHelper::GetPDO()->beginTransaction();

    $result = (new LmsCodeApplicationModel())->Add(new LmsCodeApplication([
        'lms_code_id' => $lmsCodeId,
        'paid_application_status' => LmsCodeApplication::STATUS_APPLICATION_WAITING_UPDATE_APPROVAL,
        'application_amount' => $params['application_amount'] ?? 0
    ]));

    if ($result) {
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