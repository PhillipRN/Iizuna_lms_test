<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\TeacherBookModel;
use IizunaLMS\Models\TeacherBookTempModel;
use IizunaLMS\Models\RegistrationModel;
use IizunaLMS\Helpers\PDOHelper;

/**
 * Class RegistrationController
 */
class RegistrationController
{
    /**
     * @param $id
     * @return mixed
     */
    public function GetById($id)
    {
        return $this->GetRegistrationModel()->GetByid($id);
    }

    /**
     * @param $hashKey
     * @return mixed
     */
    public function GetByHashKey($hashKey)
    {
        return $this->GetRegistrationModel()->GetByHashKey($hashKey);
    }

    /**
     * @return array
     */
    public function Gets($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PAGE_LIMIT : 0;
        $limit = PAGE_LIMIT;
        return $this->GetRegistrationModel()->GetsByLimitAndOffset($limit, $offset);
    }

    /**
     * @return int
     */
    public function GetMaxPageNum()
    {
        $count = $this->GetRegistrationModel()->Count();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PAGE_LIMIT)) + 1;
    }

    /**
     * @return array
     */
    public function GetUserList($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PAGE_LIMIT : 0;
        $limit = PAGE_LIMIT;
        return $this->GetTeacherBookTempModel()->GetsByLimitAndOffset($limit, $offset);
    }

    /**
     * @return int
     */
    public function GetUserListMaxPageNum()
    {
        $count = $this->GetTeacherBookTempModel()->CountTeacherId();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PAGE_LIMIT)) + 1;
    }

    /**
     * @return string
     */
    public function GenerateHashKey()
    {
        $hashKey = sha1(microtime() . rand(0, 10000));

        // 既に登録されているハッシュキーと衝突していないか念の為確認する
        $RegistrationModel = $this->GetRegistrationModel();
        $record = $RegistrationModel->GetByHashKey($hashKey);

        if ($record) {
            return $this->GenerateHashKey();
        }

        return $hashKey;
    }

    /**
     * @param $titleNo
     * @param $hashKey
     * @param $isCommit
     * @return int
     */
    public function AddRecord($titleNo, $hashKey, $isCommit=true)
    {
        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $RegistrationModel = $this->GetRegistrationModel();

        $addResult = $RegistrationModel->AddRecord(
            $titleNo,
            $hashKey
        );

        if (!$addResult)
        {
            return ERROR_ADMIN_REGISTRATION_ADD_FAILED;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->commit();
        }

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @return array
     */
    public function ValidateRegisrationParameters($params)
    {
        $errors = [];

        if (empty($params["title_no"])) {
            $errors[] = ERROR_ADMIN_REGISTRATION_REGISTER_PARAMETER_INVALID;
        }

        return $errors;
    }

    /**
     * @param $id
     * @return int
     */
    public function DisabledRecord($id)
    {
        $this->GetRegistrationModel()->UpdateRecordStatus($id, REGISTRATION_KEY_STATUS_DISABLED, 0);

        return ERROR_NONE;
    }

    /**
     * @param $id
     * @param $titleNo
     * @param $teacherId
     * @param $isCommit
     * @return int
     */
    public function RegistrationBook($id, $titleNo, $teacherId, $isCommit=true)
    {
        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        // user_book 更新
        $addTeacherBookResult = $this->GetTeacherBookModel()->AddTeacherBook(
            $teacherId,
            $titleNo
        );

        // 書籍キー更新
        $updateResult = $this->GetRegistrationModel()->UpdateRecordStatus(
            $id,
            REGISTRATION_KEY_STATUS_REGISTERED,
            $teacherId
        );

        if (!$addTeacherBookResult || !$updateResult)
        {
            PDOHelper::GetPDO()->rollBack();
            return ERROR_REGISTRATION_KEY_FAILED;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->commit();
        }

        return ERROR_NONE;
    }

    /**
     * @param $teacherId
     * @param $titleNo
     * @param $registrationKeyId
     * @return bool
     */
    public function SetRegistrationKeyId($teacherId, $titleNo, $registrationKeyId)
    {
        $result = $this->GetTeacherBookTempModel()->SetRegistrationKeyId($teacherId, $titleNo, $registrationKeyId);
        return ($result) ? ERROR_NONE : ERROR_UNKNOWN;
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?RegistrationModel $_RegistrationModel = null;
    private ?TeacherBookModel $_TeacherBookModel = null;
    private ?TeacherBookTempModel $_TeacherBookTempModel = null;

    private function GetRegistrationModel(): RegistrationModel
    {
        if ($this->_RegistrationModel != null) return $this->_RegistrationModel;

        $this->_RegistrationModel = new RegistrationModel();

        return $this->_RegistrationModel;
    }
    
    private function GetTeacherBookModel(): TeacherBookModel
    {
        if ($this->_TeacherBookModel != null) return $this->_TeacherBookModel;

        $this->_TeacherBookModel = new TeacherBookModel();

        return $this->_TeacherBookModel;
    }

    private function GetTeacherBookTempModel(): TeacherBookTempModel
    {
        if ($this->_TeacherBookTempModel != null) return $this->_TeacherBookTempModel;

        $this->_TeacherBookTempModel = new TeacherBookTempModel();

        return $this->_TeacherBookTempModel;
    }
}