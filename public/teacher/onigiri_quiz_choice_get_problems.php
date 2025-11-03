<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Onigiri\OnigiriQuiz;

if (!TeacherLoginController::IsLogin()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);

$teacher = TeacherLoginController::GetTeacherData();
if (!(new LmsTicketLoader())->HaveOnigiriTicket($teacher->id)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

if (isset($_POST["data"])) {
    $params = RequestHelper::GetPostParams();

    $records = [];
    $ids = [];

    for ($i=0; $i<count($params['data']); ++$i) {
        $myParams = $params['data'][$i];
        $genre = $myParams['genre'];
        $learning_range_level = $myParams['learning_range_level'];
        $stage = $myParams['stage'];

        $wordRecords = (new OnigiriQuiz())->GetWords($genre, $learning_range_level, $stage);

        foreach ($wordRecords as $wordRecord) {
            $id = $wordRecord['id'];

            if (in_array($id, $ids)) continue;

            $ids[] = $id;

            $records[] = [
                'id' => $id,
                'word' => $wordRecord['word'],
                'mean' => $wordRecord['mean'],
            ];
        }
    }

    $words = array_column($records, 'word');
    array_multisort($words, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $records);

    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'records' => $records,
    ]);
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_QUIZ_CHOICE_GET_PROBLEMS_PARAMETER_ERROR);
}