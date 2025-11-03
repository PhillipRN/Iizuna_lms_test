<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\TeacherBookApplicationLogModel;
use IizunaLMS\Models\TeacherBookApplicationModel;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録
if (isset($_POST['id'])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    $id = $params['id'];

    $TeacherBookApplicationModel = new TeacherBookApplicationModel();
    $record = $TeacherBookApplicationModel->GetById($id);

    if (empty($record)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_PARAMETER_ERROR);

    // TeacherBookApplication を消す
    $result =$TeacherBookApplicationModel->DeleteByKeyValue('id', $id);

    // TeacherBookApplicationLog を消す
    // NOTE 状態を残す場合は対応必要
    $resultLog = (new TeacherBookApplicationLogModel())->DeleteTeacherLog(
        $record['title_no'],
        $record['teacher_id']
    );

    if ($result && $resultLog) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_DELETE_FAILED);
    }
}
else {
    PDOHelper::GetPDO()->rollBack();
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_PARAMETER_ERROR);
}