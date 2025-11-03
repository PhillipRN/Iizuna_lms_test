<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;

// ログインチェック
if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

// 教師データ取得
$teacher = TeacherLoginController::GetTeacherData();

// 現在の日時を取得（ファイル名用）
$dateStr = date('Ymd_His');

// ファイル名の設定
$filename = "student_template_" . $dateStr . ".csv";

// CSVヘッダー（必須項目のみを含める）
$headers = [
    '名前',
    '学籍番号',
    'ログインID',
    'パスワード',
    '学校名',
    '学年',
    'クラス'
];

// サンプルデータ（必須項目のみ）
$sampleData = [
    ['山田太郎', '10001', 'yamada_t', 'password123', '○○高校', '1', 'A'],
    ['佐藤花子', '10002', 'sato_h', 'password456', '○○高校', '2', 'B']
];

// ヘッダー設定
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 出力バッファを開始
ob_start();

// CSVデータを出力
$output = fopen('php://output', 'w');

// BOMを追加してUTF-8として認識されるようにする
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ヘッダー行を書き込み
fputcsv($output, $headers);

// サンプルデータを書き込み
foreach ($sampleData as $row) {
    fputcsv($output, $row);
}

// テンプレート用の空の行を追加
$emptyRow = array_fill(0, count($headers), '');
fputcsv($output, $emptyRow);
fputcsv($output, $emptyRow);
fputcsv($output, $emptyRow);

fclose($output);

// 出力バッファの内容をクライアントに送信
ob_end_flush();
exit;