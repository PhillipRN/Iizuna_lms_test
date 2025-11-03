<?php
global $jsonQuiz;
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriQuizModel;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderController;
use IizunaLMS\Schools\OnigiriLearningRangeLoader;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

$currentLmsCodeId = $_GET['lcid'] ?? null;
$isManual = $_GET['manual'] ?? 0;

$quizId = $_GET['quiz_id'] ?? null;
$isUpdate = (!empty($quizId));
$stage = [1];
$ranges = [];
$rangesString = [];
$total = 10;
$title = '';
$isAnswered = false;
$isDelivered = false;
$openDate = '';
$expireDate = '';
$timeLimit = 0;
$type = ($isManual) ? '' : 'random';
$questions = [];
$isOtherTeacher = false;
$notEditable = false;
$parentFolderId = $_GET['parent_folder_id'] ?? 0;

if (!empty($quizId)) {
    $record = (new OnigiriJsonQuizModel())->GetAndResultNumById($quizId);

    if (!empty($record))
    {
        $isOtherTeacher = $record['teacher_id'] != $teacher->id;
        $isAnswered = $record['result_num'] != 0;
        $parentFolderId = $record['parent_folder_id'];

        if (!$isOtherTeacher)
        {
            $notEditable = $isAnswered;
        }
        else
        {
            $recordOwner = (new TeacherModel())->GetById($record['teacher_id']);

            // 自分のテストではなく、同じ学校のテストの場合は閲覧可能
            if ($teacher->school_id == $recordOwner['school_id']) {
                $notEditable = true;
            }
            // それ以外の場合は閲覧不可
            else
            {
                header('Location: index.php');
                exit;
            }
        }

        // 配信済み判定チェック
        $deliveryRecords = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $quizId);

        $currentLmsCodeId = $record['range_lms_code_id'];
        $stage = explode('_', $record['range_stage']);
        $ranges = !empty($record['ranges']) ? explode(',', $record['ranges']) : [];
        $total = $record['total'];
        $title = $record['title'];
        $isDelivered = !empty($deliveryRecords);
        $openDate = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
        $expireDate = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);
        $timeLimit = $record['time_limit'];
        $type = $record['type'];

        if ($isManual) {
            $questions = json_decode($record['json'], true);

            // 単語データを集める
            $wordIds = [];
            foreach ($questions as $question) $wordIds[] = $question['id'];
            $wordIds = array_values(array_unique($wordIds));

            $wordRecords = (new OnigiriQuizModel())->GetsByKeyInValues('id', $wordIds);

            $words = [];
            foreach ($wordRecords as $wordRecord) {
                $words[ $wordRecord['id'] ] = [
                    'word' => $wordRecord['word'],
                    'mean' => $wordRecord['mean'],
                ];
            }

            // 単語データを追加する
            foreach ($questions as &$question) {
                $question['word'] = $words[ $question['id'] ]['word'];
                $question['mean'] = $words[ $question['id'] ]['mean'];
            }
        }

        // すでに回答者がいたり配信済みのテストの場合は、範囲を文字列で表示できるようにする
        if ($isAnswered || $isDelivered) {
            foreach ($ranges as $range) {
                $rangesString[] = OnigiriLearningRangeLoader::GetTitleByRangeString($range);
            }
        }
    }
}

$data = [
    'id' => $quizId,
    'stage' => $stage,
    'ranges' => $ranges,
    'rangesString' => $rangesString,
    'total' => $total,
    'title' => $title,
    'is_answered' => $isAnswered,
    'is_delivered' => $isDelivered,
    'open_date' => $openDate,
    'expire_date' => $expireDate,
    'time_limit' => $timeLimit,
    'type' => $type,
];

$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($teacher->school_id);

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name']
    ];
}

if (empty($currentLmsCodeId)) {
    foreach ($schoolGroups as $schoolGroup) {
        if (!empty($schoolGroup['is_school'])) {
            $currentLmsCodeId = $schoolGroup['lms_code_id'];
            break;
        }
    }
}

$rangeData = (new OnigiriLearningRangeLoader($currentLmsCodeId))->LoadForOnigiriQuizCreatePage();

// フォルダ取得
$JsonQuizFolderController = new OnigiriJsonQuizFolderController($teacher->school_id);
$currentFolder = $JsonQuizFolderController->GetFolder($parentFolderId);
$folderListOptions = $JsonQuizFolderController->CreateFolderListOptions($parentFolderId);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('quizId', $quizId);
$smarty->assign('isUpdate', $isUpdate);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->assign('rangeData', $rangeData);
$smarty->assign('data', $data);
$smarty->assign('isManual', $isManual);
$smarty->assign('questions', $questions);
$smarty->assign('isOtherTeacher', $isOtherTeacher);
$smarty->assign('notEditable', $notEditable);
$smarty->assign('currentFolder', $currentFolder);
$smarty->assign('folderListOptions', $folderListOptions);
$smarty->assign('csrf', CSRFHelper::GenerateKey());
$smarty->display('_onigiri_quiz_create.html');