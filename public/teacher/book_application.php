<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\TeacherBookApplicationViewModel;
use IizunaLMS\Models\TeacherBookModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$teacherBookList = (new TeacherBookModel())->GetBooksWithNameByTeacherId($teacher->id);

$bookList = (new BookLoader())->GetAvailableBookListForApplicationLMS();

$excludeTitleNos = [];

// 所持している書籍は除外
foreach ($teacherBookList as $teacherBook) {
    $excludeTitleNos[] = $teacherBook['title_no'];
}

// 申請中書籍
$applicationBookList = (new TeacherBookApplicationViewModel())->GetsByKeyValue('teacher_id', $teacher->id, ['id' => 'DESC']);

// 申請中書籍は除外
foreach ($applicationBookList as $applicationBook) {
    $excludeTitleNos[] = $applicationBook['title_no'];
}

// 除外書籍を除外
for ($languageType=0; $languageType<=1; ++$languageType) {
    for ($i=0; $i<count($bookList[$languageType]); ++$i) {
        $book = $bookList[$languageType][$i];

        if (in_array($book['title_no'], $excludeTitleNos)) {
            array_splice($bookList[$languageType], $i, 1);
            --$i;
        }
    }
}

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('bookList', $bookList);
$smarty->assign('applicationBookList', $applicationBookList);
$smarty->assign('excludeTitleNos', $excludeTitleNos);
$smarty->display('_book_application.html');
