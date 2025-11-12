<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\ExternalCharacterHelper;
use IizunaLMS\Helpers\LogHelper;
use IizunaLMS\Models\AnswerIndexModel;
use IizunaLMS\Models\BookModel;
use IizunaLMS\Models\DaimonModel;
use IizunaLMS\Models\OtherAnswerModel;
use IizunaLMS\Models\QuestionModel;
use IizunaLMS\Models\SyubetuModel;

class TestController
{
    private $_titleNo;
    private $_params;
    private $_daimonData;
    private $_daimonStrings = array(
        "A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z",
        "a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"
    );

    private $_daimonIndex = 0;
    private $_syomonNum = 1;
    private $_formItemBodys = [
        "class" => "クラス（半角）",
        "number" => "番号（半角）",
        "name" => "氏名（全角，姓字と名前の間に全角スペース入れる）",
    ];
    private $_isShuffle;

    /**
     * @param $params
     */
    public function SetParams($params)
    {
        $this->_params = $params;
        $this->_titleNo = $params["titleNo"];
        $this->_isShuffle = $params["isShuffle"] ?? true;
    }

    /**
     * @param $params
     * @return array
     */
    public function CreateTest($params)
    {
        $this->SetParams($params);

        if (!$this->ValidateTotalNum())
        {
            return array(
                "data" => array(),
                "error" => Error::ERROR_INVALID_TOTAL
            );
        }

        $book = $this->GetTCBookModel()->GetBook($this->_titleNo);

        $shomonNos = [];

        // 範囲データ生成
        $rangeData = $this->CreateRangeData();
        $syubetuNos = [];

        // マニュアルモード
        if ($this->IsManualMode())
        {
            // 個別選択モード
            if (!empty($this->_params["selectIndividual"]))
            {
                if (!empty($this->_params["syomonNos"]))
                {
                    $shomonNos = explode(",", $this->_params["syomonNos"]);

                    // 重複除去
                    $shomonNos = array_unique($shomonNos);
                }
            }
            else
            {
                $syubetuNos = $this->GenerateSyubetuNos();
                $shomonNos = $this->ExtractManualQuestions($syubetuNos, $rangeData);
            }
        }

        // 同時出題禁止など制限されたデータを削除する
        if (!empty($shomonNos))
        {
            $shomonNos = $this->RemoveRestrictedShomonNos($shomonNos);
        }

        $remainNum = $this->_params["total"] - count($shomonNos);

        if ($remainNum > 0)
        {
            // 個別選択モードで問題が不足してしまった場合はエラー扱いにする
            if ($this->IsManualMode() && !empty($this->_params["selectIndividual"]))
            {
                return array(
                    "data" => array(),
                    "error" => Error::ERROR_INDIVIDUAL_TOTAL_MISMATCH
                );
            }

            // マニュアルモードで種別Noが指定されている場合
            if ($this->IsManualMode() && !empty($syubetuNos))
            {
                $shomonNos = $this->ExtractManualQuestionsNoDuplication($syubetuNos, $rangeData, $shomonNos, $remainNum);
            }

            // おまかせモード、またはマニュアルモードで種別Noが指定されていない場合で且つ問題数がTotalより不足している場合はおまかせモードで残りを埋める
            if (empty($syubetuNos))
            {
                $tmpShomonNos = $this->ExtractRecommendQuestions($remainNum, $rangeData, $shomonNos);
                $shomonNos = array_merge($shomonNos, $tmpShomonNos);
            }
        }

        // テストフォームデータ生成
        $questions = $this->GenerateTestFormDataByShomonNos($shomonNos);

        if ($questions["error"] != ERROR_NONE)
        {
            return array(
                "data" => array(),
                "error" => $questions["error"]
            );
        }

        $error = $this->CheckError($questions);

        $bookName = $book["name"];
        $formName = "第　回 {$bookName}_" . date("YmdHis");
        $description = $bookName;

        if (!empty($rangeData["rangeName"]))
        {
            $description .= "　" . $rangeData["rangeName"];
        }

        return array(
            "data" => array(
                "bookName" => $bookName,
                "subDirectoryName" => date("Ymd"),
                "description" => $description,
                "questions" => $questions["data"],
                "total" => $questions["total"],
                "formName" => $formName
            ),
            "language_type" => ($this->isEnglishBook()) ? 0 : 1,
            "error" => $error
        );
    }

    /**
     * SHOMONNOからテストフォーム用のデータを生成する
     * @param $shomonNos
     * @return array
     */
    private function GenerateTestFormDataByShomonNos($shomonNos)
    {
        $result = array(
            "data" => array(),
            "total" => 0,
            "error" => ERROR_NONE
        );

        $QuestionModel = $this->GetQuestionModel($this->_titleNo);

        // 絞り込んだ問題の詳細データを取得する
        $selected = $QuestionModel->GetsByShomonNos($shomonNos);
        
        // 別解データを取得する
        $OtherAnswerModel = $this->GetOtherAnswerModel($this->_titleNo);
        $otherAnswers = $OtherAnswerModel->GetsByShomonNos($shomonNos);

        // AnswerIndex データを取得する
        $AnswerIndexModel = $this->GetAnswerIndexModel($this->_titleNo);
        $answerIndexes = $AnswerIndexModel->GetsByShomonNos($shomonNos);

        // 大問データ取得
        $daimonDataResult = $this->LoadDaimonData($selected);

        if ($daimonDataResult["error"] != ERROR_NONE)
        {
            $result["error"] = $daimonDataResult["error"];
            return $result;
        }

        $this->SetDaimonData($daimonDataResult["data"]);

        // ソート前にシャッフルして一旦並び順をバラバラにする
        shuffle($selected);

        // ソートする
        $selected = $this->SortData($selected);

        $currentDaimonNo = "";
        $result["total"] = count($selected);

        // 先にクラス氏名を追加する
//        $result["data"][] = array(
//            "body" => $this->_formItemBodys["class"],
//            "type" => GAS_TYPE_TEXT_ITEM_NO_POINTS,
//            "require" => 1
//        );
//
//        $result["data"][] = array(
//            "body" => $this->_formItemBodys["number"],
//            "type" => GAS_TYPE_TEXT_ITEM_NO_POINTS,
//            "require" => 1
//        );
//
//        $result["data"][] = array(
//            "body" => $this->_formItemBodys["name"],
//            "type" => GAS_TYPE_TEXT_ITEM_NO_POINTS,
//            "require" => 1
//        );

        $countDaimonNos = [];

        // 使いやすいようまず大問ごとの問題数を数える
        for ($i=0; $i<count($selected); ++$i)
        {
            $data = $selected[$i];
            $daimonNo = $data["DAIMONNO"];

            // 先頭にいれるテキストだけの問題も問題数にカウントされてしまうため、2からスタートする
//            $countDaimonNos[$daimonNo] =
//                (isset($countDaimonNos[$daimonNo]))
//                    ? $countDaimonNos[$daimonNo] + 1
//                    : 2;

            $countDaimonNos[$daimonNo] =
                (isset($countDaimonNos[$daimonNo]))
                    ? $countDaimonNos[$daimonNo] + 1
                    : 1;
        }

        for ($i=0; $i<count($selected); ++$i)
        {
            $data = $selected[$i];
            $daimonNo = $data["DAIMONNO"];
            $syomonNo = $data["SYOMONNO"];

            // DAIMONNOが変わるタイミングで大問用ののデータを挿入する
            if ($currentDaimonNo == "" || $currentDaimonNo != $daimonNo)
            {
                $currentDaimonNo = $daimonNo;
                $result["data"][] = $this->CreateDaimonData($daimonNo, $countDaimonNos[$daimonNo]);
            }

            // 正答位置指定追加
            $data['answer_index'] = $this->FilterAnswerIndex($answerIndexes, $syomonNo);

            $questionData = $this->MoldQuestionData($data);

            $questionData['other_answers'] = $this->FilterOtherAnswers($otherAnswers, $syomonNo);

            $result["data"][] = $questionData;
        }
        
        return $result;
    }

    /**
     * 小問Noの別解に絞り込む
     * @param $otherAnswers
     * @param $syomonNo
     * @return array
     */
    private function FilterOtherAnswers($otherAnswers, $syomonNo)
    {
        $result = [];

        foreach ($otherAnswers as $data)
        {
            if ($data['syomon_no'] == $syomonNo) $result[] = $data['answer'];
        }

        return $result;
    }

    /**
     * @param $answerIndexes
     * @param $syomonNo
     * @return int|mixed
     */
    private function FilterAnswerIndex($answerIndexes, $syomonNo)
    {
        foreach ($answerIndexes as $data)
        {
            if ($data['syomon_no'] == $syomonNo) {
                return intval($data['answer_index']);
            }
        }

        return 0;
    }

    /**
     * 種別Noのパラメータを抽出します
     * @return array
     */
    private function GenerateSyubetuNos()
    {
        $syubetuNos = [];

        foreach ($this->_params as $key => $val)
        {
            if (empty($val)) continue;

            if (preg_match("/^syubetu_num_([0-9]+)$/", $key,$matches))
            {
                $syubetuNos[] = [
                    "syubetuNo" => $matches[1],
                    "num" => $val,
                ];
            }
            else if (preg_match("/^syubetu_num_([0-9]+)_([0-9]+)$/", $key,$matches))
            {
                $syubetuNos[] = [
                    "syubetuNo" => $matches[1],
                    "levelNo" => $matches[2],
                    "num" => $val,
                ];
            }
        }

        return $syubetuNos;
    }

    /**
     * マニュアルモードで選択された問題を抽出する
     * @param $syubetuNos
     * @param $rangeData
     * @return array
     */
    private function ExtractManualQuestions($syubetuNos, $rangeData)
    {
        $shomonNos = [];

        for ($i=0; $i<count($syubetuNos); ++$i)
        {
            $syubetuNo = $syubetuNos[$i]["syubetuNo"];
            $levelNo = (!empty($syubetuNos[$i]["levelNo"])) ? $syubetuNos[$i]["levelNo"] : null;
            $frequency = (!empty($this->_params["frequency"])) ? $this->_params["frequency"] : [];
            $num = $syubetuNos[$i]["num"];

            $records = $this->GetQuestionModel($this->_titleNo)->GetRandomRecordsBySyubetuNo($syubetuNo, $levelNo, $frequency, $num, $rangeData);

            foreach ($records as $record)
            {
                $shomonNos[] = $record["SYOMONNO"];
            }
        }

        return $shomonNos;
    }

    /**
     * 重複禁止を考慮しつつ問題を抽出する
     * @param $syubetuNos
     * @param $rangeData
     * @param $shomonNos
     * @param $remainNum
     * @return array
     */
    private function ExtractManualQuestionsNoDuplication($syubetuNos, $rangeData, $shomonNos, $remainNum)
    {
        $QuestionModel = $this->GetQuestionModel($this->_titleNo);
        $questions     = $QuestionModel->GetsForExtractByShomonNos($shomonNos);

        $revNos      = [];
        $revpNos     = [];
        $myShomonNos = [];
        $shubetuNosCount = [];

        foreach ($syubetuNos as $syubetuNoData)
        {
            $key = $syubetuNoData['syubetuNo'];
            $shubetuNosCount[$key] = 0;
        }

        // すでにある問題から重複禁止のNoを収集しつつ、種別Noごとの必要問題数を求める
        foreach ($questions as $val) {
            $shomonNo  = $val["SYOMONNO"];
            $syubetuNo = $val["SYUBETUNO"];
            $revNo     = $val["REVNO"];
            $revpNo    = $val["REVPNO"];

            // 重複禁止
            if ($revNo  != 0)
            {
                $revNos[]  = $revNo;
            }

            // 個別重複禁止
            if ($revpNo  != 0)
            {
                $revpNos[]  = $revpNo;
            }

            $myShomonNos[] = $shomonNo;

            if (!isset($shubetuNosCount[$syubetuNo])) $shubetuNosCount[$syubetuNo] = 1;
            else $shubetuNosCount[$syubetuNo] += 1;
        }

        for ($i=0; $i<count($syubetuNos); ++$i)
        {
            $syubetuNoData = $syubetuNos[$i];
            $syubetuNo = $syubetuNoData["syubetuNo"];
            $levelNo = (!empty($syubetuNoData["levelNo"])) ? $syubetuNoData["levelNo"] : null;
            $frequency = (!empty($this->_params["frequency"])) ? $this->_params["frequency"] : [];
            $syubetuRemainNum = $syubetuNoData["num"] - $shubetuNosCount[$syubetuNo];

            if ($syubetuRemainNum == 0) continue;

            for ($loop=0; $loop<$syubetuRemainNum; ++$loop)
            {
                $record = $this->GetQuestionModel($this->_titleNo)->GetRandomRecordBySyubetuNoAndNoDuplication($syubetuNo, $levelNo, $frequency, $rangeData, $myShomonNos, $revNos);

                if (empty($record)) continue;

                $myShomonNos[] = $record['SYOMONNO'];
                $revNos[] = $record['REVNO'];
                $revpNos[] = $record['REVPNO'];
            }
        }

        // DEBUG 生成された問題数チェック用
//        {
//            $questions = $QuestionModel->GetsForExtractByShomonNos($myShomonNos);
//
//            $revNos = [];
//            $revpNos = [];
//            $shubetuNosCount = [];
//
//            // すでにある問題から重複禁止のNoを収集しつつ、種別Noごとの必要問題数を求める
//            foreach ($questions as $val) {
//                $shomonNo = $val["SYOMONNO"];
//                $syubetuNo = $val["SYUBETUNO"];
//                $revNo = $val["REVNO"];
//                $revpNo = $val["REVPNO"];
//
//                // 重複禁止
//                if ($revNo != 0) {
//                    $revNos[] = $revNo;
//                }
//
//                // 個別重複禁止
//                if ($revpNo != 0) {
//                    $revpNos[] = $revpNo;
//                }
//
//                if (!isset($shubetuNosCount[$syubetuNo])) $shubetuNosCount[$syubetuNo] = 1;
//                else $shubetuNosCount[$syubetuNo] += 1;
//            }
//
//            var_dump($shubetuNosCount);
//        }

        return $myShomonNos;
    }

    /**
     * @return string
     */
    public function CreateRangeData()
    {
        $rangeData = array(
            "rangeName" => "",
            "column" => "",
            "range" => [],
            "values" => [],
        );

        // 出題の範囲に沿って問題を抽出する
        switch ($this->_params["rangeType"])
        {
            case "page":
                $rangeData["column"] = "question.PAGE";
                $rangeData["range"]  = $this->CreatePageRange();
                break;

            case "questionNo":
                $rangeData["column"] = "question.MIDASINO";
                $rangeData["range"]  = $this->CreateNumberRange();
                break;

            case "midasiNo":
                $rangeData["column"] = "question.MIDASINO";
                $rangeData["range"]  = $this->CreateMidasiNumberRange();
                break;

            case "chapter":
                $rangeData["column"] = "question.SECNO";
                $rangeData["values"] = (!empty($this->_params["sectionNos"]))
                    ? explode(",", $this->_params["sectionNos"])
                    : array();
                break;

            case "midasi":
                $rangeData["column"] = "question.MIDASINO";
                $rangeData["values"] = (!empty($this->_params["midasiNos"]))
                    ? explode(",", $this->_params["midasiNos"])
                    : array();
                break;
        }

        if (count($rangeData["range"]) > 0)
        {
            $rangeNameArray = array();

            foreach ($rangeData["range"] as $key => $range)
            {
                if (isset($range["from"]) && isset($range["to"]))
                {
                    $rangeNameArray[] = $range["from"] . "～" . $range["to"];
                }
                else if (isset($range["from"]))
                {
                    $rangeNameArray[] = $range["from"] . "～";
                }
                else
                {
                    $rangeNameArray[] = "～" . $range["to"];
                }
            }

            $rangeName = join("・", $rangeNameArray);

            switch ($this->_params["rangeType"])
            {
                case "page":
                    $rangeName = "P" . $rangeName;
                    break;

                case "questionNo":
                    $rangeName = "問題番号" . $rangeName;
                    break;

                case "midasiNo":
                    $rangeName = "見出し語" . $rangeName;
                    break;
            }

            $rangeData["rangeName"] = $rangeName;
        }

        return $rangeData;
    }

    /**
     * @return bool
     */
    private function ValidateTotalNum()
    {
        if (empty($this->_params["total"]) || $this->_params["total"] < 1) return false;

        return true;
    }

    /**
     * エラーをチェックする
     * @param $questions
     * @return int
     */
    private function CheckError($questions)
    {
        if ($questions["total"] < $this->_params["total"])
        {
            return Error::ERROR_LACK_OF_TOTAL;
        }

        return ERROR_NONE;
    }

    /**
     * おまかせモードの問題を抽出する
     * @param $num
     * @param $rangeData
     * @param $shomonNos
     * @return array
     */
    private function ExtractRecommendQuestions($num, $rangeData, $shomonNos)
    {
        $this->_daimonIndex = 0;
        $this->_syomonNum = 1;

        $QuestionModel = $this->GetQuestionModel($this->_titleNo);
        $questions     = $QuestionModel->GetsForExtractWithRangeData($rangeData);

        if (empty($questions))
        {
            return array();
        }

        $createNum = min($num, count($questions));

        // 種別データ取得
        $syubetuData = $this->LoadSyubetuData();

        if (empty($syubetuData))
        {
            LogHelper::OutputErrorLog("Syubetsu data is none.");
            exit;
        }

        // 抽出された問題から、RATEに従って設定された問題数に絞り込む
        $extracted = $this->ExtractQuestions($questions, $createNum, $syubetuData, $shomonNos);

        // RATEに設定されているデータが全くない場合
        if (empty($extracted))
        {
            return array();
        }

        // SYOMONNOを集める
        $shomonNos = array();
        foreach ($extracted as $key => $val)
        {
            $shomonNos[] = $val["SYOMONNO"];
        }

        return $shomonNos;
    }

    /**
     * ソートする
     * @param $selected
     * @return array
     */
    private function SortData($selected)
    {
        $result = array();
        $daimonNos = array();
        $syomonNos = array();

        foreach ($selected as $key => $val)
        {
            // ランダムの場合はarray_multisortで余計なソートがされないよう、1個目のキーに現在の順番を持っておく
            $baseData = array(
                "row" => $key
            );
            $result[$key] = array_merge($baseData, $val);

            $daimonNos[$key] = $val["DAIMONNO"];
            $syomonNos[$key] = $val["SYOMONNO"];
        }

        // 出題順: 収録順
        if ($this->_params["sort"] == "asc")
        {
            // DAIMONNOでソートし且つSYOMONNOでソート
            array_multisort($daimonNos, SORT_ASC, $syomonNos, SORT_ASC, $result);
        }
        // 出題順: ランダム
        else
        {
            // DAIMONNOでのみソート
            array_multisort($daimonNos, SORT_ASC, $result);
        }

        return $result;
    }

    /**
     * RATEと指定数に合わせて問題を絞り込む
     * @param $questions
     * @param $createNum
     * @param $syubetuData
     * @param $shomonNos
     * @return array
     */
    private function ExtractQuestions($questions, $createNum, $syubetuData, $shomonNos)
    {
        $syubetuNos = array();

        // 各種別がいくつあるのかカウントする
        foreach ($questions as $key => $question)
        {
            $syubetuNo = $question["SYUBETUNO"];

            if (!isset($syubetuNos[ $syubetuNo ]))
            {
                $syubetuNos[ $syubetuNo ] = 1;
            }
            else
            {
                $syubetuNos[ $syubetuNo ] += 1;
            }
        }

        // 種別データ全体からではなく、抽出された問題の中からRATEの合計を求めて計算していく
        $totalRate = 0;

        foreach ($syubetuData as $key => $val)
        {
            if (!array_key_exists($key, $syubetuNos)) continue;

            $totalRate += $val["RATE"];
        }

        // 各種別をいくつずつ抽出するのか計算する
        $syubetuRemainNum = array();
        $calcCurrentNum = 0;
        $calcCurrentRate = (float)0;

        foreach ($syubetuData as $key => $val)
        {
            if (!array_key_exists($key, $syubetuNos)) continue;

            $calcCurrentRate += ((float)$val["RATE"] / (float)$totalRate) * $createNum;

            $num = (int)$calcCurrentRate - $calcCurrentNum;

            // 種別の数より多い場合は数を調整
            if ($num > $syubetuNos[$key])
            {
                $num = $syubetuNos[$key];
            }

            $syubetuRemainNum[ $val["SYUBETUNO"] ] = $num;

            $calcCurrentNum += $num;
        }

        // 問題をシャッフルして先頭から順にピックアップしていく
        shuffle($questions);

        $result      = [];
        $revNos      = [];
        $revpNos     = [];
        $myShomonNos = [];

        // 既に選択されている shomonNo の revNo などを集め、制限対象となるようにする
        if (!empty($shomonNos))
        {
            $QuestionModel = $this->GetQuestionModel($this->_titleNo);
            $tmpQuestions  = $QuestionModel->GetsForExtractByShomonNos($shomonNos);

            foreach ($tmpQuestions as $key => $val)
            {
                $myShomonNos[] = $val["SYOMONNO"];
                $revNos[]      = $val["REVNO"];
                $revpNos[]     = $val["REVPNO"];
            }
        }

        foreach ($questions as $val)
        {
            $shomonNo = $val["SYOMONNO"];
            $syubetuNo = $val["SYUBETUNO"];
            $revNo = $val["REVNO"];
            $revpNo = $val["REVPNO"];

            if (isset($syubetuRemainNum[ $syubetuNo ]) && $syubetuRemainNum[ $syubetuNo ] > 0)
            {
                // 重複禁止
                if ($revNo  != 0)
                {
                    if (in_array($revNo,  $revNos, true)) continue;
                    $revNos[]  = $revNo;
                }

                // 個別重複禁止
                if ($revpNo  != 0)
                {
                    if (in_array($revpNo,  $myShomonNos, true)) continue;
                    $revpNos[]  = $revpNo;
                }

                // 小問題Noを対象とする個別重複禁止の問題がすでに入っている場合は、対象となっている小問題Noの問題もスキップ
                if (in_array($shomonNo,  $revpNos, true)) continue;

                $syubetuRemainNum[ $syubetuNo ] -= 1;

                $result[] = $val;
                $myShomonNos[] = $shomonNo;

                if (count($result) == $createNum) break;
            }
        }

        // テストが指定数に達している場合はそのまま返す
        if (count($result) == $createNum) return $result;

        foreach ($questions as $val)
        {
            $shomonNo = $val["SYOMONNO"];
            $syubetuNo = $val["SYUBETUNO"];
            $revNo = $val["REVNO"];
            $revpNo = $val["REVPNO"];

            if (in_array($shomonNo,  $myShomonNos, true)) continue;

            // まだ足りない場合は種別ごとの指定数を無視する
            if (isset($syubetuRemainNum[ $syubetuNo ]))
            {
                // 重複禁止
                if ($revNo  != 0)
                {
                    if (in_array($revNo,  $revNos, true)) continue;
                    $revNos[]  = $revNo;
                }

                // 個別重複禁止
                if ($revpNo  != 0)
                {
                    if (in_array($revpNo,  $myShomonNos, true)) continue;
                    $revpNos[]  = $revpNo;
                }

                // 小問題Noを対象とする個別重複禁止の問題がすでに入っている場合は、対象となっている小問題Noの問題もスキップ
                if (in_array($shomonNo,  $revpNos, true)) continue;

                $syubetuRemainNum[ $syubetuNo ] -= 1;

                $result[] = $val;
                $myShomonNos[] = $shomonNo;

                if (count($result) == $createNum) break;
            }
        }

        return $result;
    }

    /**
     * 同時出題禁止など制限されたデータを削除する
     * @param $shomonNos
     * @return array
     */
    private function RemoveRestrictedShomonNos($shomonNos)
    {
        $QuestionModel = $this->GetQuestionModel($this->_titleNo);
        $questions     = $QuestionModel->GetsForExtractByShomonNos($shomonNos);

        $revNos      = [];
        $revpNos     = [];
        $myShomonNos = [];

        foreach ($questions as $key => $val)
        {
            $shomonNo = $val["SYOMONNO"];
            $revNo    = $val["REVNO"];
            $revpNo   = $val["REVPNO"];

            // 重複禁止
            if ($revNo  != 0)
            {
                if (in_array($revNo,  $revNos, true)) continue;
                $revNos[]  = $revNo;
            }

            // 個別重複禁止
            if ($revpNo  != 0)
            {
                if (in_array($revpNo,  $myShomonNos, true)) continue;
                $revpNos[]  = $revpNo;
            }

            // 小問題Noを対象とする個別重複禁止の問題がすでに入っている場合は、対象となっている小問題Noの問題もスキップ
            if (in_array($shomonNo,  $revpNos, true)) continue;

            $myShomonNos[] = $shomonNo;
        }

        return $myShomonNos;
    }

    /**
     * CanvasLMS用にデータを成型する
     * @param $data
     * @return array
     */
    private function MoldQuestionData($data)
    {
        $body = $this->ReplaceTagsForQusetion($data["BUN"]);
        $questionId = $this->_syomonNum;
        ++$this->_syomonNum;
        $answerIdCounter = 0;

        if ((isset($this->_params["showQuestionNo"])) && $this->_params["showQuestionNo"] == 1 && !empty($data["ANSWERFROM"]))
        {
            $body .= " " . $data["ANSWERFROM"];
        }
        else if ((isset($this->_params["showMidasiNo"])) && $this->_params["showMidasiNo"] == 1 && !empty($data["MIDASINO"]))
        {
            $body .= " → " . $data["MIDASINO"];
        }

        $daimonNo = $data["DAIMONNO"];
        $answer = $this->buildAnswerItem($questionId, $answerIdCounter, $this->ReplaceTagsForQusetion($data["ANSBUN"]), 100);

        $result = array(
            "daimon_no" => $daimonNo,
            "question_text" => $body,
            "answers" => [ $answer ],
            "points_possible" => 1
        );

        $result["question_id"] = $questionId;

        // 選択肢がある場合は選択肢をセットする
        if (!empty($data["CHOICES"]))
        {
            $items = explode("／", $data["CHOICES"]);

            // 正答位置に対応するため一旦クリアする
            $result["answers"] = [];

            foreach ($items as $key => $item)
            {
                $result["answers"][] = $this->buildAnswerItem(
                    $questionId,
                    $answerIdCounter,
                    $this->ReplaceTagsForQusetion($item),
                    0
                );
            }

            // 正答位置指定がある場合は途中に挿入する
            if (!empty($data['answer_index']))
            {
                $tmpAnswers = $result["answers"];
                $result["answers"] = [];

                $isPushAnswer = false;
                for ($i=0; $i<count($tmpAnswers); ++$i)
                {
                    if ($i == $data['answer_index'] - 1)
                    {
                        $result["answers"][] = $answer;
                        $isPushAnswer = true;
                    }
                    $result["answers"][] = $tmpAnswers[$i];
                }

                if (!$isPushAnswer) $result["answers"][] = $answer;
            }
            else
            {
                $result["answers"][] = $answer;
                if ($this->_isShuffle) shuffle($result["answers"]);
            }


            $result["question_type"] = ($this->isEnglishBook())
                ? TYPE_MULTIPLE_CHOICE_QUESTION
                : TYPE_VERTICAL_MULTIPLE_CHOICE_QUESTION;

            return $result;
        }
        // それ以外はTYPE_TEXT_ITEM
        else
        {
            $result["question_type"] = ($this->isEnglishBook())
                ? TYPE_SHORT_ANSWER_QUESTION
                : TYPE_VERTICAL_SHORT_ANSWER_QUESTION;

            return $result;
        }
    }

    /**
     * @param int $questionId
     * @param int $counter (by reference)
     * @param string $text
     * @param int $weight
     * @return array
     */
    private function buildAnswerItem($questionId, &$counter, $text, $weight)
    {
        return [
            "answer_id" => sprintf('q%s_a%s', $questionId, $counter++),
            "answer_text" => $text,
            "weight" => $weight
        ];
    }

    /**
     * @param $daimonNo
     * @return array
     */
    private function CreateDaimonData($daimonNo, $count)
    {
        if (empty($this->_daimonData))
        {
            LogHelper::OutputErrorLog("Daimon data is none.");
            exit;
        }

        $daimonPrefix = (!$this->isEnglishBook())
            ? "【" . $this->_daimonStrings[$this->_daimonIndex] . "】 "
            : "[" . $this->_daimonStrings[$this->_daimonIndex] . "] ";
        ++$this->_daimonIndex;

        return array(
            "daimon_no" => $daimonNo,
            "question_type" => TYPE_PAGE_BREAK_ITEM,
            "question_text" => $daimonPrefix . $this->ReplaceTagsForDaimon($this->_daimonData[$daimonNo]["BUN"]),
            "pick_count" => $count
        );
    }

    /**
     * 大問データを読み込む
     * @param $selected
     * @return mixed
     */
    private function LoadDaimonData($selected)
    {
        // 大問Noを集める
        $daimonNos = array();

        for ($i=0; $i<count($selected); ++$i)
        {
            $data = $selected[$i];
            $daimonNo = $data["DAIMONNO"];

            $daimonNos[] = $daimonNo;
        }

        // 重複削除
        $uniqueDaimonNos = array_values( array_unique($daimonNos) );

        $DaimonModel = $this->GetDaimonModel($this->_titleNo);
        $daimonData = $DaimonModel->Gets($uniqueDaimonNos);

        if (empty($daimonData))
        {
            return array(
                "error" => Error::ERROR_NO_DAIMON_DATA,
                "data" => array()
            );
        }

        $result = array(
            "error" => ERROR_NONE,
            "data" => array()
        );

        foreach ($daimonData as $key => $val)
        {
            $result["data"][ $val["DAIMONNO"] ] = $val;
        }

        return $result;
    }

    /**
     * @param $daimonData
     */
    private function SetDaimonData($daimonData)
    {
        $this->_daimonData = $daimonData;
    }

    /**
     * 種別データを読み込み
     * @return array
     */
    private function LoadSyubetuData()
    {
        $SyubetuModel = $this->GetSyubetuModel($this->_titleNo);
        $syubetuData = $SyubetuModel->GetRatedRecords();

        $result = array();

        foreach ($syubetuData as $key => $val)
        {
            $result[ $val["SYUBETUNO"] ] = $val;
        }

        return $result;
    }

    /**
     * @return array
     */
    private function CreatePageRange()
    {
        return $this->CreateRangeFromTo("page");
    }

    /**
     * @return array
     */
    private function CreateNumberRange()
    {
        return $this->CreateRangeFromTo("number");
    }

    /**
     * @return array
     */
    private function CreateMidasiNumberRange()
    {
        return $this->CreateRangeFromTo("midasi_number");
    }

    /**
     * @param $prefix
     * @return array
     */
    private function CreateRangeFromTo($prefix)
    {
        $pageRange = array();

        for ($i=1; $i<=10; ++$i)
        {
            $from = (isset($this->_params["{$prefix}_from_" . $i])) ? $this->_params["{$prefix}_from_" . $i] : null;
            $to   = (isset($this->_params["{$prefix}_to_" . $i]))   ? $this->_params["{$prefix}_to_" . $i]   : null;

            if ($from == null && $to == null) continue;

            // from が入っていて、 to が空の時
            if ($from != null && empty($to) && $to != "0")
            {
                $pageRange[] = array(
                    "from" => $from
                );
                continue;
            }

            // to が入っていて、 from が空の時
            if ($to != null && empty($from) && $from != "0")
            {
                $pageRange[] = array(
                    "to" => $to
                );
                continue;
            }

            if ($from < 0) $from = 0;
            if ($to < 0)   $to = 0;

            if ($from > $to) continue;

            $pageRange[] = array(
                "from" => $from,
                "to" => $to
            );
        }

        return $pageRange;
    }

    /**
     * @param $str
     * @return string|string[]
     */
    private function ReplaceTagsForQusetion($str)
    {
        $result = $str;
        if (!$this->isEnglishBook())
        {
            $result = preg_replace('/…/u', '︙', $result);
        }
        $result = preg_replace('/　/', ($this->isEnglishBook()) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '&nbsp;', $result);
        $result = ExternalCharacterHelper::ReplaceTags($result);
        $result = preg_replace('/(.)<r>([^\/]+)<\/r>/u', '<ruby>$1<rt>$2</rt></ruby>', $result);
        return preg_replace('/<s>([^\/]+)<\/s>/u', '<sup>$1</sup>', $result);
    }

    private function ReplaceTagsForDaimon($str)
    {
//        $result = str_replace("<br>", "　", $str);
//        $result = str_replace("下線部", "【】", $result);
//        $result = str_replace("下線", "【】", $result);
//        return $result;
        return $str;
    }

    /**
     * 英語の書籍かどうか判定する
     * @return bool
     */
    private function isEnglishBook()
    {
        return $this->_params['selectBookType'] == "0";
    }

    /**
     * マニュアルモード判定
     * @return bool
     */
    private function IsManualMode()
    {
        if (empty($this->_params)) false;
        return !empty($this->_params["manualMode"]);
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?BookModel $_TCBookModel = null;
    private ?QuestionModel $_QuestionModel = null;
    private ?DaimonModel $_DaimonModel = null;
    private ?SyubetuModel $_SyubetuModel = null;

    private function GetTCBookModel(): ?BookModel
    {
        if ($this->_TCBookModel != null) return $this->_TCBookModel;

        $this->_TCBookModel = new BookModel();

        return $this->_TCBookModel;
    }

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

    private function GetDaimonModel($titleNo): ?DaimonModel
    {
        if ($this->_DaimonModel != null) return $this->_DaimonModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_DaimonModel = new DaimonModel($titleNo);

        return $this->_DaimonModel;
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

    private ?OtherAnswerModel $_OtherAnswerModel = null;
    private function GetOtherAnswerModel($titleNo): ?OtherAnswerModel
    {
        if ($this->_OtherAnswerModel != null) return $this->_OtherAnswerModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_OtherAnswerModel = new OtherAnswerModel($titleNo);

        return $this->_OtherAnswerModel;
    }

    private ?AnswerIndexModel $_AnswerIndexModel = null;
    private function GetAnswerIndexModel($titleNo): ?AnswerIndexModel
    {
        if ($this->_AnswerIndexModel != null) return $this->_AnswerIndexModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_AnswerIndexModel = new AnswerIndexModel($titleNo);

        return $this->_AnswerIndexModel;
    }

    /**
     * @param BookModel $TCBookModel
     */
    public function AttachTCBookModel(BookModel $TCBookModel)
    {
        $this->_TCBookModel = $TCBookModel;
    }

    /**
     * @param QuestionModel $QuestionModel
     */
    public function AttachQuestionModel(QuestionModel $QuestionModel)
    {
        $this->_QuestionModel = $QuestionModel;
    }

    /**
     * @param DaimonModel $DaimonModel
     */
    public function AttachDaimonModel(DaimonModel $DaimonModel)
    {
        $this->_DaimonModel = $DaimonModel;
    }

    /**
     * @param SyubetuModel $SyubetuModel
     */
    public function AttachSyubetuModel(SyubetuModel $SyubetuModel)
    {
        $this->_SyubetuModel = $SyubetuModel;
    }
}
