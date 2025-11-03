<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\TeacherBookApplicationLogModel;
use IizunaLMS\Models\TeacherBookApplicationModel;

if (!TeacherLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録・更新
if (isset($_POST['book_id'])) {
    $params = RequestHelper::GetPostParams();

    if (empty($params['book_id'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_PARAMETER_ERROR);
    }

    $bookId = $params['book_id'];

    // ログインしているユーザーのものかチェック
    $teacher = TeacherLoginController::GetTeacherData();

    $TeacherBookApplicationModel = new TeacherBookApplicationModel();
    $bookApplication = $TeacherBookApplicationModel->GetById($bookId);

    if ($bookApplication['teacher_id'] != $teacher->id) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_PARAMETER_ERROR);
    }

    PDOHelper::GetPDO()->beginTransaction();

    // TeacherBookApplication を消す
    $result = (new TeacherBookApplicationModel())->DeleteByKeyValue('id', $bookId);

    // TeacherBookApplicationLog を消す
    // NOTE 状態を残す場合は対応必要
    $resultLog = (new TeacherBookApplicationLogModel())->DeleteTeacherLog($bookApplication['title_no'], $teacher->id);

    if ($result && $resultLog) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_DELETE_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_BOOK_APPLICATION_PARAMETER_ERROR);
}