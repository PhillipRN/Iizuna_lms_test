<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Controllers\TestController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;

if (!TeacherLoginController::IsLogin()) {
    header('Content-Type: application/text');
    $result = array(
        'error' => Error::ERROR_NOT_LOGIN
    );
    echo json_encode($result);
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$result = array(
    'error' => Error::ERROR_NONE
);

if (isset($_POST['titleNo']))
{
    $params = RequestHelper::GetPostParams();
    $quizId = $params['quiz_id'] ?? null;
    $loopNum = $params['testNum'] ?? 1;

    // $quizId がある場合は指定更新のため、ループ回数は1回のみ
    if (!empty($quizId)) $loopNum = 1;

    PDOHelper::GetPDO()->beginTransaction();

    for ($i=0; $i<$loopNum; ++$i)
    {
        $TestController = new TestController();
        $result = $TestController->CreateTest($params);

        if ($result['error'] == Error::ERROR_NONE)
        {
            $JsonQuizController = new JsonQuizController();

            // 既に作成済みのデータを取得する
            $jsonQuiz = (!empty($quizId)) ? $JsonQuizController->GetQuizById($quizId) : null;

            $title = $params['title'];

            $jsonQuizResult = [
                'error' => Error::ERROR_NONE
            ];

            if (empty($jsonQuiz))
            {
                if ($loopNum >= 2)
                {
                    // 2つ以上クイズを作る際は連番にする
                    $title .= '_' . ($i + 1);
                }

                $jsonQuizResult = $JsonQuizController->Add($teacher->id, $title, $params, $result['language_type'], $result['data']);
            }
            else
            {
                $jsonQuizResult = $JsonQuizController->Update($teacher->id, $title, $params, $result['language_type'], $result['data'], $jsonQuiz);
            }

            $result['error'] = $jsonQuizResult['error'];
        }

        // エラーが出ている場合は中断
        if ($result['error'] != Error::ERROR_NONE) break;
    }

    if ($result['error'] == Error::ERROR_NONE) PDOHelper::GetPDO()->commit();
    else                                       PDOHelper::GetPDO()->rollBack();
}
else
{
    $result['error'] = Error::ERROR_INVALID_PARAMETERS;
}

if ($result['error'] != Error::ERROR_NONE)
{
    $result['error_message'] = ErrorMessage::GetMessage($result['error']);
}

header('Content-Type: application/text');
echo json_encode($result);