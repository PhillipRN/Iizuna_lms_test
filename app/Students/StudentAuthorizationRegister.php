<?php

namespace IizunaLMS\Students;

use IizunaLMS\Models\StudentAuthorizationKeyModel;
use IizunaLMS\Students\Datas\StudentAuthorizationKeyData;

class StudentAuthorizationRegister
{
    const LIMIT_INTERVAL = 3600 * 24 * 7;

    public function Add($studentId, $authorizationKey)
    {
        // 有効期限
        $dateTime = new \DateTime();
        $dateTime->modify('+' . self::LIMIT_INTERVAL . ' second');
        $expiredDate = $dateTime->format("Y-m-d H:i:s");

        $StudentAuthorizationKey = new StudentAuthorizationKeyData([
            'student_id' => $studentId,
            'authorization_key' => $authorizationKey,
            'create_date' => date("Y-m-d H:i:s"),
            'expire_date' => $expiredDate,
        ]);

        return $this->GetStudentAuthorizationKeyModel()->Add($StudentAuthorizationKey);
    }

    private ?StudentAuthorizationKeyModel $_StudentAuthorizationKeyModel = null;
    private function GetStudentAuthorizationKeyModel(): StudentAuthorizationKeyModel
    {
        if ($this->_StudentAuthorizationKeyModel != null) return $this->_StudentAuthorizationKeyModel;
        $this->_StudentAuthorizationKeyModel = new StudentAuthorizationKeyModel();
        return $this->_StudentAuthorizationKeyModel;
    }

    public function AttachStudentAuthorizationKeyModel($model)
    {
        $this->_StudentAuthorizationKeyModel = $model;
    }
}