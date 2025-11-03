<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Models\OnigiriQuizCollegeCommonTestModel;
use IizunaLMS\Models\OnigiriQuizCollegeEliteModel;
use IizunaLMS\Models\OnigiriQuizCollegeStandardModel;
use IizunaLMS\Models\OnigiriQuizEnglishCertModel;
use IizunaLMS\Models\OnigiriQuizHighSchoolModel;
use IizunaLMS\Models\OnigiriQuizJuniorHighSchoolModel;
use IizunaLMS\Models\OnigiriQuizToeicModel;

class OnigiriQuiz
{
    function GetWords($genre, $learningRangeLevel, $stage)
    {
        switch ($genre)
        {
            case 'toeic':
                return (new OnigiriQuizToeicModel())->GetWords($learningRangeLevel, $stage);

            case 'english_cert':
                return (new OnigiriQuizEnglishCertModel())->GetWords($learningRangeLevel, $stage);

            case 'junior_high_school':
                return (new OnigiriQuizJuniorHighSchoolModel())->GetWords($stage);

            case 'high_school':
                return (new OnigiriQuizHighSchoolModel())->GetWords($stage);

            case 'college_common_test':
                return (new OnigiriQuizCollegeCommonTestModel())->GetWords($stage);

            case 'college_standard':
                return (new OnigiriQuizCollegeStandardModel())->GetWords($stage);

            case 'college_elite':
                return (new OnigiriQuizCollegeEliteModel())->GetWords($stage);
        }

        return [];
    }
}