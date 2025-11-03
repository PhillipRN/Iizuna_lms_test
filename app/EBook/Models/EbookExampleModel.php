<?php

namespace IizunaLMS\EBook\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\ModelBase;

class EbookExampleModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'ebook_example';
    }

    /**
     * @param $titleNo
     * @return array|false
     */
    public function GetVoicePages($titleNo)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  page
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND voice IS NOT NULL 
GROUP BY page 
ORDER BY page ASC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @param $page
     * @return array|false
     */
    public function GetVoiceRecords($titleNo, $page)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  id,
  page,
  english,
  voice
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND page = :page
ORDER BY id ASC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);
        $sth->bindValue(':page', $page, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @param $chapter
     * @return array|false
     */
    public function GetChapterRecords($titleNo, $chapter)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  id,
  english,
  japanese,
  voice
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND chapter = :chapter
ORDER BY id ASC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);
        $sth->bindValue(':chapter', $chapter, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @param $chapterMin
     * @param $chapterMax
     * @return array|false
     */
    public function GetChapterRangeRecords($titleNo, $chapterMin, $chapterMax)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  id,
  english,
  japanese,
  voice
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND chapter >= :chapterMin 
  AND chapter <= :chapterMax
ORDER BY id ASC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);
        $sth->bindValue(':chapterMin', $chapterMin, \PDO::PARAM_INT);
        $sth->bindValue(':chapterMax', $chapterMax, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}