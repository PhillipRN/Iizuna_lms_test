<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Errors\ErrorMessage;

class OnigiriJsonQuiz extends OnigiriCurlBase
{
    public function Create($lmsCode, $stages, $total, $type, $openDate, $expireDate)
    {
        $errorCode = $this->CheckCreateParameters($lmsCode, $stages, $total, $type, $openDate, $expireDate);

        $stage = implode('_', $stages);

        if ($errorCode !== ERROR::ERROR_NONE) return [
            'error' => [
                'code' => $errorCode,
                'message' => ErrorMessage::GetMessage($errorCode)
            ]
        ];

        $url = ONIGIRI_API . '?m=quiz';

        $postData = [
            't' => $type,
            'g' => 'lms_code',
            's' => $stage,
            'n' => $total,
            'lms_code' => $lmsCode,
        ];

        return $this->CurlExecAndGetDecodedResult($url, $postData);
    }

    /**
     * @param $wordIds
     * @param $types
     * @param $openDate
     * @param $expireDate
     * @return array[]|mixed
     */
    public function CreateManualMode($wordIds, $types, $openDate, $expireDate)
    {
        $errorCode = $this->CheckCreateManualParameters($wordIds, $types, $openDate, $expireDate);

        if ($errorCode !== ERROR::ERROR_NONE) return [
            'error' => [
                'code' => $errorCode,
                'message' => ErrorMessage::GetMessage($errorCode)
            ]
        ];

        $url = ONIGIRI_API . '?m=manual_quiz';

        $postData = [
            'word_ids' => implode('_', $wordIds),
            'types' => implode('#', $types),
        ];

        return $this->CurlExecAndGetDecodedResult($url, $postData);
    }

    private function CheckCreateParameters($lmsCode, $stages, $total, $type, $openDate, $expireDate)
    {
        if (empty($lmsCode)) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_LMS_CODE;
        if (empty($stages)) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_STAGE;
        if (empty($total)) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_TOTAL;
        if (empty($type)) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_EMPTY_TYPE;
        if ($total > 100) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_TOTAL_MAX;

        if (!empty($openDate) && !empty($expireDate))
        {
            $date1 = new \DateTime($openDate);
            $date2 = new \DateTime($expireDate);

            if ($date1 >= $date2) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_INVALID_TERMS;
        }

        return ERROR::ERROR_NONE;
    }

    private function CheckCreateManualParameters($wordIds, $types, $openDate, $expireDate)
    {
        if (empty($wordIds) || count($wordIds) < 5) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_TOTAL_MIN;
        if (count($wordIds) != count($types)) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_ERROR;
        if (count($wordIds) > 100) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_TOTAL_MAX;

        if (!empty($openDate) && !empty($expireDate))
        {
            $date1 = new \DateTime($openDate);
            $date2 = new \DateTime($expireDate);

            if ($date1 >= $date2) return Error::ERROR_ONIGIRI_QUIZ_PARAMETER_INVALID_TERMS;
        }

        return ERROR::ERROR_NONE;
    }
}