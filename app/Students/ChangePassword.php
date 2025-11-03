<?php

namespace IizunaLMS\Students;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Requests\RequestParamStudentChangePassword;

class ChangePassword
{
    const RESET_PASSWORD = 'password';

    /**
     * @param RequestParamStudentChangePassword $params
     * @return array
     */
    public function CheckValidateParameters(RequestParamStudentChangePassword $params)
    {
        $errors = [];

        if (empty($params->password)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NULL;
        else if (!(new StudentDataChecker())->CheckPassword($params->password)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_INVALID;
        else if ($params->password != $params->password_confirm) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NOT_SAME;

        return $errors;
    }

    /**
     * @param $studentId
     * @param $password
     * @return bool
     */
    public function UpdatePassword($studentId, $password)
    {
        return (new StudentModel())->Update([
            'id' => $studentId,
            'password' => $password,
            'is_change_password' => 0
        ]);
    }

    /**
     * @param $studentId
     * @return bool
     */
    public function ResetPassword($studentId)
    {
        return (new StudentModel())->Update([
            'id' => $studentId,
            'password' => StringHelper::GetHashedString(self::RESET_PASSWORD),
            'is_change_password' => 1
        ]);
    }
}