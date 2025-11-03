<?php
global $quizId;
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Controllers\TestController;
use IizunaLMS\Datas\JsonQuizOption;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\JsonQuizOptionModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizData;
use IizunaLMS\Onigiri\OnigiriJsonQuiz;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

if (isset($_POST["quiz_id"])) {
    $params = RequestHelper::GetPostParams();

    if (empty($params['quiz_id'])) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_COPY_INVALID_PARAMETER);
    }

    $quizId = $params['quiz_id'];

    $JsonQuizController = new JsonQuizController();
    $jsonQuiz = $JsonQuizController->Get($quizId);

    if (empty($jsonQuiz)) {
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_COPY_INVALID_PARAMETER);
    }

    foreach ($jsonQuiz as $key => $value)
    {
        switch ($key)
        {
            case "language_type":
            case "title_no":
            case "parent_folder_id":
            case "time_limit":
                $paramsKey = $key;

                if ($key == 'title_no') $paramsKey = 'titleNo';
                else if ($key == 'language_type') $paramsKey = 'selectBookType';

                $params[$paramsKey] = $value;
                break;
        }
    }

    // NOTE isShuffle の値は固定
    $params['isShuffle'] = 1;

    $quiz = json_decode($jsonQuiz['json'], true);
    $params['total'] = $quiz['total'];

    $jsonQuizOptionRecord = (new JsonQuizOptionModel())->GetByKeyValue('json_quiz_id', $quizId);

    foreach ($jsonQuizOptionRecord as $key => $value)
    {
        switch ($key)
        {
            case 'mode':
                $params[$key] = $value;
                $params['manualMode'] = $value;
                break;

            case 'range_type':
            case 'section_numbers':
            case 'midasi_numbers':
            case 'is_show_question_no':
            case 'is_show_midasi_no':
            case 'manual_is_individual':
            case 'manual_change_display':
            case 'manual_syomon_numbers':
                $paramsKey = $key;

                if ($key == 'range_type') $paramsKey = 'rangeType';
                else if ($key == 'section_numbers') $paramsKey = 'sectionNos';
                else if ($key == 'midasi_numbers') $paramsKey = 'midasiNos';
                else if ($key == 'is_show_question_no') $paramsKey = 'showQuestionNo';
                else if ($key == 'is_show_midasi_no') $paramsKey = 'showMidasiNo';
                else if ($key == 'manual_is_individual') $paramsKey = 'selectIndividual';
                else if ($key == 'manual_change_display') $paramsKey = 'changeDisplay';
                else if ($key == 'manual_syomon_numbers') $paramsKey = 'syomonNos';

                $params[$paramsKey] = $value;
                break;

            case 'page_ranges':
            case 'question_number_ranges':
            case 'midasi_number_ranges':
            {
                $ranges = JsonQuizOption::ExplodeRanges($jsonQuizOptionRecord[$key]);

                for ($i=0; $i<=9; ++$i)
                {
                    if (!isset($ranges[$i]) || !is_array($ranges[$i])) continue;

                    $value0 = $ranges[$i][0];
                    $value1 = (isset($ranges[$i][1])) ? $ranges[$i][1] : '';

                    $number = $i+1;
                    
                    $paramsPrefix = '';
                    
                    if ($key == 'page_ranges') $paramsPrefix = 'page';
                    else if ($key == 'question_number_ranges') $paramsPrefix = 'number';
                    else if ($key == 'midasi_number_ranges') $paramsPrefix = 'midasi_number';
                    
                    $params["{$paramsPrefix}_from_{$number}"] = $value0;
                    $params["{$paramsPrefix}_to_{$number}"] = $value1;
                }
                break;
            }

            case 'sort':
                $params[$key] = ($value == 0) ? 'random' : 'asc';
                break;
        }
    }


    $individualSelectedJsonData = json_decode($jsonQuizOptionRecord['manual_individual_selected_json'], true);

    if ($jsonQuizOptionRecord['manual_is_individual'] == 1)
    {
        $params['individualSelected'] = $jsonQuizOptionRecord['manual_individual_selected_json'];
    }
    else
    {
        $manualSyubetuNumbersJson = JsonQuizOption::CreateJsonForSyubetuNumbers($jsonQuizOptionRecord['manual_syubetu_numbers']);

        // マニュアルモード 今回の出題数
        $manualSyubetuNumbers = json_decode($manualSyubetuNumbersJson, true);
        if (is_array($manualSyubetuNumbers))
        {
            foreach ($manualSyubetuNumbers as $key => $value)
            {
                $params["syubetu_num_$key"] = $value;
            }
        }
    }

    // マニュアルモード 出題頻度
    $manualFrequenciesJson = JsonQuizOption::CreateJsonForFrequencies($jsonQuizOptionRecord['manual_frequencies']);
    $params['frequency'] = json_decode($manualFrequenciesJson, true);

    $TestController = new TestController();
    $result = $TestController->CreateTest($params);

    if ($result['error'] == Error::ERROR_NONE)
    {
        PDOHelper::GetPDO()->beginTransaction();

        $teacher = TeacherLoginController::GetTeacherData();
        $jsonQuizResult = $JsonQuizController->Add($teacher->id, $params['title'], $params, $result['language_type'], $result['data']);

        $result['error'] = $jsonQuizResult['error'];

        if ($result['error'] == Error::ERROR_NONE) PDOHelper::GetPDO()->commit();
        else                                       PDOHelper::GetPDO()->rollBack();
    }

    if ($result['error'] == Error::ERROR_NONE)
    {
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else
    {
        DisplayJsonHelper::ShowErrorAndExit($result['error']);
    }
}

DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_JSON_QUIZ_COPY_INVALID_PARAMETER);