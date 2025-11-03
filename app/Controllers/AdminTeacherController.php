<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Datas\Teacher;
use IizunaLMS\Datas\TeacherBookApplicationLog;
use IizunaLMS\Helpers\CsvHelper;
use IizunaLMS\Models\ITeacherBookModel;
use IizunaLMS\Models\SchoolModel;
use IizunaLMS\Models\TeacherBookApplicationLogModel;
use IizunaLMS\Models\TeacherBookModel;
use IizunaLMS\Models\TeacherBookTempModel;
use IizunaLMS\Models\TeacherEbookModel;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\TeacherSchoolModel;

/**
 * Class AdminTeacherController
 */
class AdminTeacherController
{
    /**
     * @return array
     */
    public function GetTeachers()
    {
        return $this->GetTeacherSchoolModel()->Gets(['create_date' => 'ASC']);
    }

    /**
     * @return array
     */
    public function GetOnigiriTeachers()
    {
        return $this->GetTeacherSchoolModel()->GetsByKeyValue(
            'is_e_onigiri',
            1,
            ['create_date' => 'ASC']
        );
    }

    /**
     * @return array
     */
    public function GetActiveNoPasswordTeachers()
    {
        return $this->GetTeacherSchoolModel()->GetActiveNoPasswordTeachers();
    }

    /**
     * @return array
     */
    public function GetById($id)
    {
        return $this->GetTeacherSchoolModel()->GetById($id);
    }

    /**
     * @param $params
     * @param bool $isCommit
     * @return bool
     */
    public function AddTeacherAndRegistTeacherBooks($params, $isCommit=true)
    {
        $validResult = $this->IsValidTeacherRegisterParams($params);

        if ($validResult != ERROR_NONE)
        {
            return $validResult;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $result = $this->_AddTeacher($params);

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherBooks($params);
        }

        $teacher = $this->GetTeacherModel()->GetByKeyValue('login_id', $params["login_id"]);

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherEBooks($params, $teacher['id']);
        }

        if ($isCommit && $result == ERROR_NONE)
        {
            PDOHelper::GetPDO()->commit();
        }

        return $result;
    }

    /**
     * @param $params
     * @param $isCommit
     * @return bool|void
     */
    public function AddTeacherNoPasswordAndRegistTeacherBookTemps($params, $isCommit=true)
    {
        // NOTE _RegistTeacherBookApplicationLog は申請サイトの登録の前提で作られているので、それ以外でこの関数を使う場合は使い分けが必要。

        $validResult = $this->IsValidTeacherRegisterParams($params, false);

        if ($validResult != ERROR_NONE)
        {
            return $validResult;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $result = $this->_AddTeacher($params);

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherBooks($params, true);
        }

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherBookApplicationLog($params, TeacherBookApplicationLog::TYPE_CREATE_TEACHER);
        }

        $teacher = $this->GetTeacherModel()->GetByKeyValue('login_id', $params["login_id"]);

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherEBooks($params, $teacher['id']);
        }

        if ($isCommit && $result == ERROR_NONE)
        {
            PDOHelper::GetPDO()->commit();
        }

        return $result;
    }

    /**
     * @param $params
     * @return bool
     */
    private function _AddTeacher($params)
    {
        $TeacherModel = $this->GetTeacherModel();

        $Teacher = new Teacher($params);

        $addResult = $TeacherModel->Add($Teacher);

        if (!$addResult)
        {
            return ERROR_ADMIN_USER_ADD_FAILED;
        }

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @return int
     */
    private function _RegistTeacherBooks($params, $isTemp=false)
    {
        if (!empty($params["title_no"]))
        {
            $teacherData = $this->GetTeacherModel()->GetByKeyValue('login_id', $params["login_id"]);

            if (!empty($teacherData))
            {
                $this->RegistTeacherBooks($teacherData["id"], $params["title_no"], $isTemp);
            }
        }

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @param $logType
     * @return int
     */
    private function _RegistTeacherBookApplicationLog($params, $logType)
    {
        if (!empty($params["title_no"]))
        {
            $teacherData = $this->GetTeacherModel()->GetByKeyValue('login_id', $params["login_id"]);

            if (!empty($teacherData))
            {

                $teacherId = $teacherData["id"];
                $titleNos = $params["title_no"];
                $addLogRecords = [];

                foreach ($titleNos as $titleNo)
                {
                    $addLogRecords[] = new TeacherBookApplicationLog([
                        'teacher_id' => $teacherId,
                        'title_no' => $titleNo,
                        'type' => $logType
                    ]);
                }

                $resultLog = $this->GetTeacherBookApplicationLogModel()->MultipleAdd($addLogRecords);

                if ($resultLog) return ERROR_NONE;
                else            return ERROR_ADMIN_USER_ADD_FAILED;
            }
        }

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @param $teacherId
     * @return int
     */
    private function _RegistTeacherEBooks($params, $teacherId)
    {
        $teacherEbook = $params["teacher_ebook"] ?? [];
        $this->RegistTeacherEBooks($teacherId, $teacherEbook);

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @param bool $isCommit
     * @return bool
     */
    public function UpdateTeacherAndRegistTeacherBooks($params, $isCommit=true)
    {
        $validResult = $this->IsValidTeacherRegisterParams($params);

        if ($validResult != ERROR_NONE) {
            return $validResult;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $result = $this->_UpdateTeacher($params);

        if ($result == ERROR_NONE)
        {
            $titleNos = (!empty($params["title_no"])) ? $params["title_no"] : array();
            $this->RegistTeacherBooks($params["id"], $titleNos);
        }

        if ($result == ERROR_NONE) {
            $result = $this->_RegistTeacherEBooks($params, $params["id"]);
        }

        if ($isCommit && $result == ERROR_NONE)
        {
            PDOHelper::GetPDO()->commit();
        }

        return $result;
    }

    /**
     * @param $params
     * @param bool $isCommit
     * @return bool
     */
    public function UpdateTeacherNoPasswordAndRegistTeacherBookTemps($params, $isCommit=true)
    {
        $validResult = $this->IsValidTeacherRegisterParams($params, false);

        if ($validResult != ERROR_NONE) {
            return $validResult;
        }

        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $result = $this->_UpdateTeacher($params);

        if ($result == ERROR_NONE)
        {
            $titleNos = (!empty($params["title_no"])) ? $params["title_no"] : array();
            $this->RegistTeacherBooks($params["id"], $titleNos, true);
        }

        if ($isCommit && $result == ERROR_NONE)
        {
            PDOHelper::GetPDO()->commit();
        }

        return $result;
    }

    /**
     * @param $params
     * @return int
     */
    private function _UpdateTeacher($params)
    {
        $TeacherModel = $this->GetTeacherModel();

        $updateData = [
            'id' => $params['id'],
            'login_id' => $params['login_id'],
            'school_id' => $params['school_id'],
            'name_1' => $params['name_1'],
            'name_2' => $params['name_2'],
            'kana_1' => $params['kana_1'],
            'kana_2' => $params['kana_2'],
            'mail' => $params['mail'],
            'phone' => $params['phone'],
            'is_e_onigiri' => $params['is_e_onigiri'] ?? 0,
            'update_date' => null
        ];

        if (!empty($params['password']))
        {
            $updateData['password'] = StringHelper::GetHashedString($params['password']);
        }

        $updateResult = $TeacherModel->Update($updateData);

        if (!$updateResult)
        {
            return ERROR_ADMIN_USER_UPDATE_FAILED;
        }

        return ERROR_NONE;
    }

    /**
     * @param $params
     * @param bool $isCheckPassword
     * @return array
     */
    public function ValidateLoginParameters($params, $isCheckPassword=true)
    {
        $errors = [];

        if (empty($params["login_id"])) {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_LOGIN_ID;
        }

        $id = (isset($params["id"])) ? $params["id"] : 0;

        if (!empty($params["login_id"]) &&
            $this->IsRegisteredLoginId($params["login_id"], $id))
        {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_REGISTERED_LOGIN_ID;
        }

        if ($isCheckPassword && empty($params["password"])) {
            $errors[] = ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PASSWORD;
        }

        return $errors;
    }

    /**
     * @param $teachers
     * @param bool $isCommit
     * @return array
     */
    public function RegistTeachers($teachers, $isCommit=true)
    {
        if ($isCommit)
        {
            PDOHelper::GetPDO()->beginTransaction();
        }

        $result = array(
            "errors" => [],
            "statuses" => []
        );

        foreach ($teachers as $key => $teacher)
        {
            if (isset($teacher["ID"]))
            {
                unset($teacher["ID"]);
            }

            if (!empty($teacher["login_id"]))
            {
                $TeacherModel = $this->GetTeacherModel();
                $registeredTeacher = $TeacherModel->GetByKeyValue('login_id', $teacher["login_id"]);

                if (!empty($registeredTeacher))
                {
                    $teacher["id"] = $registeredTeacher["id"];
                }
            }
            else
            {
                // login_idがない行はスキップする
                $result["statuses"][$key] = REGISTER_STATUS_SKIP_WITHOUT_LOGIN_ID;
                continue;
            }

            // 学校ID取得
            if (empty($teacher['school_id']) && !empty($teacher['school_name']))
            {
                $schoolName = $teacher['school_name'];
                $schoolPref = $teacher['school_pref'] ?? "";

                $schoolRecord = $this->GetSchoolModel()->GetByKeyValues(
                    ['name', 'pref'],
                    [$schoolName, $schoolPref]
                );

                if (!empty($schoolRecord))
                {
                    $teacher['school_id'] = $schoolRecord['id'];
                }
            }

            // 学校IDが取得できていない場合はスキップする
            if (empty($teacher['school_id']))
            {
                $result["statuses"][$key] = REGISTER_STATUS_SKIP_WITHOUT_SCHOOL_ID;
                continue;
            }

            // 更新
            if (isset($teacher["id"]))
            {
                $errors = $this->ValidateLoginParameters($teacher, false);

                if (!empty($errors))
                {
                    $result["errors"][$key] = $errors;
                    continue;
                }

                // RegistTeachers全体でCommitの制御をするので個々にCommitしない
                $myResult = $this->UpdateTeacherAndRegistTeacherBooks($teacher, false);

                if ($myResult != ERROR_NONE)
                {
                    $result["errors"][$key] = array($myResult);
                    continue;
                }

                if (!empty($teacher["check_textbook"]))
                {
                    // 所持書籍レコード登録
                    $titleNos = explode(",", $teacher["check_textbook"]);
                    $this->RegistTeacherBooks($teacher["id"], $titleNos);
                }

                $result["statuses"][$key] = REGISTER_STATUS_UPDATED;
            }

            // 新規登録
            else
            {
                $errors = $this->ValidateLoginParameters($teacher);

                if (!empty($errors))
                {
                    $result["errors"][$key] = $errors;
                    continue;
                }

                // RegistTeachers全体でCommitの制御をするので個々にCommitしない
                $myResult = $this->AddTeacherAndRegistTeacherBooks($teacher, false);

                if ($myResult != ERROR_NONE)
                {
                    $result["errors"][$key] = array($myResult);
                    continue;
                }

                if (!empty($teacher["check_textbook"]))
                {
                    $TeacherModel = $this->GetTeacherModel();
                    $registeredTeacher = $TeacherModel->GetByKeyValue('login_id', $teacher["login_id"]);

                    if (empty($registeredTeacher)) continue;

                    // 所持書籍レコード登録
                    $titleNos = explode(",", $teacher["check_textbook"]);
                    $this->RegistTeacherBooks($registeredTeacher["id"], $titleNos);
                }

                $result["statuses"][$key] = REGISTER_STATUS_REGISTERED;
            }
        }

        if ($isCommit && empty($result["errors"]))
        {
            PDOHelper::GetPDO()->commit();
        }

        return $result;
    }

    /**
     * @param $fileTmpName
     * @param $fileName
     * @return array|void
     */
    public function UploadCsvFile($fileTmpName, $fileName)
    {
        $uploadCsvResult = CsvHelper::UploadCsvFile($fileTmpName, $fileName);

        if (isset($uploadCsvResult['error']))
        {
            return [
                "csvErrors" => [$uploadCsvResult['error']]
            ];
        }

        $filePath = $uploadCsvResult['filePath'];

        $fp = new \SplFileObject($filePath);
        $fp->setFlags(\SplFileObject::READ_CSV);

        $keys = [];
        $teachers = [];

        foreach ($fp as $line) {
            // keyを取得
            if ($fp->key() == 0)
            {
                for ($i=0; $i<count($line); ++$i)
                {
                    $keys[] = StringHelper::ConvertEncodingToUTF8($line[$i]);
                }
            }

            // 最終行判定したらループ終了
            else if (CsvHelper::IsLastLineForCsvFile($line))
            {
                break;
            }

            // 各データ取得
            else
            {
                $tmpData = array();

                for ($i=0; $i<count($line); ++$i)
                {
                    $tmpData[$keys[$i]] = StringHelper::ConvertEncodingToUTF8($line[$i]);
                }

                $teachers[] = $tmpData;
            }
        }

        unlink($filePath);

        $AdminTeacherController = new AdminTeacherController();
        $registerResult = $AdminTeacherController->RegistTeachers($teachers);

        return [
            "csvErrors" => [],
            "registerErrors" => (isset($registerResult["errors"])) ? $registerResult["errors"] : [],
            "statuses" => (isset($registerResult["statuses"])) ? $registerResult["statuses"] : []
        ];
    }

    /**
     * @param $teacherId
     * @param $titleNos
     */
    private function RegistTeacherBooks($teacherId, $titleNos, $isTemp=false)
    {
        $TeacherBookModel = ($isTemp == false)
            ? $this->GetTeacherBookModel()
            : $this->GetTeacherBookTempModel();

        $TeacherBooks = $TeacherBookModel->GetsByTeacherId($teacherId);

        $addTitleNos = [];
        foreach ($titleNos as $titleNo) $addTitleNos[] = $titleNo;

        $deelteTitleNos = [];

        foreach ($TeacherBooks as $TeacherBook)
        {
            $tmpTitleNo = $TeacherBook["title_no"];

            // この title_no は既に持っているため追加対象から除外
            if (in_array($tmpTitleNo, $addTitleNos, true))
            {
                foreach ($addTitleNos as $key => $val)
                {
                    if ($tmpTitleNo == $val)
                    {
                        unset($addTitleNos[$key]);
                        break;
                    }
                }
            }
            // 登録対象にない title_no は削除対象にする
            else
            {
                $deelteTitleNos[] = $tmpTitleNo;
            }
        }

        if (!empty($addTitleNos))
        {
            // 所持書籍レコード追加
            $TeacherBookModel->AddTeacherBooks($teacherId, $addTitleNos);
        }

        if (!empty($deelteTitleNos))
        {
            // 所持書籍レコード削除
            $TeacherBookModel->DeleteTeacherBooks($teacherId, $deelteTitleNos);
        }

        return true;
    }

    /**
     * @param $loginId
     * @return bool
     */
    public function IsRegisteredLoginId($loginId, $id)
    {
        $teacher = $this->GetTeacherModel()->GetByKeyValue('login_id', $loginId);

        // 登録されていない
        if (empty($teacher)) return false;

        // 自身が所持しているログインID
        if ($teacher["id"] == $id) return false;

        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    public function DeleteTeacher($id)
    {
        // TODO 書籍も消す
        return ($this->GetTeacherModel()->DeleteByKeyValue('id', $id));
    }

    /**
     * @param $params
     * @return bool
     */
    private function IsValidTeacherRegisterParams($params, $isCheckPassword=true)
    {
        if (!isset($params["login_id"]))  return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_LOGIN_ID;
        if (!isset($params["school_id"]) && !isset($params["school_name"]))    return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCHOOL;
        if (!isset($params["name_1"]))     return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_SEI;
//        if (!isset($params["name1_2"]))   return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_MEI;
        if (!isset($params["kana_1"]))     return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_SEI;
//        if (!isset($params["name2_2"]))   return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_MEI;
        if (!isset($params["mail"]))      return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_MAIL;
//        if (!isset($params["gaccount"]))  return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_GACCOUNT;

        if (!isset($params["password"]) && $isCheckPassword) {
            return ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PASSWORD;
        }

        return ERROR_NONE;
    }

    /**
     * @param $teacherId
     * @param $titleNos
     * @return true
     */
    private function RegistTeacherEBooks($teacherId, $titleNos)
    {
        $TeacherEBookModel = new TeacherEbookModel();
        $TeacherEBooks = $TeacherEBookModel->GetsByTeacherId($teacherId);

        $addTitleNos = [];
        foreach ($titleNos as $titleNo) $addTitleNos[] = $titleNo;

        $deleteTitleNos = [];

        foreach ($TeacherEBooks as $TeacherEBook)
        {
            $tmpTitleNo = $TeacherEBook["title_no"];

            // この title_no は既に持っているため追加対象から除外
            if (in_array($tmpTitleNo, $addTitleNos, true))
            {
                foreach ($addTitleNos as $key => $val)
                {
                    if ($tmpTitleNo == $val)
                    {
                        unset($addTitleNos[$key]);
                        break;
                    }
                }
            }
            // 登録対象にない title_no は削除対象にする
            else
            {
                $deleteTitleNos[] = $tmpTitleNo;
            }
        }

        if (!empty($addTitleNos))
        {
            // 所持書籍レコード追加
            $TeacherEBookModel->AddTeacherBooks($teacherId, $addTitleNos);
        }

        if (!empty($deleteTitleNos))
        {
            // 所持書籍レコード削除
            $TeacherEBookModel->DeleteTeacherBooks($teacherId, $deleteTitleNos);
        }

        return true;
    }


    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?TeacherModel $_TeacherModel = null;
    private function GetTeacherModel(): TeacherModel
    {
        if ($this->_TeacherModel != null) return $this->_TeacherModel;

        $this->_TeacherModel = new TeacherModel();

        return $this->_TeacherModel;
    }

    private ?ITeacherBookModel $_TeacherBookModel = null;
    private function GetTeacherBookModel(): ITeacherBookModel
    {
        if ($this->_TeacherBookModel != null) return $this->_TeacherBookModel;

        $this->_TeacherBookModel = new TeacherBookModel();

        return $this->_TeacherBookModel;
    }

    private ?ITeacherBookModel $_TeacherBookTempModel = null;
    private function GetTeacherBookTempModel(): ITeacherBookModel
    {
        if ($this->_TeacherBookTempModel != null) return $this->_TeacherBookTempModel;

        $this->_TeacherBookTempModel = new TeacherBookTempModel();

        return $this->_TeacherBookTempModel;
    }

    private ?TeacherSchoolModel $_TeacherSchoolModel = null;
    private function GetTeacherSchoolModel(): TeacherSchoolModel
    {
        if ($this->_TeacherSchoolModel != null) return $this->_TeacherSchoolModel;

        $this->_TeacherSchoolModel = new TeacherSchoolModel();

        return $this->_TeacherSchoolModel;
    }

    private ?SchoolModel $_SchoolModel = null;
    private function GetSchoolModel(): SchoolModel
    {
        if ($this->_SchoolModel != null) return $this->_SchoolModel;

        $this->_SchoolModel = new SchoolModel();

        return $this->_SchoolModel;
    }


    private ?TeacherBookApplicationLogModel $_TeacherBookApplicationLogModel = null;
    private function GetTeacherBookApplicationLogModel() : TeacherBookApplicationLogModel
    {
        if ($this->_TeacherBookApplicationLogModel != null) return $this->_TeacherBookApplicationLogModel;

        $this->_TeacherBookApplicationLogModel = new TeacherBookApplicationLogModel();

        return $this->_TeacherBookApplicationLogModel;
    }

    /**
     * @param TeacherModel $TeacherModel
     */
    public function AttachTeacherModel(TeacherModel $TeacherModel)
    {
        $this->_TeacherModel = $TeacherModel;
    }

    /**
     * @param TeacherBookModel $TeacherBookModel
     */
    public function AttachTeacherBookModel(TeacherBookModel $TeacherBookModel)
    {
        $this->_TeacherBookModel = $TeacherBookModel;
    }

    /**
     * @param TeacherBookApplicationLogModel $TeacherBookApplicationLogModel
     */
    public function AttachTeacherBookApplicationLogModel(TeacherBookApplicationLogModel $TeacherBookApplicationLogModel)
    {
        $this->_TeacherBookApplicationLogModel = $TeacherBookApplicationLogModel;
    }
}