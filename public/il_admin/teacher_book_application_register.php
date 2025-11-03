<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Datas\TeacherBookApplication;
use IizunaLMS\EBook\EbookSchool;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\TeacherBookApplicationModel;
use IizunaLMS\Models\TeacherBookModel;
use IizunaLMS\Schools\SchoolRegister;

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

    $teacherId = $record['teacher_id'];
    $titleNo = $record['title_no'];

    // 既に持っている書籍かチェック
    $TeacherBookModel = new TeacherBookModel();
    if ($TeacherBookModel->IsRegisterd($teacherId, $titleNo)) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_ALREADY_REGISTERED);
    }

    // 登録
    $resultAdd = $TeacherBookModel->AddTeacherBook($teacherId, $titleNo);

    // 申請レコード更新
    $resultUpdateBookApplication = $TeacherBookApplicationModel->Update([
        'id' => $id,
        'status' => TeacherBookApplication::STATUS_OK
    ]);

    if ($resultAdd && $resultUpdateBookApplication) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_REGISTER_FAILED);
    }
}
else {
    PDOHelper::GetPDO()->rollBack();
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ADMIN_BOOK_APPLICATION_PARAMETER_ERROR);
}