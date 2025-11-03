<?php

namespace IizunaLMS\Models;

use IizunaLMS\Datas\JsonQuizSearchParams;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use PDOStatement;

class JsonQuizModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'json_quiz';
    }

    /**
     * @return array
     */
    function GetAndResultNumById($id)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  quiz.*,
  COUNT(quiz_result.id) AS result_num 
FROM {$this->_tableName} AS quiz 
LEFT JOIN json_quiz_result AS quiz_result
  ON quiz.id = quiz_result.json_quiz_id
WHERE quiz.id = CAST(:id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $ids
     * @return array|false
     */
    function GetsByIds($ids)
    {
        $pdo = $this->GetPDO();
        $tmpWheres = [];

        for ($i=0; $i<count($ids); ++$i)
        {
            $tmpWheres[] = "CAST(:id_{$i} AS DECIMAL(20))";
        }

        $whereSql = 'id IN (' . implode(', ', $tmpWheres) . ')';

        $sql = <<<SQL
SELECT 
  id,
  teacher_id,
  language_type,
  title,
  max_score,
  open_date,
  expire_date,
  create_date,
  time_limit
FROM {$this->_tableName} 
WHERE {$whereSql} 
ORDER BY create_date DESC
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
     * @param $teacherId
     * @param $page
     * @return array|false
     */
    public function GetTeacherQuizzes($teacherId, $page)
    {
        $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
        $limit = PageHelper::PAGE_LIMIT;

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  quiz.id,
  quiz.teacher_id,
  quiz.title_no,
  quiz.language_type,
  quiz.title,
  quiz.open_date,
  quiz.expire_date,
  quiz.create_date,
  COUNT(quiz_result.id) AS result_num 
FROM {$this->_tableName} AS quiz 
LEFT JOIN json_quiz_result AS quiz_result
  ON quiz.id = quiz_result.json_quiz_id
WHERE quiz.teacher_id = CAST(:teacher_id AS DECIMAL(20))
GROUP BY quiz.id
ORDER BY quiz.create_date DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $schoolId
     * @param $page
     * @param $limit
     * @param JsonQuizSearchParams $searchParams
     * @return array|false
     */
    public function GetSchoolQuizzes($schoolId, $page, $limit, JsonQuizSearchParams $searchParams)
    {
        $offset = ($page > 0) ? ($page - 1) * $limit : 0;

        $whereSQL = $this->CreateWhereBySearchParams($searchParams);

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  quiz.id,
  quiz.teacher_id,
  quiz.title_no,
  quiz.language_type,
  quiz.title,
  quiz.open_date,
  quiz.expire_date,
  quiz.create_date,
  tsv.name_1 AS teacher_name_1,
  tsv.name_2 AS teacher_name_2,
  COUNT(quiz_result.id) AS result_num 
FROM {$this->_tableName} AS quiz 
INNER JOIN teacher_school_view AS tsv
  ON quiz.teacher_id = tsv.id  
LEFT JOIN json_quiz_result AS quiz_result
  ON quiz.id = quiz_result.json_quiz_id
WHERE tsv.school_id = CAST(:school_id AS DECIMAL(20))
{$whereSQL}
GROUP BY quiz.id
ORDER BY quiz.create_date DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':school_id', $schoolId, \PDO::PARAM_STR);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $sth = $this->BindParameters($sth, $searchParams);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function CreateWhereBySearchParams(JsonQuizSearchParams $searchParams)
    {
        $params = [];

        if (!empty($searchParams->title))
        {
            $params[] = "quiz.title LIKE :title_name";
        }

        if (!empty($searchParams->title_no))
        {
            $params[] = "quiz.title_no = :title_no";
        }

        if (!empty($searchParams->teacher_id))
        {
            $params[] = "quiz.teacher_id = :teacher_id";
        }

        if (!empty($searchParams->start_date) || !empty($searchParams->end_date))
        {
            $dateParams = [];

            if (!empty($searchParams->start_date))
            {
                $dateParams[] = "quiz.create_date >= :start_date";
            }

            if (!empty($searchParams->end_date))
            {
                $dateParams[] = "quiz.create_date <= :end_date";
            }

            $dateParam = implode(' AND ', $dateParams);

            $params[] = "({$dateParam})";
        }

        if ($searchParams->parent_folder_id !== null && $searchParams->parent_folder_id !== 'all')
        {
            $params[] = "quiz.parent_folder_id = :parent_folder_id";
        }

        if (empty($params)) return '';

        return ' AND ' . implode(' AND ', $params);
    }

    /**
     * @param PDOStatement $sth
     * @param JsonQuizSearchParams $searchParams
     * @return PDOStatement
     */
    private function BindParameters(PDOStatement $sth, JsonQuizSearchParams $searchParams)
    {
        if (preg_match('/:title_name/', $sth->queryString))
        {
            $sth->bindValue(':title_name', '%' . $searchParams->title . '%');
        }

        if (preg_match('/:title_no/', $sth->queryString))
        {
            $sth->bindValue(':title_no', $searchParams->title_no);
        }

        if (preg_match('/:teacher_id/', $sth->queryString))
        {
            $sth->bindValue(':teacher_id', $searchParams->teacher_id);
        }

        if (preg_match('/:start_date/', $sth->queryString))
        {
            $sth->bindValue(':start_date', $searchParams->start_date);
        }

        if (preg_match('/:end_date/', $sth->queryString))
        {
            $sth->bindValue(':end_date', $searchParams->end_date);
        }

        if (preg_match('/:parent_folder_id/', $sth->queryString))
        {
            $sth->bindValue(':parent_folder_id', $searchParams->parent_folder_id);
        }

        return $sth;
    }

    /**
     * @param $teacherId
     * @return int
     */
    public function GetMaxPageNumber($teacherId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(id) AS number
FROM {$this->_tableName} 
WHERE teacher_id = CAST(:teacher_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return PageHelper::GetMaxPageNum($record['number']);
    }

    /**
     * @param $schoolId
     * @param $limit
     * @param JsonQuizSearchParams $searchParams
     * @return int
     */
    public function GetMaxPageNumberBySchoolId($schoolId, $limit, JsonQuizSearchParams $searchParams)
    {
        $pdo = PDOHelper::GetPDO();

        $whereSQL = $this->CreateWhereBySearchParams($searchParams);

        $sql = <<<SQL
SELECT 
  COUNT(quiz.id) AS number
FROM {$this->_tableName} AS quiz
INNER JOIN teacher_school_view AS tsv
  ON quiz.teacher_id = tsv.id  
WHERE tsv.school_id = CAST(:school_id AS DECIMAL(20))
{$whereSQL}
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':school_id', $schoolId, \PDO::PARAM_STR);

        $sth = $this->BindParameters($sth, $searchParams);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return PageHelper::GetMaxPageNum($record['number'], $limit);
    }

    /**
     * @param $ids
     * @return bool
     */
    public function SetCalculateAnswerRateByIds($ids)
    {
        $pdo = PDOHelper::GetPDO();

        $tmpWheres = [];

        for ($i=0; $i<count($ids); ++$i)
        {
            $tmpWheres[] = "CAST(:id_{$i} AS DECIMAL(20))";
        }

        $whereSql = 'id IN (' . implode(', ', $tmpWheres) . ')';

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  calc_correct_answer_rate = 1,
  update_date = :update_date
WHERE {$whereSql}
SQL;

        $sth = $pdo->prepare($sql);
        for ($i=0; $i<count($ids); ++$i)
        {
            $sth->bindValue(":id_{$i}", $ids[$i], \PDO::PARAM_STR);
        }
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $quizId
     * @param $parentFolderId
     * @return bool
     */
    public function MoveToFolder($quizId, $parentFolderId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  parent_folder_id = CAST(:parent_folder_id AS DECIMAL(20)),
  update_date = :update_date
WHERE id = CAST(:id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);

        $sth->bindValue(':id', $quizId, \PDO::PARAM_STR);
        $sth->bindValue(':parent_folder_id', $parentFolderId, \PDO::PARAM_STR);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $quizIds
     * @param $parentFolderId
     * @return bool
     */
    public function BulkMoveToFolder($quizIds, $parentFolderId)
    {
        $pdo = PDOHelper::GetPDO();

        $tmpWheres = [];

        for ($i=0; $i<count($quizIds); ++$i)
        {
            $tmpWheres[] = "CAST(:id_{$i} AS DECIMAL(20))";
        }

        $where = 'id IN (' . implode(',', $tmpWheres) . ')';

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  parent_folder_id = CAST(:parent_folder_id AS DECIMAL(20)),
  update_date = :update_date
WHERE {$where}
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($quizIds); ++$i)
        {
            $sth->bindValue(":id_{$i}", $quizIds[$i], \PDO::PARAM_STR);
        }

        $sth->bindValue(':parent_folder_id', $parentFolderId, \PDO::PARAM_STR);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $parentFolderId
     * @return bool
     */
    public function MoveToRootFolder($parentFolderId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  parent_folder_id = 0,
  update_date = :update_date
WHERE parent_folder_id = CAST(:parent_folder_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);

        $sth->bindValue(':parent_folder_id', $parentFolderId, \PDO::PARAM_STR);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }
}