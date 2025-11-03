<?php

namespace IizunaLMS\Models;

use IizunaLMS\Datas\JsonQuizOption;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;

class JsonQuizOptionModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'json_quiz_option';
    }

    /**
     * @param JsonQuizOption $jsonQuizOption
     * @return bool
     */
    public function AddOrUpdate(JsonQuizOption $jsonQuizOption)
    {
        if (empty($jsonQuizOption->json_quiz_id)) return false;

        $record = $this->GetByKeyValue('json_quiz_id', $jsonQuizOption->json_quiz_id);

        if (empty($record))
        {
            return $this->Add($jsonQuizOption);
        }
        else
        {
            return $this->UpdateJsonQuizOption($jsonQuizOption);
        }
    }

    /**
     * @param JsonQuizOption $jsonQuizOption
     * @return bool
     */
    private function UpdateJsonQuizOption(JsonQuizOption $jsonQuizOption)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName}
SET 
  mode = :mode,
  range_type = :range_type,
  page_ranges = :page_ranges,
  question_number_ranges = :question_number_ranges,
  midasi_number_ranges = :midasi_number_ranges,
  section_numbers = :section_numbers,
  midasi_numbers = :midasi_numbers,
  sort = :sort,
  is_show_question_no = :is_show_question_no,
  is_show_midasi_no = :is_show_midasi_no,
  manual_is_individual = :manual_is_individual,
  manual_syubetu_numbers = :manual_syubetu_numbers,
  manual_change_display = :manual_change_display,
  manual_frequencies = :manual_frequencies,
  manual_syomon_numbers = :manual_syomon_numbers,
  manual_individual_selected_json = :manual_individual_selected_json
WHERE json_quiz_id = CAST(:json_quiz_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':mode',  $jsonQuizOption->mode, \PDO::PARAM_INT);
        $sth->bindValue(':json_quiz_id', $jsonQuizOption->json_quiz_id, \PDO::PARAM_STR);
        $sth->bindValue(':range_type',  $jsonQuizOption->range_type, \PDO::PARAM_STR);
        $sth->bindValue(':page_ranges',  $jsonQuizOption->page_ranges, \PDO::PARAM_STR);
        $sth->bindValue(':question_number_ranges',  $jsonQuizOption->question_number_ranges, \PDO::PARAM_STR);
        $sth->bindValue(':midasi_number_ranges',  $jsonQuizOption->midasi_number_ranges, \PDO::PARAM_STR);
        $sth->bindValue(':section_numbers',  $jsonQuizOption->section_numbers, \PDO::PARAM_STR);
        $sth->bindValue(':midasi_numbers',  $jsonQuizOption->midasi_numbers, \PDO::PARAM_STR);
        $sth->bindValue(':sort',  $jsonQuizOption->sort, \PDO::PARAM_INT);
        $sth->bindValue(':is_show_question_no',  $jsonQuizOption->is_show_question_no, \PDO::PARAM_INT);
        $sth->bindValue(':is_show_midasi_no',  $jsonQuizOption->is_show_midasi_no, \PDO::PARAM_INT);
        $sth->bindValue(':manual_is_individual',  $jsonQuizOption->manual_is_individual, \PDO::PARAM_STR);
        $sth->bindValue(':manual_syubetu_numbers',  $jsonQuizOption->manual_syubetu_numbers, \PDO::PARAM_STR);
        $sth->bindValue(':manual_change_display',  $jsonQuizOption->manual_change_display, \PDO::PARAM_INT);
        $sth->bindValue(':manual_frequencies',  $jsonQuizOption->manual_frequencies, \PDO::PARAM_STR);
        $sth->bindValue(':manual_syomon_numbers',  $jsonQuizOption->manual_syomon_numbers, \PDO::PARAM_STR);
        $sth->bindValue(':manual_individual_selected_json',  $jsonQuizOption->manual_individual_selected_json, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}