<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Controllers\TestController;
use IizunaLMS\Models\QuestionModel;
use IizunaLMS\Models\BookModel;
use IizunaLMS\Models\FrequencyModel;
use IizunaLMS\Helpers\LogHelper;

class QuestionController
{
    /**
     * @param $params
     * @return array
     */
    public function GetSyubetuNoNums($params)
    {
        $titleNo = $params["titleNo"];

        $TestController = $this->GetTestController();
        $TestController->SetParams($params);
        $rangeData = $TestController->CreateRangeData();
        $frequency = (!empty($params["frequency"])) ? $params["frequency"] : [];
        $changeDisplay = (!empty($params["changeDisplay"])) ? $params["changeDisplay"] : null;

        $data = [];

        if (!empty($changeDisplay))
        {
            $data = $this->GetQuestionModel($titleNo)->GetSyubetuNumsWithLevel($rangeData, $frequency, $changeDisplay);
        }
        else
        {
            $data = $this->GetQuestionModel($titleNo)->GetSyubetuNums($rangeData, $frequency);
        }

        return [
            "error" => ERROR_NONE,
            "data" => $data
        ];
    }

    /**
     * @param $titleNo
     * @return array
     */
    public function GetFrequencyData($titleNo)
    {
        $data = $this->GetFrequencyModel($titleNo)->Gets();

        if (!empty($data))
        {
            $data[] = [
                "FREQUENCYNO" => "0",
                "NAME" => "その他"
            ];
        }

        return [
            "error" => ERROR_NONE,
            "data" => $data
        ];
    }

    /**
     * @param $params
     * @return array
     */
    public function GetIndividualQuestions($params)
    {
        $titleNo = $params["titleNo"];
        $syubetuNo = $params["syubetuNo"];
        $levelNo = (!empty($params["levelNo"])) ? $params["levelNo"] : null;
        $frequency = (!empty($params["frequency"])) ? $params["frequency"] : [];

        $book = $this->GetTCBookModel()->GetBook($titleNo);

        $TestController = $this->GetTestController();
        $TestController->SetParams($params);
        $rangeData = $TestController->CreateRangeData();

        $records = $this->GetQuestionModel($titleNo)->GetIndividualQuestions($syubetuNo, $levelNo, $frequency, $rangeData, $book);

        $data = [];

        // データを整形しつつ必要なデータを入れる
        for ($i=0; $i<count($records); ++$i)
        {
            $record = $records[$i];

            if ($book["question_no_flg"] == 1)
            {
                $record["QUESTIONNO"] = $record["MIDASINO"];
            }

            if ($book["midasi_flg"] == 1)
            {
                $record["MIDASI"] = ($record["MIDASINO"] != 0)
                                  ? $record["MIDASINO"] . ":" . $record["MIDASINAME"]
                                  : "";
            }

            $data[] = $record;
        }

        // Keys
        $keys = [];

        if ($book["question_no_flg"] == 1) $keys[] = "QUESTIONNO";
        if ($book["level_flg"] == 1)       $keys[] = "LEVEL";
        if ($book["frequency_flg"] == 1)   $keys[] = "FREQUENCY";
        if ($book["midasi_flg"] == 1)      $keys[] = "MIDASI";

        $keys = array_merge($keys, ["BUN", "ANSWERFROM"]);

        return [
            "error" => ERROR_NONE,
            "keys" => $keys,
            "data" => $data
        ];
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?QuestionModel $_QuestionModel = null;
    private ?SyubetuModel $_SyubetuModel = null;
    private ?TestController $_TestController = null;
    private ?BookModel $_TCBookModel = null;
    private ?FrequencyModel $_FrequencyModel = null;

    private function GetQuestionModel($titleNo): ?QuestionModel
    {
        if ($this->_QuestionModel != null) return $this->_QuestionModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_QuestionModel = new QuestionModel($titleNo);

        return $this->_QuestionModel;
    }

    private function GetSyubetuModel($titleNo): ?SyubetuModel
    {
        if ($this->_SyubetuModel != null) return $this->_SyubetuModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_SyubetuModel = new SyubetuModel($titleNo);

        return $this->_SyubetuModel;
    }

    private function GetTestController(): ?TestController
    {
        if ($this->_TestController != null) return $this->_TestController;

        $this->_TestController = new TestController();

        return $this->_TestController;
    }

    private function GetTCBookModel(): ?BookModel
    {
        if ($this->_TCBookModel != null) return $this->_TCBookModel;

        $this->_TCBookModel = new BookModel();

        return $this->_TCBookModel;
    }

    private function GetFrequencyModel($titleNo): ?FrequencyModel
    {
        if ($this->_FrequencyModel != null) return $this->_FrequencyModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_FrequencyModel = new FrequencyModel($titleNo);

        return $this->_FrequencyModel;
    }

    /**
     * @param QuestionModel $QuestionModel
     */
    public function AttachQuestionModel(QuestionModel $QuestionModel)
    {
        $this->_QuestionModel = $QuestionModel;
    }

    /**
     * @param SyubetuModel $SyubetuModel
     */
    public function AttachSyubetuModel(SyubetuModel $SyubetuModel)
    {
        $this->_SyubetuModel = $SyubetuModel;
    }

    /**
     * @param TestController $TestController
     */
    public function AttachTestController(TestController $TestController)
    {
        $this->_TestController = $TestController;
    }

    /**
     * @param BookModel $TCBookModel
     */
    public function AttachTCBookModel(BookModel $TCBookModel)
    {
        $this->_TCBookModel = $TCBookModel;
    }
}