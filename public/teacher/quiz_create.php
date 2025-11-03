<?php

require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Books\Book;
use IizunaLMS\Books\BookLoader;
use IizunaLMS\Controllers\ChapterController;
use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\JsonQuizFolderController;
use IizunaLMS\Controllers\MidasiNoController;
use IizunaLMS\Controllers\QuestionController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Datas\JsonQuizOption;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\Models\BookModel;
use IizunaLMS\Models\JsonQuizOptionModel;
use IizunaLMS\Models\QuestionModel;
use IizunaLMS\Models\TeacherModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$quizId = $_GET['quiz_id'] ?? null;
$isUpdate = (!empty($quizId));

$teacher = TeacherLoginController::GetTeacherData();

// 書籍リスト取得
$BookLoader = new BookLoader();
$bookList = $BookLoader->GetAvailableBookList($teacher->id);

// 書籍データを作成する
$titleNos = [];
foreach ($bookList[Book::FLAG_ENGLISH_BOOK] as $key => $book)
{
    $titleNos[] = $book['title_no'];
}
foreach ($bookList[Book::FLAG_JAPANESE_BOOK] as $key => $book)
{
    $titleNos[] = $book['title_no'];
}
$bookDetails = $BookLoader->GetBookDetails($titleNos);

$jsonData = [];

foreach ($bookDetails as $key => $book)
{
    $jsonData[ $book['title_no']] = $book;
}

$isAnswered = false;
$isOtherTeacher = false;
$notEditable = false;
$enableCopy = false;
$isShuffle = true;
$isEnglishBookSelect = true;
$titleNo = 0;
$jsonQuiz = [];
$jsonQuizOption = null;
$total = 10;
$parentFolderId = $_GET['parent_folder_id'] ?? 0;

$currentBookData = [];
$currentQuestTypeData = [];
$currentFrequencyData = [];
$currentChapterData = [];
$currentMidasiData = [];
$currentSyomonDataMap = [];

// 既に作成済みのデータを取得する
if (!empty($quizId))
{
    $JsonQuizController = new JsonQuizController();
    $jsonQuiz = $JsonQuizController->Get($quizId);

    if (!empty($jsonQuiz))
    {
        $titleNo = $jsonQuiz['title_no'];
        $parentFolderId = $jsonQuiz['parent_folder_id'];

        $isOtherTeacher = $jsonQuiz['teacher_id'] != $teacher->id;
        // 自分のテストの場合は編集可能
        if (!$isOtherTeacher)
        {
            // 自分のテストはコピー可
            $enableCopy = true;

            // 自分のテストの場合でも既に回答者がいる場合は閲覧可能
            $isAnswered = $jsonQuiz['result_num'] != 0;
            $notEditable = $isAnswered;
        }
        else
        {
            $jsonQuizOwner = (new TeacherModel())->GetById($jsonQuiz['teacher_id']);

            // 自分のテストではなく、同じ学校のテストの場合は閲覧可能
            if ($teacher->school_id == $jsonQuizOwner['school_id']) {
                $notEditable = true;

                // 自分が持っている書籍のみコピーできる
                $enableCopy = (isset($jsonData[$titleNo]));
            }
            // それ以外の場合は閲覧不可
            else
            {
                header('Location: index.php');
                exit;
            }
        }

        // 書籍情報を取る
        $currentBookData = (new BookModel())->GetBook($titleNo);
        $isEnglishBookSelect = $currentBookData['type'] == 0;

        // 設定値を取得する
        $jsonQuizOptionRecord = (new JsonQuizOptionModel())->GetByKeyValue('json_quiz_id', $quizId);

        if (!empty($jsonQuizOptionRecord))
        {
            $jsonQuizOptionRecord['page_ranges'] = JsonQuizOption::ExplodeRanges($jsonQuizOptionRecord['page_ranges']);
            $jsonQuizOptionRecord['question_number_ranges'] = JsonQuizOption::ExplodeRanges($jsonQuizOptionRecord['question_number_ranges']);
            $jsonQuizOptionRecord['midasi_number_ranges'] = JsonQuizOption::ExplodeRanges($jsonQuizOptionRecord['midasi_number_ranges']);

            $jsonQuizOptionRecord['section_numbers_json'] = JsonQuizOption::CreateJsonForNumbers($jsonQuizOptionRecord['section_numbers'], 'sec_id_');
            $jsonQuizOptionRecord['midasi_numbers_json'] = JsonQuizOption::CreateJsonForNumbers($jsonQuizOptionRecord['midasi_numbers'], 'midasino_');


            $jsonQuizOptionRecord['manual_frequencies_json'] = JsonQuizOption::CreateJsonForFrequencies($jsonQuizOptionRecord['manual_frequencies']);

            if (empty($jsonQuizOptionRecord['manual_individual_selected_json'])) $jsonQuizOptionRecord['manual_individual_selected_json'] = '{}';

            $individualSelectedJsonData = json_decode($jsonQuizOptionRecord['manual_individual_selected_json'], true);
            $jsonQuizOptionRecord['manual_selected_shomonnos'] = JsonQuizOption::CreateSelectedShomonnos($individualSelectedJsonData);

            if ($jsonQuizOptionRecord['manual_is_individual'] == 1)
            {
                $jsonQuizOptionRecord['manual_syubetu_numbers_json'] = JsonQuizOption::CreateJsonForIndividualSyubetuNumbers($individualSelectedJsonData);
            }
            else
            {
                $jsonQuizOptionRecord['manual_syubetu_numbers_json'] = JsonQuizOption::CreateJsonForSyubetuNumbers($jsonQuizOptionRecord['manual_syubetu_numbers']);
            }

            $jsonQuizOption = $jsonQuizOptionRecord;

            // 回答者がいる場合に設定値を表示する
            if ($notEditable)
            {
                $params = [
                    'titleNo' => $titleNo,
                    'rangeType' => $jsonQuizOption['range_type'],
                    'frequency' => json_decode($jsonQuizOption['manual_frequencies_json'], true),
                    'changeDisplay' => $jsonQuizOption['manual_change_display'],
                    'sectionNos' => $jsonQuizOption['section_numbers'],
                    'midasiNos' => $jsonQuizOption['midasi_numbers'],
                ];

                // paramsに「ページで指定する」値を追加
                for ($i=0; $i<10; ++$i)
                {
                    if (isset($jsonQuizOption['page_ranges'][$i]) && is_array($jsonQuizOption['page_ranges'][$i]))
                    {
                        $params['page_from_' . ($i+1)] = $jsonQuizOption['page_ranges'][$i][0];
                        $params['page_to_' . ($i+1)] = $jsonQuizOption['page_ranges'][$i][1];
                    }
                }

                // paramsに「問題番号で指定する」値を追加
                for ($i=0; $i<10; ++$i)
                {
                    if (isset($jsonQuizOption['question_number_ranges'][$i]) && is_array($jsonQuizOption['question_number_ranges'][$i]))
                    {
                        $params['number_from_' . ($i+1)] = $jsonQuizOption['question_number_ranges'][$i][0];
                        $params['number_to_' . ($i+1)] = $jsonQuizOption['question_number_ranges'][$i][1];
                    }
                }

                // paramsに「見出し語番号で指定する」値を追加
                for ($i=0; $i<10; ++$i)
                {
                    if (isset($jsonQuizOption['midasi_number_ranges'][$i]) && is_array($jsonQuizOption['midasi_number_ranges'][$i]))
                    {
                        $params['midasi_number_from_' . ($i+1)] = $jsonQuizOption['midasi_number_ranges'][$i][0];
                        $params['midasi_number_to_' . ($i+1)] = $jsonQuizOption['midasi_number_ranges'][$i][1];
                    }
                }

                $QuestionController = new QuestionController();
                $result = $QuestionController->GetSyubetuNoNums($params);
                $currentQuestTypeData = $result['data'];

                $jsonQuizOption['manual_syubetu_numbers_map'] = json_decode($jsonQuizOption['manual_syubetu_numbers_json'], true);

                // 章・節で指定する
                if ($jsonQuizOption['range_type'] == 'chapter')
                {
                    $selectedSectionNumbers = (empty($jsonQuizOption['section_numbers']))
                        ? []
                        : explode(',', $jsonQuizOption['section_numbers']);

                    $result = (new ChapterController())->CreateChapter($titleNo);

                    if (!empty($result["chapters"]))
                    {
                        foreach ($result["chapters"] as $chapter)
                        {
                            if (!isset($chapter['children']))
                            {
                                $myId = str_replace('sec_id_', '', $chapter['id']);
                                if (in_array($myId, $selectedSectionNumbers, true))
                                {
                                    $currentChapterData[] = $chapter;
                                }
                            }
                            else
                            {
                                $children = [];
                                foreach ($chapter['children'] as $section)
                                {
                                    $myId = str_replace('sec_id_', '', $section['id']);
                                    if (in_array($myId, $selectedSectionNumbers, true))
                                    {
                                        $children[] = $section;
                                    }
                                }

                                if (!empty($children))
                                {
                                    $chapter['children'] = $children;
                                    $currentChapterData[] = $chapter;
                                }
                            }
                        }
                    }
                }

                // 見出し語を個別に指定する
                if ($jsonQuizOption['range_type'] == 'midasi')
                {
                    $selectedMidasiNumbers = (empty($jsonQuizOption['midasi_numbers']))
                        ? []
                        : explode(',', $jsonQuizOption['midasi_numbers']);

                    $result = (new MidasiNoController())->CreateMidasiNo($titleNo);

                    if (!empty($result['midasinos']))
                    {
                        foreach ($result['midasinos'] as $midasino)
                        {
                            $myId = str_replace('midasino_', '', $midasino['id']);
                            if (in_array($myId, $selectedMidasiNumbers, true))
                            {
                                $currentMidasiData[] = $midasino;
                            }
                        }
                    }
                }

                // 出題頻度
                if ($currentBookData['frequency_flg'] == 1)
                {
                    $result = $QuestionController->GetFrequencyData($titleNo);

                    if (!empty($result["data"]))
                    {
                        foreach ($result["data"] as $data)
                        {
                            if (in_array($data['FREQUENCYNO'], $params['frequency'], true))
                            {
                                $currentFrequencyData[] = $data;
                            }
                        }
                    }
                }

                if ($jsonQuizOption['manual_is_individual'] == 1)
                {
                    $shomonNos = empty($jsonQuizOption['manual_syomon_numbers'])
                        ? []
                        : explode(',', $jsonQuizOption['manual_syomon_numbers']);
                    $records = (new QuestionModel($titleNo))->GetsForManualIndividualByShomonNos($shomonNos);
                    if (!empty($records))
                    {
                        $isSyubetuAndLevel = (!empty($jsonQuizOption['manual_change_display']));

                        foreach ($records as $record)
                        {
                            $myKey = ($isSyubetuAndLevel) ? $record['SYUBETUNO'] . '_' . $record['LEVELNO'] : $record['SYUBETUNO'];

                            if (!isset($currentSyomonDataMap[$myKey])) $currentSyomonDataMap[$myKey] = [];

                            $currentSyomonDataMap[$myKey][] = $record;
                        }
                    }
                }
            }
        }

        $quiz = json_decode($jsonQuiz['json'], true);
        $total = $quiz['total'];
    }
    else
    {
        header('Location: index.php');
        exit;
    }
}

// フォルダ取得
$JsonQuizFolderController = new JsonQuizFolderController($teacher->school_id);
$currentFolder = $JsonQuizFolderController->GetFolder($parentFolderId);
$folderListOptions = $JsonQuizFolderController->CreateFolderListOptions($parentFolderId);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('isShuffle', true);
$smarty->assign('isUpdate', $isUpdate);
$smarty->assign('quizId', $quizId);
$smarty->assign('bookList', $bookList);
$smarty->assign('bookData', json_encode($jsonData));
$smarty->assign('jsonQuiz', $jsonQuiz);
$smarty->assign('jsonQuizOption', $jsonQuizOption);
$smarty->assign('isAnswered', $isAnswered);
$smarty->assign('isOtherTeacher', $isOtherTeacher);
$smarty->assign('notEditable', $notEditable);
$smarty->assign('enableCopy', $enableCopy);
$smarty->assign('titleNo', $titleNo);
$smarty->assign('total', $total);
$smarty->assign('isEnglishBookSelect', $isEnglishBookSelect);
$smarty->assign('currentBookData', $currentBookData);
$smarty->assign('currentQuestTypeData', $currentQuestTypeData);
$smarty->assign('currentFrequencyData', $currentFrequencyData);
$smarty->assign('currentChapterData', $currentChapterData);
$smarty->assign('currentMidasiData', $currentMidasiData);
$smarty->assign('currentSyomonDataMap', $currentSyomonDataMap);
$smarty->assign('currentFolder', $currentFolder);
$smarty->assign('folderListOptions', $folderListOptions);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_quiz_create.html');