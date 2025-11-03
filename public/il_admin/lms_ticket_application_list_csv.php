<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Admin\LmsTickets\AdminLmsTicketLoader;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\LmsTickets\LmsTicket;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$records = (new AdminLmsTicketLoader())->GetTicketList();
$ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();

// ヘッダー情報
$headers = [
    '申請日時',
    '学校',
    '氏名_1',
    '氏名_2',
    '種別',
    '有効期限',
    '購入申請人数',
    'ステータス',
];

$outputRecords = [];

foreach ($records as $record)
{
    $status = '';
    if ($record['lms_ticket_application_type'] == 2)
    {
        $status = ($record['teacher_id'] == 0) ? '管理者が学校に付与' : '管理者が先生に付与';
    }
    else
    {
        switch ($record['lms_ticket_application_status'])
        {
            case 1:
                $status = '申請中';
                break;
            case 2:
                $status = '承認済';
                break;
        }
    }

    $outputRecords[] = [
        date('Y-m-d H:i:s', strtotime($record['create_date'])),
        $record['school_name'],
        $record['teacher_name_1'] ?? '',
        $record['teacher_name_2'] ?? '',
        $ticketTypes[$record['title_no']]['name'] ?? '',
        "{$record['expire_year']}年{$record['expire_month']}月",
        $record['quantity'],
        $status
    ];
}

$file_name = 'lms_ticket_application_list_csv.csv';

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// UTF-8からSJIS-winへ変換するフィルター
stream_filter_append($fp, 'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);


// ヘッダー出力
fputcsv($fp, $headers, ',', '"');

// ボディー出力
foreach ($outputRecords as $record) {
    fputcsv($fp, $record, ',', '"');
}
fclose($fp);

exit;