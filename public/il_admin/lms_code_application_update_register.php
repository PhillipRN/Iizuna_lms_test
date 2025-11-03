<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeApplicationModel;
use IizunaLMS\Models\SchoolGroupModel;
use IizunaLMS\Schools\LmsCodeApplication;

if (!AdminLoginController::IsLogin()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_NOT_LOGIN);

// 登録
if (isset($_POST["available_amount"])) {
    $params = RequestHelper::GetPostParams();

    $id = $params['id'] ?? null;

    if (empty($id)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_CODE_APPLICATION_INVALID_PARAMETER);

    $record = (new LmsCodeApplicationModel())->GetById($id);
    if (empty($id)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_CODE_APPLICATION_NO_DATA);

    $availableAmount = $params['available_amount'] ?? 0;
    $lmsCodeId = $record['lms_code_id'];

    PDOHelper::GetPDO()->beginTransaction();

    $result = (new LmsCodeApplicationModel())->Update([
        'id' => $id,
        'available_amount' => $availableAmount,
        'paid_application_status' => LmsCodeApplication::STATUS_ALLOWED
    ]);

    $resultLmsCodeAmount = (new LmsCodeAmountModel())->ApproveAndIncreaseAmount(
        $record['application_amount'],
        $availableAmount,
        $lmsCodeId);

    $resultSchoolGroup = (new SchoolGroupModel())->ApproveApplication($lmsCodeId);

    if ($result && $resultLmsCodeAmount && $resultSchoolGroup) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    } else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_CODE_APPLICATION_REGISTER_FAILED);
    }
} else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_LMS_CODE_APPLICATION_INVALID_PARAMETER);
}