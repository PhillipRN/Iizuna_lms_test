<?php

namespace IizunaLMS\Students;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Requests\RequestParamStudentAddLmsCode;
use IizunaLMS\Students\Datas\StudentLmsCodeData;

class AddLmsCode
{
    /**
     * @param $studentId
     * @param RequestParamStudentAddLmsCode $params
     * @return array
     */
    public function CheckValidateParameters($studentId, RequestParamStudentAddLmsCode $params)
    {
        $errors = [];

        $StudentDataChecker = new StudentDataChecker();

        if (empty($params->lms_code)) return [Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_NULL];

        // LMSコードではない場合はエラー
        if (!$StudentDataChecker->IsLmsCode($params->lms_code))
        {
            $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID;
        }
        // LMSコードとして正しくない、又はOnigiri用LMSチケットとして正しくない場合はエラー
        else if ( !$StudentDataChecker->IsValidLmsCode($params->lms_code) &&
                  !$StudentDataChecker->IsValidLmsTicketForOnigiri($params->lms_code)
            ) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID;

        if ($StudentDataChecker->AlreadyRegisteredLmsCode($studentId, $params->lms_code)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_ALREADY_REGISTERED;

        return $errors;
    }

    /**
     * @param $studentId
     * @param RequestParamStudentAddLmsCode $params
     * @return bool
     */
    public function AddLmsCode($studentId, RequestParamStudentAddLmsCode $params)
    {
        $record = (new LmsCodeModel())->GetByKeyValue('lms_code', $params->lms_code);

        if (empty($record)) return false;

        $StudentLmsCodeData = new StudentLmsCodeData([
            'student_id' => $studentId,
            'lms_code_id' => $record['id']
        ]);

        PDOHelper::GetPDO()->beginTransaction();

        $resultAdd = (new StudentLmsCodeModel())->Add($StudentLmsCodeData);
        if (!$resultAdd)
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        $result = true;

        // LMS チケットの場合はカウントアップする
        $lmsTicketGroup = (new LmsTicketGroupViewModel())->GetByKeyValue('lms_code_id', $record['id']);
        if (!empty($lmsTicketGroup))
        {
            $result = (new LmsTicketGroupRegister())->CountUp($lmsTicketGroup['id']);
        }

        if ($result) PDOHelper::GetPDO()->commit();
        else         PDOHelper::GetPDO()->rollBack();

        return $result;
    }
}