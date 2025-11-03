<?php

namespace IizunaLMS\EBook\Models;

use IizunaLMS\EBook\EbookQuiz;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\ModelBase;

class EbookQuizModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'ebook_quiz';
    }

    /**
     * @param $titleNo
     * @param $page
     * @param $genres
     * @param $isInput
     * @return array|false
     */
    public function GetPageRecords($titleNo, $page, $genres, $isInput)
    {
        $pdo = PDOHelper::GetPDO();

        $whereByInput = (empty($isInput)) ? 'AND question_type != ' . EbookQuiz::QUESTION_TYPE_INPUT : '';
        $whereByGenre = '';
        $genresCondition = [];

        if  (!empty($genres))
        {
            $genresCondition = $this->GenerateGenresCondition($genres);
            $whereByGenre = 'AND ' . $genresCondition['where'];
        }

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND page = :page 
  AND question_kind = :question_kind
  {$whereByInput}
  {$whereByGenre}
ORDER BY id ASC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);
        $sth->bindValue(':page', $page, \PDO::PARAM_INT);
        $sth->bindValue(':question_kind', EbookQuiz::QUESTION_KIND_CHECK, \PDO::PARAM_INT);

        if (!empty($whereByGenre))
        {
            foreach ($genresCondition['bind_values'] as $key => $value)
            {
                $sth->bindValue($key, $value);
            }
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * @param $titleNo
     * @param array $questionKinds
     * @param $chapters
     * @param $isInput
     * @return array|false
     */
    public function GetsByQuestionKindsAndChapter($titleNo, array $questionKinds, $chapters, $isInput)
    {
        $pdo = PDOHelper::GetPDO();

        $whereByInput = (empty($isInput)) ? 'AND question_type != ' . EbookQuiz::QUESTION_TYPE_INPUT : '';
        $whereByChapter = '';
        $chaptersCondition = [];

        if  (!empty($chapters))
        {
            $chaptersCondition = $this->GenerateChaptersCondition($chapters);
            $whereByChapter = 'AND ' . $chaptersCondition['where'];
        }

        $bindKeyArray = [];

        for ($i=0; $i<count($questionKinds); ++$i)
        {
            $bindKeyArray[] = ":question_kind_{$i}";
        }

        $bindKeys = implode(',', $bindKeyArray);

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND question_kind IN ({$bindKeys}) 
  {$whereByChapter}
  {$whereByInput}
ORDER BY id ASC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);

        for ($i=0; $i<count($questionKinds); ++$i)
        {
            $sth->bindValue(":question_kind_{$i}", $questionKinds[$i]);
        }

        if (!empty($whereByChapter))
        {
            foreach ($chaptersCondition['bind_values'] as $key => $value)
            {
                $sth->bindValue($key, $value);
            }
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $chapters
     * @return array
     */
    private function GenerateChaptersCondition($chapters): array
    {
        $chapterWheres = [];
        $bindValues = [];
        $countBindValue = 0;

        foreach ($chapters as $chapter)
        {
            if (empty($chapter['chapter'])) continue;

            $myWhere = 'chapter = :chapter_value_' . $countBindValue;
            $bindValues[':chapter_value_' . $countBindValue] = $chapter['chapter'];
            ++$countBindValue;

            # primary_item
            if (empty($chapter['primary_item']))
            {
                $chapterWheres[] = "($myWhere)";
                continue;
            }

            $myWhere .= ' AND primary_item = :primary_item_' . $countBindValue;
            $bindValues[':primary_item_' . $countBindValue] = $chapter['primary_item'];
            ++$countBindValue;

            # secondary_item
            if (empty($chapter['secondary_item']))
            {
                $chapterWheres[] = "($myWhere)";
                continue;
            }

            $myWhere .= ' AND secondary_item = :secondary_item_' . $countBindValue;
            $bindValues[':secondary_item_' . $countBindValue] = $chapter['secondary_item'];
            ++$countBindValue;

            # tertiary_item
            if (empty($chapter['tertiary_item']))
            {
                $chapterWheres[] = "($myWhere)";
                continue;
            }

            $myWhere .= ' AND tertiary_item = :tertiary_item_' . $countBindValue;
            $bindValues[':tertiary_item_' . $countBindValue] = $chapter['tertiary_item'];
            ++$countBindValue;

            $chapterWheres[] = "($myWhere)";
        }

        return [
            'where' => '(' . implode(' OR ', $chapterWheres) . ')',
            'bind_values' => $bindValues
        ];
    }

    /**
     * @param $genres
     * @return array
     */
    private function GenerateGenresCondition($genres): array
    {
        $genreWheres = [];
        $bindValues = [];
        $countBindValue = 0;

        foreach ($genres as $genre)
        {
            $myWhere = 'question_genre = :genre_value_' . $countBindValue;
            $bindValues[':genre_value_' . $countBindValue] = $genre;
            ++$countBindValue;

            $genreWheres[] = $myWhere;
        }

        return [
            'where' => '(' . implode(' OR ', $genreWheres) . ')',
            'bind_values' => $bindValues
        ];
    }

    /**
     * @param array $titleNos
     * @param array $questionKinds
     * @param $maxChapter
     * @return array|false
     */
    public function GetIdsByTitleNosAndQuestionKindsAndMaxChapter(array $titleNos, array $questionKinds, $maxChapter=null)
    {
        $pdo = PDOHelper::GetPDO();

        $whereByMaxChapter = (!empty($maxChapter)) ? 'AND chapter <= :max_chapter' : '';

        // title_no
        $bindTitleNoKeyArray = [];

        for ($i=0; $i<count($titleNos); ++$i)
        {
            $bindTitleNoKeyArray[] = ":title_no_{$i}";
        }

        $bindTitleNoKeys = implode(',', $bindTitleNoKeyArray);

        // question_kind
        $bindQuestionKindsKeyArray = [];

        for ($i=0; $i<count($questionKinds); ++$i)
        {
            $bindQuestionKindsKeyArray[] = ":question_kind_{$i}";
        }

        $bindQuestionKindKeys = implode(',', $bindQuestionKindsKeyArray);

        $sql = <<<SQL
SELECT 
  id
FROM {$this->_tableName} 
WHERE title_no IN ({$bindTitleNoKeys}) 
  AND question_kind IN ({$bindQuestionKindKeys}) 
  {$whereByMaxChapter}
SQL;

        $sth = $pdo->prepare($sql);

        if (!empty($maxChapter))
        {
            $sth->bindValue(':max_chapter', $maxChapter, \PDO::PARAM_INT);
        }

        for ($i=0; $i<count($titleNos); ++$i)
        {
            $sth->bindValue(":title_no_{$i}", $titleNos[$i]);
        }

        for ($i=0; $i<count($questionKinds); ++$i)
        {
            $sth->bindValue(":question_kind_{$i}", $questionKinds[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $ids
     * @return array|false
     */
    public function GetByIDs(array $ids)
    {
        $pdo = PDOHelper::GetPDO();

        // title_no
        $bindIdKeyArray = [];

        for ($i=0; $i<count($ids); ++$i)
        {
            $bindIdKeyArray[] = "CAST(:id_{$i} AS DECIMAL(20))";
        }

        $bindIdKeys = implode(',', $bindIdKeyArray);

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE id IN ({$bindIdKeys}) 
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($ids); ++$i)
        {
            $sth->bindValue(":id_{$i}", $ids[$i], \PDO::PARAM_STR);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @return array|false
     */
    public function GetQuizPages($titleNo)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  page
FROM {$this->_tableName} 
WHERE title_no = :title_no 
  AND question_kind = :question_kind 
  AND question_genre IN (:question_genre_1, :question_genre_2) 
GROUP BY page 
ORDER BY page ASC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo, \PDO::PARAM_INT);
        $sth->bindValue(':question_kind', EbookQuiz::QUESTION_KIND_CHECK, \PDO::PARAM_INT);
        $sth->bindValue(':question_genre_1', EbookQuiz::QUESTION_GENRE_UNCHANGED, \PDO::PARAM_INT);
        $sth->bindValue(':question_genre_2', EbookQuiz::QUESTION_GENRE_CHANGED, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}