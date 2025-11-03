<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\EBook\EbookSchool;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Schools\SchoolRegister;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

// 登録・更新
if (isset($_POST["school_name"])) {
    $params = RequestHelper::GetPostParams();

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    $id = $params['id'] ?? null;

    // 新規登録
    if (empty($params['id'])) {
        $result = (new SchoolRegister())->Add($params);

        if (is_array($result)) {
            $id = $result['school_id'];
        }
    }
    // 更新
    else {
        $result = (new SchoolRegister())->Update($params);
    }

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_SCHOOL_REGISTER_FAILED);
    }
}