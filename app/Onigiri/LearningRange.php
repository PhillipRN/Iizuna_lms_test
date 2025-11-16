<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Models\OnigiriLearningRangeMaxStageModel;

class LearningRange
{
    public function GetMaxStages()
    {
        if ($this->HasLearningRangeMaxStageTable()) {
            return $this->GetOnigiriLearningRangeMaxStageModel()->GetsAll(['genre', 'learning_range_level', 'max_stage'], 'genre');
        }

        return $this->GetMaxStagesFromApi();
    }

    private function HasLearningRangeMaxStageTable(): bool
    {
        try {
            $pdo = \IizunaLMS\Helpers\PDOHelper::GetOnigiriPDO();
            $sql = "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'learning_range_max_stage'";
            $sth = $pdo->prepare($sql);
            $sth->bindValue(':schema', defined('ONIGIRI_DB_NAME') ? ONIGIRI_DB_NAME : 'onigiri');
            $sth->execute();
            $row = $sth->fetch(\PDO::FETCH_ASSOC);
            return !empty($row['cnt']);
        }
        catch (\PDOException $exception) {
            // 取得に失敗した場合はテーブルが無い扱いにする
            return false;
        }
    }

    private function GetMaxStagesFromApi(): array
    {
        $apiRecords = (new OnigiriMaxStage())->GetMaxStageList();
        $result = [];

        foreach ($apiRecords as $record) {
            if (!is_array($record) || !isset($record['genre'], $record['max_stage'])) {
                continue;
            }

            $result[] = [
                'genre' => $record['genre'],
                'learning_range_level' => $record['level'] ?? 0,
                'max_stage' => (int) $record['max_stage'],
            ];
        }

        if (empty($result)) {
            throw new \RuntimeException('Max stage data could not be retrieved from the Onigiri API.');
        }

        return $result;
    }


    private $_OnigiriLearningRangeMaxStageModel;
    private function GetOnigiriLearningRangeMaxStageModel(): OnigiriLearningRangeMaxStageModel
    {
        if ($this->_OnigiriLearningRangeMaxStageModel != null) return $this->_OnigiriLearningRangeMaxStageModel;
        $this->_OnigiriLearningRangeMaxStageModel = new OnigiriLearningRangeMaxStageModel();
        return $this->_OnigiriLearningRangeMaxStageModel;
    }
}
