<?php

namespace IizunaLMS\Students;

use IizunaLMS\Errors\Error;
use IizunaLMS\Requests\RequestParamStudentRegisterForWeb;

class StudentRegisterForWeb
{
    public function CheckValidateParameters(RequestParamStudentRegisterForWeb $params)
    {
        $errors = [];

        $StudentDataChecker = new StudentDataChecker();

        if (empty($params->login_id)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_NULL;
        else if (!$StudentDataChecker->CheckLoginId($params->login_id)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_INVALID;
        else if ($StudentDataChecker->IsRegisteredOtherStudentLoginId($params->login_id)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_ALREADY_REGISTERED;

        if (empty($params->password)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NULL;
        else if (!$StudentDataChecker->CheckPassword($params->password)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_INVALID;
        else if ($params->password != $params->password_confirm) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NOT_SAME;

        if (empty($params->name)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_NAME_NULL;

        if (empty($params->student_number)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_STUDENT_NUMBER_NULL;

        if (empty($params->lms_code)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_NULL;
        else if (!$StudentDataChecker->IsLmsCode($params->lms_code)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID;
        else if (!$StudentDataChecker->IsValidLmsCode($params->lms_code)) $errors[] = Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID;

        return $errors;
    }
}