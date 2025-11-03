<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\SchoolViewModel;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$params = RequestHelper::GetPostParams();

$result = [];

$record = (new SchoolViewModel())->GetByKeyValue('lms_code', $params['lms_code']);

if (empty($record))
{
    $result = [
        'error' => [
            'message' => '学校が見つかりませんでした。'
        ]
    ];
}
else
{
    $result = [
        'id' => $record['id'],
        'name' => $record['name']
    ];
}

DisplayJsonHelper::ShowAndExit($result);