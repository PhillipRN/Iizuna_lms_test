<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Controllers\StudentViewController; // 新しいコントローラークラス
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\RequestHelper; // 新しいヘルパークラス
use IizunaLMS\Helpers\TeacherSmartyHelper;

// ログインチェック
if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// リクエストパラメータの取得（サニタイズ済み）
$requestParams = RequestHelper::getParameters([
    'page' => ['filter' => FILTER_VALIDATE_INT, 'default' => 1],
    'sn' => ['filter' => FILTER_VALIDATE_INT, 'default' => 1],
    'lcid' => ['filter' => FILTER_VALIDATE_INT, 'default' => null]
]);

// ビューコントローラーの初期化
$viewController = new StudentViewController(
    TeacherLoginController::GetTeacherData(),
    $requestParams
);

// データの取得
$viewData = $viewController->getViewData();

// Smartyの設定
$smarty = TeacherSmartyHelper::GetSmarty($viewData['teacher']);
$smarty->assign($viewData);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_student.html');