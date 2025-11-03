<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Datas\TeacherBookApplication;
use IizunaLMS\Datas\TeacherBookApplicationLog;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\SchoolGroupModel;
use IizunaLMS\Models\TeacherBookApplicationLogModel;
use IizunaLMS\Models\TeacherBookApplicationModel;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Schools\SchoolGroup;

if (!TeacherLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録・更新
if (isset($_POST['title_no'])) {
    $params = RequestHelper::GetPostParams();

    if (empty($params['title_no'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_PARAMETER_ERROR);
    }

    $teacher = TeacherLoginController::GetTeacherData();

    $addRecords = [];
    $addLogRecords = [];

    foreach ($params['title_no'] as $titleNo) {
        $addRecords[] = new TeacherBookApplication([
            'teacher_id' => $teacher->id,
            'title_no' => $titleNo
        ]);

        $addLogRecords[] = new TeacherBookApplicationLog([
            'teacher_id' => $teacher->id,
            'title_no' => $titleNo,
            'type' => TeacherBookApplicationLog::TYPE_ADD
        ]);
    }

    PDOHelper::GetPDO()->beginTransaction();
    $result = (new TeacherBookApplicationModel())->MultipleAdd($addRecords);
    $resultLog = (new TeacherBookApplicationLogModel())->MultipleAdd($addLogRecords);

    if ($result && $resultLog) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_PARAMETER_ERROR);
}