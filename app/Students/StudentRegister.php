<?php

namespace IizunaLMS\Students;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Students\Datas\StudentData;
use IizunaLMS\Students\Datas\StudentLmsCodeData;

class StudentRegister
{
    const LMS_CODE_SEPARATOR = '_';
    const KEY_LMS_CODE = 'lms_code';

    /**
     * @param $params
     * @return array
     */
    public function Register($params): array
    {
        // LMSコードでエラーの場合はエラー
        $lmsCodes = explode(StudentRegister::LMS_CODE_SEPARATOR, $params['lms_code']);
        if (!(new LmsCodeModel())->CheckLmsCodes($lmsCodes))
        {
            return ['error_code' => Error::ERROR_STUDENT_REGISTER_FAILED_LMS_CODE];
        }

        $studentId = 0;

        if (isset($params['onigiri_user_id']))
        {
            $userId = $params['onigiri_user_id'];

            $studentData = (new StudentModel())->GetStudentByOnigiriUserId($userId);
            $studentId = $studentData['id'];
        }
        else if (isset($params['ebook_user_id']))
        {
            $userId = $params['ebook_user_id'];

            $studentData = (new StudentModel())->GetStudentByEbookUserId($userId);
            $studentId = $studentData['id'];
        }

        PDOHelper::GetPDO()->beginTransaction();

        if (empty($studentId))
        {
            $params['contact_user_id'] = (new ContactUserIdGenerator())->Generate();

            // 未登録の場合は新規登録する
            $studentRegisterResult = $this->ADD($params);

            // 登録エラー時
            if (!empty($studentRegisterResult['error_code']))
            {
                PDOHelper::GetPDO()->rollBack();

                return ['error_code' => $studentRegisterResult['error_code']];
            }

            $studentId = $studentRegisterResult['student_id'];
        }

        if (empty($studentId))
        {
            return ['error_code' => Error::ERROR_STUDENT_REGISTER_FAILED_AUTHORIZATION_KEY];
        }

        // LMSコード紐づけ
        $registerLmsCodeResult = $this->ResisterLmsCode($studentId, $lmsCodes);
        if (!$registerLmsCodeResult)
        {
            PDOHelper::GetPDO()->rollBack();

            return ['error_code' => Error::ERROR_STUDENT_REGISTER_FAILED_SCHOOL];
        }

        // 認証キー発行
        $authorizationKey = (new AuthorizationCodeGenerator)->Generate();

        // 認証キー登録
        $studentAuthorizationKeyResult = (new StudentAuthorizationRegister())->Add($studentId, $authorizationKey);

        if (empty($studentAuthorizationKeyResult))
        {
            PDOHelper::GetPDO()->rollBack();

            return ['error_code' => Error::ERROR_STUDENT_REGISTER_FAILED_AUTHORIZATION_KEY];
        }

        PDOHelper::GetPDO()->commit();

        return ['authorization_key' => $authorizationKey];
    }

    /**
     * @param $params
     * @return array
     */
    private function ADD($params)
    {
        $params['password'] = StringHelper::GetHashedString($params['password']);

        $Student = new StudentData($params);
        $result = $this->GetStudentModel()->Add($Student);

        if (!$result)
        {
            return [
                'error_code' => Error::ERROR_STUDENT_REGISTER_FAILED
            ];
        }

        $studentId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        return [
            'result' => Error::ERROR_NONE,
            'student_id' => $studentId
        ];
    }

    /**
     * @param $studentId
     * @param $lmsCodes
     * @return bool
     */
    private function ResisterLmsCode($studentId, $lmsCodes)
    {
        $records = $this->GetLmsCodeModel()->GetsByKeyInValues(self::KEY_LMS_CODE, $lmsCodes, ['id'=>'ASC']);

        // 既に登録済みのものを集める
        $registeredRecords = $this->GetStudentLmsCodeModel()->GetsByKeyValue('student_id', $studentId);
        $registeredLmsCodeIds = [];
        foreach ($registeredRecords as $registeredRecord) $registeredLmsCodeIds[] = $registeredRecord['lms_code_id'];

        $studentLmsCodeArray = [];
        foreach ($records as $record)
        {
            // 既に登録済みのものは登録しない
            if (in_array($record['id'], $registeredLmsCodeIds)) continue;

            $studentLmsCodeArray[] = new StudentLmsCodeData([
                'student_id' => $studentId,
                'lms_code_id' => $record['id']
            ]);
        }

        if (empty($studentLmsCodeArray)) return true;

        $resultStudentLmsCode = $this->GetStudentLmsCodeModel()->MultipleAdd($studentLmsCodeArray);

        if (!$resultStudentLmsCode) return false;

        return true;
    }

    private ?StudentModel $_StudentModel = null;
    private function GetStudentModel(): StudentModel
    {
        if ($this->_StudentModel != null) return $this->_StudentModel;
        $this->_StudentModel = new StudentModel();
        return $this->_StudentModel;
    }

    private ?LmsCodeModel $_LmsCodeModel = null;
    private function GetLmsCodeModel(): LmsCodeModel
    {
        if ($this->_LmsCodeModel != null) return $this->_LmsCodeModel;
        $this->_LmsCodeModel = new LmsCodeModel();
        return $this->_LmsCodeModel;
    }

    private ?StudentLmsCodeModel $_StudentLmsCodeModel = null;
    private function GetStudentLmsCodeModel(): StudentLmsCodeModel
    {
        if ($this->_StudentLmsCodeModel != null) return $this->_StudentLmsCodeModel;
        $this->_StudentLmsCodeModel = new StudentLmsCodeModel();
        return $this->_StudentLmsCodeModel;
    }
}