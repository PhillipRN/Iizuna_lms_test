<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Datas\TeacherBookApplicationLog;
use IizunaLMS\Models\TeacherBookApplicationLogModel;
use IizunaLMS\Models\TeacherBookApplicationViewModel;

class AdminTeacherBookApplicationController
{
    /**
     * @return array
     */
    public function Gets($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PAGE_LIMIT : 0;
        $limit = PAGE_LIMIT;
        return $this->GetTeacherBookApplicationViewModel()->GetsByLimitAndOffset($limit, $offset);
    }

    /**
     * @return int
     */
    public function GetMaxPageNum()
    {
        $count = $this->GetTeacherBookApplicationViewModel()->Count();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PAGE_LIMIT)) + 1;
    }

    /**
     * @return array
     */
    public function GetTeachersLog()
    {
        $result = [];

        $records = $this->GetTeacherBookApplicationLogModel()->Gets();

        foreach ($records as $record)
        {
            $teacherId = $record['teacher_id'];
            $titleNo = $record['title_no'];
            $type = $record['type'];

            if (!isset($result[$teacherId])) {
                $result[$teacherId] = [
                    TeacherBookApplicationLog::TYPE_ADMIN => [],
                    TeacherBookApplicationLog::TYPE_CREATE_TEACHER => [],
                    TeacherBookApplicationLog::TYPE_ADD => []
                ];
            }

            $result[$teacherId][$type][] = $titleNo;
        }

        return $result;
    }

    private ?TeacherBookApplicationViewModel $_TeacherBookApplicationViewModel = null;
    private function GetTeacherBookApplicationViewModel(): TeacherBookApplicationViewModel
    {
        if ($this->_TeacherBookApplicationViewModel != null) return $this->_TeacherBookApplicationViewModel;

        $this->_TeacherBookApplicationViewModel = new TeacherBookApplicationViewModel();

        return $this->_TeacherBookApplicationViewModel;
    }

    private ?TeacherBookApplicationLogModel $_TeacherBookApplicationLogModel = null;
    private function GetTeacherBookApplicationLogModel(): TeacherBookApplicationLogModel
    {
        if ($this->_TeacherBookApplicationLogModel != null) return $this->_TeacherBookApplicationLogModel;

        $this->_TeacherBookApplicationLogModel = new TeacherBookApplicationLogModel();

        return $this->_TeacherBookApplicationLogModel;
    }
}