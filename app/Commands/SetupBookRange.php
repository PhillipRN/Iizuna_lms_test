<?php

namespace IizunaLMS\Commands;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\BookRangeModel;
use IizunaLMS\Models\ChapterModel;
use IizunaLMS\Models\MidasiNoModel;
use IizunaLMS\Models\QuestionModel;
use IizunaLMS\Models\TCBookModel;

class SetupBookRange
{
    private TCBookModel $_TCBookModel;
    private BookRangeModel $_BookRangeModel;

    function Setup()
    {
        $this->_TCBookModel = new TCBookModel();
        $this->_BookRangeModel = new BookRangeModel();

        $this->_BookRangeModel->TruncateRecord();
        $pdo = PDOHelper::GetPDO();

        try {
            $pdo->beginTransaction();

            $this->SetupWithFlag(FLAG_ENGLISH_BOOK);
            $this->SetupWithFlag(FLAG_JAPANESE_BOOK);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }

    private function SetupWithFlag($flag)
    {
        // 本一覧を取得
        $bookList = $this->_TCBookModel->GetBookListByFlag($flag);

        // 本ごとに問題のテーブルを取得し、データを作る
        foreach ($bookList as $key => $book)
        {
            $TITLENO = $book["TITLENO"];
            $QuestionModel = new QuestionModel($TITLENO);

            if (!$QuestionModel->ExistTable())
            {
                echo "$TITLENO のテーブルは存在しないためスキップします\n";
                continue;
            }

            // ページだけ対象外、見出しNoだけ対象外のレコードが除外されないよう別々に取得する
            $pageMinMax = $QuestionModel->GetPageMinMAx();
            $midasiNoMinMax = $QuestionModel->GetMidasiNoMinMAx();

            // 「問題番号で指定する」が可能か確認
            $nonEmptyMidasiNoRecords = $QuestionModel->GetNonEmptyMidasiNoRecords();
            $questionNoFlag = ($book["MIDASILABELFLG"] != 0 && empty($nonEmptyMidasiNoRecords) == false) ? 1 : 0;

            // 「章・節で指定する」が可能か確認
            $chapterFlag = 0;
            if ($QuestionModel->ExistChapterTable())
            {
                $ChapterModel = new ChapterModel($TITLENO);
                $chapters = $ChapterModel->Gets();
                $chapterFlag = (empty($chapters) == false) ? 1 : 0;
            }

            // 「見出し語番号で指定する」が可能か確認
            $nonEmptyMidasiNameRecords = $QuestionModel->GetNonEmptyMidasiNameRecords();
            $midasiNoFlag = (
                empty($nonEmptyMidasiNoRecords) == false &&
                empty($nonEmptyMidasiNameRecords) == false
            ) ? 1 : 0;

            // 「見出し語を個別に指定する」が可能か確認
            $midasiFlag = 0;
            if ($QuestionModel->ExistMidasiTable())
            {
                $MidasiNoModel = new MidasiNoModel($TITLENO);
                $midasiNos = $MidasiNoModel->Gets();
                $midasiFlag = (
                    empty($nonEmptyMidasiNoRecords) == false &&
                    empty($nonEmptyMidasiNameRecords) == false &&
                    empty($midasiNos) == false
                ) ? 1 : 0;
            }

            // レベルを表示可能か確認
            $levelFlag = 0;
            if ($QuestionModel->ExistLevelTable())
            {
                $level = $QuestionModel->GetLevelCount();
                $levelFlag = ($level["count"] > 0) ? 1 : 0;
            }

            // レベルを表示可能か確認
            $frequencyFlag = 0;
            if ($QuestionModel->ExistFrequencyTable())
            {
                $frequency = $QuestionModel->GetFrequencyCount();
                $frequencyFlag = ($frequency["count"] > 0) ? 1 : 0;
            }

            $result = $this->_BookRangeModel->AddRecord(
                $TITLENO,
                $pageMinMax["page_min"],
                $pageMinMax["page_max"],
                $midasiNoMinMax["midasi_no_min"],
                $midasiNoMinMax["midasi_no_max"],
                $questionNoFlag,
                $chapterFlag,
                $midasiNoFlag,
                $midasiFlag,
                $levelFlag,
                $frequencyFlag
            );

            if (!$result)
            {
                echo "ERROR: {$book["TITLENO"]} でエラーが発生しました。(setup_book_range.php)";
                exit(1);
            }
        }
    }
}
