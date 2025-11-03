<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Controllers\AdminTeacherBookApplicationController;
use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\Datas\TeacherBookApplicationLog;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$isNoPasswordUser = isset($_GET["no_password_user"]);
$isAllUser = isset($_GET["all_teacher"]);

$file_name = "";
$headers = [];
$outputUsers = [];

if ($isNoPasswordUser || $isAllUser)
{
    $file_name = ($isNoPasswordUser)
        ? "no_password_teachers_for_testcreator.csv"
        : "all_teachers_for_testcreator.csv";

    // ヘッダー情報
    $headers = [
        'id',
        'login_id',
        'password',
//        'pref',
        'school_pref',
        'school_name',
//        'school_zip',
//        'school_address',
//        'school_phone',
        'phone',
        'name_1',
        'name_2',
        'kana_1',
        'kana_2',
        'mail',
        'is_e_onigiri',
        'book_1', // 先生申請時
        'book_2'  // 追加申請時
    ];

    $AdminTeacherController = new AdminTeacherController();
    $teacherList = ($isNoPasswordUser)
        ? $AdminTeacherController->GetActiveNoPasswordTeachers()
        : $AdminTeacherController->GetTeachers();

    $teacherBookApplicationLogList = (new AdminTeacherBookApplicationController())->GetTeachersLog();

    foreach ($teacherList as $teacher) {
        $book1 = ''; // 先生申請時
        $book2 = ''; // 追加申請時

        $teacherId = $teacher['id'];

        if (isset($teacherBookApplicationLogList[ $teacherId ])) {
            $book1 = implode('_', $teacherBookApplicationLogList[ $teacherId ][ TeacherBookApplicationLog::TYPE_CREATE_TEACHER ]);
            $book2 = implode('_', $teacherBookApplicationLogList[ $teacherId ][ TeacherBookApplicationLog::TYPE_ADD ]);
        }

        $outputUsers[] = [
            $teacherId,
            $teacher['login_id'],
            '',
//            $teacher['pref'],
            $teacher['school_pref'],
            $teacher['school_name'],
//            $teacher['school_zip'],
//            $teacher['school_address'],
//            $teacher['school_phone'],
            $teacher['phone'],
            $teacher['name_1'],
            $teacher['name_2'],
            $teacher['kana_1'],
            $teacher['kana_2'],
            $teacher['mail'],
            $teacher['is_e_onigiri'],
            $book1,
            $book2
        ];
    }
}
else
{
    exit;
}

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// UTF-8からSJIS-winへ変換するフィルター
stream_filter_append($fp, 'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);

// ヘッダー出力
fputcsv($fp, $headers, ',', '"');

// ボディー出力
foreach ($outputUsers as $teacher) {
    fputcsv($fp, $teacher, ',', '"');
}
fclose($fp);
exit;
