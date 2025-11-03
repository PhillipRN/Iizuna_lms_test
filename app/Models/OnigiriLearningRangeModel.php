<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class OnigiriLearningRangeModel extends ModelBase
{
    public const LEVEL_LIST_TOEIC = [220, 300, 350, 470, 500, 600, 730, 860, 950];
    public const LEVEL_LIST_ENGLISH_CERT = [6, 5, 4, 3, 2, 1];

    public const GENRE_TOEIC = 'toeic';
    public const GENRE_ENGLISH_CERT = 'english_cert';
    public const GENRE_JUNIOR_HIGH_SCHOOL = 'junior_high_school';
    public const GENRE_HIGH_SCHOOL = 'high_school';
    public const GENRE_COLLEGE_COMMON_TEST = 'college_common_test';
    public const GENRE_COLLEGE_STANDARD = 'college_standard';
    public const GENRE_COLLEGE_ELITE = 'college_elite';

    public const MAX_STAGE_NUM = 100;

    function __construct() {
        $this->_tableName ='onigiri_learning_range';
    }

    /**
     * @param $lmsCodeId
     * @return array
     */
    public function GetsByLmsCodeId($lmsCodeId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
ORDER BY sequential_number ASC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return bool
     */
    function Delete($lmsCodeId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE
  lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}