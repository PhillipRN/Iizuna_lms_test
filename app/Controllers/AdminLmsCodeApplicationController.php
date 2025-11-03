<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\LmsCodeApplicationModel;

class AdminLmsCodeApplicationController
{
    public function Get($id)
    {
        return $this->GetLmsCodeApplicationModel()->Get($id);
    }

    public function GetLmsCodeApplicationList($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PAGE_LIMIT : 0;
        $limit = PAGE_LIMIT;

        return $this->GetLmsCodeApplicationModel()->GetsByLimitAndOffset($limit, $offset);
    }

    public function GetMaxPageNum()
    {
        $count = $this->GetLmsCodeApplicationModel()->Count();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PAGE_LIMIT)) + 1;
    }

    public function GetLmsCodeApplicationModel()
    {
        return new LmsCodeApplicationModel();
    }
}