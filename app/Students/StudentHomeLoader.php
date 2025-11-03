<?php

namespace IizunaLMS\Students;

use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\JsonQuizResultModel;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Models\StudentLmsCodeModel;

class StudentHomeLoader
{
    private $studentLmsCodeModel;
    private $jsonQuizDeliveryModel;
    private $onigiriJsonQuizDeliveryModel;
    private $jsonQuizModel;
    private $onigiriJsonQuizModel;
    private $jsonQuizResultModel;
    private $onigiriJsonQuizResultModel;

    public function __construct()
    {
        $this->studentLmsCodeModel = new StudentLmsCodeModel();
        $this->jsonQuizDeliveryModel = new JsonQuizDeliveryModel();
        $this->onigiriJsonQuizDeliveryModel = new OnigiriJsonQuizDeliveryModel();
        $this->jsonQuizModel = new JsonQuizModel();
        $this->onigiriJsonQuizModel = new OnigiriJsonQuizModel();
        $this->jsonQuizResultModel = new JsonQuizResultModel();
        $this->onigiriJsonQuizResultModel = new OnigiriJsonQuizResultModel();
    }

    /**
     * Get student home data with optimized performance
     * 
     * @param $studentId
     * @return array
     */
    public function GetData($studentId): array
    {
        $studentLmsCords = $this->studentLmsCodeModel->GetsByKeyValue('student_id', $studentId);
        $lmsCordIds = array_column($studentLmsCords, 'lms_code_id');
        
        if (empty($lmsCordIds)) {
            return [
                'jsonQuizzes' => [],
                'achievementRate' => 0
            ];
        }

        $jsonQuizIds = $this->jsonQuizDeliveryModel->GetJsonQuizIds($lmsCordIds);
        $onigiriJsonQuizIds = $this->onigiriJsonQuizDeliveryModel->GetOnigiriJsonQuizIds($lmsCordIds);
        
        if (empty($jsonQuizIds) && empty($onigiriJsonQuizIds)) {
            return [
                'jsonQuizzes' => [],
                'achievementRate' => 0
            ];
        }

        $jsonQuizzes = [];
        if (!empty($jsonQuizIds)) {
            $normalQuizzes = $this->jsonQuizModel->GetsByIds($jsonQuizIds);
            foreach ($normalQuizzes as $quiz) {
                $jsonQuizzes[] = [
                    'json_quiz_id' => $quiz['id'],
                    'onigiri_json_quiz_id' => 0,
                    'teacher_id' => $quiz['teacher_id'],
                    'title' => $quiz['title'],
                    'language_type' => $quiz['language_type'],
                    'max_score' => $quiz['max_score'],
                    'open_date' => $quiz['open_date'],
                    'expire_date' => $quiz['expire_date'],
                    'time_limit' => $quiz['time_limit'],
                    'create_date' => $quiz['create_date'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
        
        if (!empty($onigiriJsonQuizIds)) {
            $onigiriQuizzes = $this->onigiriJsonQuizModel->GetsByIds($onigiriJsonQuizIds);
            foreach ($onigiriQuizzes as $quiz) {
                $jsonQuizzes[] = [
                    'json_quiz_id' => 0,
                    'onigiri_json_quiz_id' => $quiz['id'],
                    'teacher_id' => $quiz['teacher_id'],
                    'title' => $quiz['title'],
                    'language_type' => 99,
                    'max_score' => $quiz['total'],
                    'open_date' => $quiz['open_date'],
                    'expire_date' => $quiz['expire_date'],
                    'time_limit' => $quiz['time_limit'],
                    'create_date' => $quiz['create_date'] ?? date('Y-m-d H:i:s'),
                ];
            }
        }
        
        usort($jsonQuizzes, function($a, $b) {
            return strtotime($b['create_date']) - strtotime($a['create_date']);
        });
        
        $jsonQuizResults = $this->processQuizResults(
            !empty($jsonQuizIds) ? $this->jsonQuizResultModel->GetsUserScore($studentId, $jsonQuizIds) : [],
            'json_quiz_id'
        );
        
        $onigiriJsonQuizResults = $this->processQuizResults(
            !empty($onigiriJsonQuizIds) ? $this->onigiriJsonQuizResultModel->GetsUserScore($studentId, $onigiriJsonQuizIds) : [],
            'onigiri_json_quiz_id'
        );

        $resultCount = 0;
        $currentTime = time();
        
        foreach ($jsonQuizzes as $key => &$quiz) {
            if (!empty($quiz['onigiri_json_quiz_id'])) {
                $quiz['is_before_opening'] = 0;
                $quiz['is_expired'] = 0;
                $onigiriId = $quiz['onigiri_json_quiz_id'];
                
                if (isset($onigiriJsonQuizResults[$onigiriId])) {
                    $quiz['score'] = $onigiriJsonQuizResults[$onigiriId];
                    $quiz['is_result'] = true;
                    $resultCount++;
                } else {
                    $quiz['score'] = 0;
                    $quiz['is_result'] = false;
                }
            } else {
                $openTimestamp = strtotime($quiz['open_date']);
                $expireTimestamp = strtotime($quiz['expire_date']);
                
                $quiz['is_before_opening'] = ($currentTime < $openTimestamp) ? 1 : 0;
                $quiz['is_expired'] = ($expireTimestamp < $currentTime) ? 1 : 0;
                $jsonQuizId = $quiz['json_quiz_id'];
                
                if (isset($jsonQuizResults[$jsonQuizId])) {
                    $quiz['score'] = $jsonQuizResults[$jsonQuizId];
                    $quiz['is_result'] = true;
                    $resultCount++;
                } else {
                    $quiz['score'] = 0;
                    $quiz['is_result'] = false;
                }
            }
        }
        
        $quizCount = count($jsonQuizzes);
        $achievementRate = ($quizCount > 0 && $resultCount > 0) 
            ? floor(($resultCount / $quizCount) * 100) 
            : 0;

        return [
            'jsonQuizzes' => $jsonQuizzes,
            'achievementRate' => $achievementRate
        ];
    }
    
    /**
     * Process quiz results to get the highest score for each quiz
     * 
     * @param array $results
     * @param string $idKey
     * @return array
     */
    private function processQuizResults(array $results, string $idKey): array
    {
        $processedResults = [];
        
        foreach ($results as $result) {
            $quizId = $result[$idKey];
            $score = $result['score'];
            
            if (!isset($processedResults[$quizId]) || $processedResults[$quizId] < $score) {
                $processedResults[$quizId] = $score;
            }
        }
        
        return $processedResults;
    }
}
