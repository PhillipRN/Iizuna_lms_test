<?php

namespace IizunaLMS\Students;

use IizunaLMS\Errors\Error;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Requests\RequestParamStudentChangeProfile;

class ChangeProfile
{
    /**
     * @param $studentId
     * @param RequestParamStudentChangeProfile $params
     * @return array
     */
    public function CheckValidateParameters($studentId, RequestParamStudentChangeProfile $params)
    {
        $errors = [];

        $StudentDataChecker = new StudentDataChecker();

        if (empty($params->login_id)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_NULL;
        else if (!$StudentDataChecker->CheckLoginId($params->login_id)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_INVALID;
        else if ($StudentDataChecker->IsRegisteredOtherStudentLoginId($params->login_id, $studentId)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_ALREADY_REGISTERED;

        if (empty($params->name)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_NAME_NULL;

        if (empty($params->student_number)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_STUDENT_NUMBER_NULL;

        return $errors;
    }

    /**
     * @param $studentId
     * @param RequestParamStudentChangeProfile $params
     * @return bool
     */
    public function Update($studentId, RequestParamStudentChangeProfile $params)
    {
        $paramArray = $params->ToArray();
        $paramArray['id'] = $studentId;

        return (new StudentModel())->Update($paramArray);
    }
}