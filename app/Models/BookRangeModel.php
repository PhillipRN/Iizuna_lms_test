<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class BookRangeModel
{
    private $_tableName = "book_range";

    /**
     * データ全削除
     */
    function TruncateRecord()
    {
        $sql = <<<SQL
TRUNCATE {$this->_tableName} 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $title_no
     * @param $page_min
     * @param $page_max
     * @param $midasi_no_min
     * @param $midasi_no_max
     * @param $question_no_flg
     * @param $chapter_flg
     * @param $midasi_no_flg
     * @param $midasi_flg
     * @param $level_flg
     * @param $frequency_flag
     * @return bool
     */
    function AddRecord($title_no, $page_min, $page_max, $midasi_no_min, $midasi_no_max, $question_no_flg, $chapter_flg, $midasi_no_flg, $midasi_flg, $level_flg, $frequency_flg)
    {
        $sql = <<<SQL
INSERT INTO
{$this->_tableName} 
(title_no, page_min, page_max, midasi_no_min, midasi_no_max, question_no_flg, chapter_flg, midasi_no_flg, midasi_flg, level_flg, frequency_flg) VALUES
(:title_no, :page_min, :page_max, :midasi_no_min, :midasi_no_max, :question_no_flg, :chapter_flg, :midasi_no_flg, :midasi_flg, :level_flg, :frequency_flg)
SQL;

        $pdo = PDOHelper::GetPDO();

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $title_no);
        $sth->bindValue(':page_min', $page_min);
        $sth->bindValue(':page_max', $page_max);
        $sth->bindValue(':midasi_no_min', $midasi_no_min);
        $sth->bindValue(':midasi_no_max', $midasi_no_max);
        $sth->bindValue(':question_no_flg', $question_no_flg);
        $sth->bindValue(':chapter_flg', $chapter_flg);
        $sth->bindValue(':midasi_no_flg', $midasi_no_flg);
        $sth->bindValue(':midasi_flg', $midasi_flg);
        $sth->bindValue(':level_flg', $level_flg);
        $sth->bindValue(':frequency_flg', $frequency_flg);

        PDOHelper::ExecuteWithTry($sth);

        return true;
    }
}