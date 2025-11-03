<?php

namespace IizunaLMS\Teachers;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Models\TeacherSchoolModel;

class TeacherLoader
{
    private const KEY_LOGIN_ID = 'login_id';

    /**
     * @param $id
     * @return array
     */
    public function GetById($id)
    {
        return $this->GetTeacherSchoolModel()->GetById($id);
    }

    /**
     * @param $page
     * @return array|false
     */
    public function GetWithPageNum($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
        return $this->GetTeacherSchoolModel()->GetsByLimitAndOffset(PageHelper::PAGE_LIMIT, $offset);
    }

    /**
     * @return int
     */
    public function Count()
    {
        return $this->GetTeacherSchoolModel()->Count();
    }

    /**
     * @param $loginId
     * @param $id
     * @return bool
     */
    public function IsRegisteredLoginId($loginId, $id=null)
    {
        return !empty(
            (empty($id))
                ? $this->GetTeacherModel()->GetsByKeyValue(self::KEY_LOGIN_ID, $loginId)
                : $this->GetTeacherModel()->GetByLoginIdExceptId($loginId, $id)
        );
    }

    // テスト用 ------------------------------------------------------------------------
    private $_TeacherModel;

    private function GetTeacherModel(): TeacherModel
    {
        if ($this->_TeacherModel != null) return $this->_TeacherModel;
        $this->_TeacherModel = new TeacherModel();
        return $this->_TeacherModel;
    }
    
    private $_TeacherSchoolModel;

    private function GetTeacherSchoolModel(): TeacherSchoolModel
    {
        if ($this->_TeacherSchoolModel != null) return $this->_TeacherSchoolModel;
        $this->_TeacherSchoolModel = new TeacherSchoolModel();
        return $this->_TeacherSchoolModel;
    }
}