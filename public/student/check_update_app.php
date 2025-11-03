<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\DisplayJsonHelper;

$result = [
    'result' => 'OK',
    'update_version' => '1.0.6', // これより低いバージョンの場合、強制アップデートのポップアップが表示される
    'disable_force_update' => 0, // 0: 強制アップデート有効、1: 強制アップデート無効
];

DisplayJsonHelper::ShowAndExit($result);