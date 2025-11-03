<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\OnigiriLearningRangeModel;

class OnigiriLearningRangeRegister
{
    private $lmsCodeId;
    private $learningRange;

    function __construct($lmsCodeId, $learningRange)
    {
        $this->lmsCodeId = $lmsCodeId;
        $this->learningRange = $learningRange;
    }

    public function Update()
    {
        PDOHelper::GetPDO()->beginTransaction();

        $OnigiriLearningRangeModel = new OnigiriLearningRangeModel();

        // まず古いデータを削除する
        $OnigiriLearningRangeModel->Delete($this->lmsCodeId);

        $registerArray = [];
        $error = false;

        // 学習範囲を登録する
        for ($i=0; $i<count($this->learningRange); ++$i)
        {
            $tmpValues = explode('-', $this->learningRange[$i]);

            if (count($tmpValues) != 3)
            {
                $error = true;
                break;
            }

            $sequentialNumber = $i + 1;
            $genre = $tmpValues[0];
            $level = $tmpValues[1];
            $stage = $tmpValues[2];

            switch ($genre)
            {
                case OnigiriLearningRangeModel::GENRE_TOEIC:
                case OnigiriLearningRangeModel::GENRE_ENGLISH_CERT:
                case OnigiriLearningRangeModel::GENRE_JUNIOR_HIGH_SCHOOL:
                case OnigiriLearningRangeModel::GENRE_HIGH_SCHOOL:
                case OnigiriLearningRangeModel::GENRE_COLLEGE_COMMON_TEST:
                case OnigiriLearningRangeModel::GENRE_COLLEGE_STANDARD:
                case OnigiriLearningRangeModel::GENRE_COLLEGE_ELITE:
                    break;

                default:
                    $error = true;
                    break;
            }

            if ($error) break;

            $registerArray[] = new OnigiriLearningRange($this->lmsCodeId, $sequentialNumber, $genre, $level, $stage);
        }

        if (!empty($registerArray))
        {
            $OnigiriLearningRangeModel->MultipleAdd($registerArray);
        }

//        PDOHelper::GetPDO()->rollBack();
        PDOHelper::GetPDO()->commit();
    }
}