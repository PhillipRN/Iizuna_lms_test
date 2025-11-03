<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\TeacherModel;

/**
 * Class TeacherController
 */
class TeacherController
{
    /**
     * @return array
     */
    public function GetById($id)
    {
        return $this->GetAdminTeacherController()->GetById($id);
    }

    /**
     * @param $params
     * @param bool $isCommit
     * @return bool
     */
    public function AddUser($params, $isCommit=true)
    {
        return $this->GetAdminTeacherController()->AddTeacherNoPasswordAndRegistTeacherBookTemps($params, $isCommit);
    }

    /**
     * @param $params
     * @param bool $isCommit
     * @return bool
     */
    public function UpdateUser($params, $isCommit=true)
    {
        return $this->GetAdminTeacherController()->UpdateTeacherNoPasswordAndRegistTeacherBookTemps($params, $isCommit);
    }

    /**
     * @param $params
     * @param bool $isCheckPassword
     * @return array
     */
    public function ValidateParameters($params, $isCheckPassword=true)
    {
        $errors = [];

        $id = (isset($params["id"])) ? $params["id"] : 0;

        if (!empty($params["login_id"]) &&
            $this->IsRegisteredLoginId($params["login_id"], $id))
        {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_REGISTERED_LOGIN_ID;
        }

        if ($isCheckPassword) {
            if (empty($params["password"]))
            {
                $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PASSWORD;
            }
            else if ($params["password"] != $params["password_confirm"])
            {
                $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_PASSWORD_NOT_SAME;
            }
        }

        if (empty($params["school_pref"]))                $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PREF;
        if (empty($params["school_id"]))              $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCHOOL;
        if (empty($params["name_1"]))               $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_SEI;
        if (empty($params["name_2"]))             $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_MEI;
        if (empty($params["kana_1"]))               $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_SEI;
        if (empty($params["kana_2"]))             $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_MEI;
        if (empty($params["mail"]))                $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_MAIL;

        if (!empty($params["mail"]) && !filter_var($params["mail"], FILTER_VALIDATE_EMAIL))
        {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_MAIL_INVALID;
        }

        if (!empty($params["sch_zip"]) && !$this->IsValidZipCode($params["sch_zip"]))
        {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_ZIP_INVALID;
        }

        if (!empty($params["sch_phone"]) && !$this->IsValidPhoneNumber($params["sch_phone"]))
        {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_PHONE_INVALID;
        }

        return $errors;
    }

    function IsValidPhoneNumber($number)
    {
        return is_string($number) && preg_match('/\A\d{8,12}\z/', $number);
    }

    function IsValidZipCode($number)
    {
        return is_string($number) && preg_match('/\A\d{3}\-\d{4}\z/', $number);
    }

    /**
     * @param $schoolId
     * @return array|false
     */
    public function GetSchoolTeachers($schoolId)
    {
        return (new TeacherModel())->GetsBySchoolId($schoolId);
    }

    /**
     * @param $loginId
     * @return mixed
     */
    private function IsRegisteredLoginId($loginId, $id)
    {
        return $this->GetAdminTeacherController()->IsRegisteredLoginId($loginId, $id);
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?AdminTeacherController $_AdminTeacherController = null;

    private function GetAdminTeacherController(): AdminTeacherController
    {
        if ($this->_AdminTeacherController != null) return $this->_AdminTeacherController;

        $this->_AdminTeacherController = new AdminTeacherController();

        return $this->_AdminTeacherController;
    }
}