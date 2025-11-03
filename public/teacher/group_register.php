<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeApplicationModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\SchoolGroupModel;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeAmount;
use IizunaLMS\Schools\LmsCodeApplication;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Schools\SchoolGroup;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 登録・更新
if (isset($_POST["name"])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    // 新規登録
    if (empty($params['id'])) {

        // LMSコード生成
        $lmsCode = (new LmsCodeGenerator())->Generate();

        $resultLmsCode = (new LmsCodeModel())->Add(new LmsCode([
            'lms_code' => $lmsCode
        ]));

        if ($resultLmsCode) {
            $lmsCodeId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

            $teacher = TeacherLoginController::GetTeacherData();

            $params['lms_code_id'] = $lmsCodeId;
            $params['teacher_id'] = $teacher->id;
            $params['school_id'] = $teacher->school_id;

            $isPaid = $params['is_paid'] ?? 0;

            if ($isPaid) $params['paid_application_status'] = LmsCodeApplication::STATUS_APPLICATION_WAITING_NEW_APPROVAL;

            $resultSchoolGroup = (new SchoolGroupModel())->Add(new SchoolGroup($params));

            $resultLmsCodeAmount = (new LmsCodeAmountModel())->Add(new LmsCodeAmount($lmsCodeId));

            // 有料申請登録処理
            $resultLmsCodeApplication = true;
            if ($isPaid)
            {
                $resultLmsCodeApplication = (new LmsCodeApplicationModel())->Add(new LmsCodeApplication([
                    'lms_code_id' => $lmsCodeId,
                    'paid_application_status' => LmsCodeApplication::STATUS_APPLICATION_WAITING_NEW_APPROVAL,
                    'application_amount' => $params['application_amount'] ?? 0
                ]));
            }

            if ($resultSchoolGroup && $resultLmsCodeAmount && $resultLmsCodeApplication) {
                $result = true;
            }
        }
    }
    // 更新
    else {
        $result = (new SchoolGroupModel())->Update(new SchoolGroup($params));
    }

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