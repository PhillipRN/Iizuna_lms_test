<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\StudentCodeModel;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeAmount;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Schools\StudentCode;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

if (!$teacher->is_juku) {
    header('Location: ./index.php');
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

            $params['lms_code_id'] = $lmsCodeId;
            $params['teacher_id'] = $teacher->id;
            $params['school_id'] = $teacher->school_id;

            $resultStudentCode = (new StudentCodeModel())->Add(new StudentCode($params));

            $resultLmsCodeAmount = (new LmsCodeAmountModel())->Add(new LmsCodeAmount($lmsCodeId));

            if ($resultStudentCode && $resultLmsCodeAmount) {
                $result = true;
            }
        }
    }
    // 更新
    else {
        $result = (new StudentCodeModel())->Update(new StudentCode($params));
    }

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_CODE_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_CODE_PARAMETER_ERROR);
}