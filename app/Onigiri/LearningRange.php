<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Models\OnigiriLearningRangeMaxStageModel;

class LearningRange
{
    public function GetMaxStages()
    {
        return $this->GetOnigiriLearningRangeMaxStageModel()->GetsAll(['genre', 'learning_range_level', 'max_stage'], 'genre');
    }

    private $_OnigiriLearningRangeMaxStageModel;
    private function GetOnigiriLearningRangeMaxStageModel(): OnigiriLearningRangeMaxStageModel
    {
        if ($this->_OnigiriLearningRangeMaxStageModel != null) return $this->_OnigiriLearningRangeMaxStageModel;
        $this->_OnigiriLearningRangeMaxStageModel = new OnigiriLearningRangeMaxStageModel();
        return $this->_OnigiriLearningRangeMaxStageModel;
    }
}