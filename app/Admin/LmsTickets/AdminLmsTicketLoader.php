<?php

namespace IizunaLMS\Admin\LmsTickets;

use IizunaLMS\Models\LmsTicketApplicationViewModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;

class AdminLmsTicketLoader
{
    public function GetTicketListByPage($page)
    {
        $offset = ($page > 0) ? ($page - 1) * PAGE_LIMIT : 0;
        $limit = PAGE_LIMIT;
        return (new LmsTicketApplicationViewModel())->GetUndeletedTicketListByLimitAndOffset($limit, $offset);
    }

    public function GetTicketList()
    {
        return (new LmsTicketApplicationViewModel())->GetUndeletedTicketList();
    }

    /**
     * @param $schoolId
     * @return array|false
     */
    public function GetSchoolTeacherTicketList($schoolId)
    {
        return (new LmsTicketApplicationViewModel())->GetUndeletedSchoolTeacherTicketList($schoolId);
    }

    public function GetSchoolsTicketGroupList($schoolId)
    {
        return (new LmsTicketGroupViewModel())->GetUndeletedSchoolsTicketGroupList($schoolId);
    }

    /**
     * @return int
     */
    public function GetMaxPageNum()
    {
        $count = (new LmsTicketApplicationViewModel())->CountUndeletedApplicationCount();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PAGE_LIMIT)) + 1;
    }
}