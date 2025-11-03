<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\SchoolGroupModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 更新
if (isset($_POST["id"]) && isset($_POST["is_enable"])) {
    $params = RequestHelper::GetPostParams();

    $id = $params['id'];

    $SchoolGroupModel = new SchoolGroupModel();

    $record = $SchoolGroupModel->GetById($id);

    if (empty($record))
    {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_GROUP_REGISTER_FAILED);
    }

    PDOHelper::GetPDO()->beginTransaction();
    $result = (new SchoolGroupModel())->Update([
        'id' => $id,
        'is_enable' => $params['is_enable']
    ]);

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