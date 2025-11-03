<?php

namespace IizunaLMS\LmsTickets;

use IizunaLMS\Datas\TeacherLoginData;
use IizunaLMS\Models\LmsTicketApplicationViewModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\LmsTicketModel;

class LmsTicketLoader
{
    /**
     * @param $teacherId
     * @return array
     */
    public function GetTicketList($teacherId): array
    {
        $ticketRecords = (new LmsTicketModel())->GetUndeletedTicketList($teacherId);
        $applicationRecords = (new LmsTicketApplicationViewModel())->GetUndeletedTicketListByTeacherId($teacherId);

        $result = [];
        foreach (LmsTicket::$AvailableTitleNos as $titleNo) $result[$titleNo] = [];

        // 使用数を取得する
        $useCounts = $this->GetUseCount($ticketRecords);

        // チケット一覧を生成する
        foreach ($ticketRecords as $record)
        {
            $key = $record['expire_year'] . '-' . $record['expire_month'];
            $result[$record['title_no']][$key] = $record;
            $result[$record['title_no']][$key]['use_count'] = $useCounts[$record['id']] ?? 0;
            $result[$record['title_no']][$key]['quantity'] = 0;
        }

        // 申請情報を当て込む
        foreach ($applicationRecords as $record)
        {
            $key = $record['expire_year'] . '-' . $record['expire_month'];

            // 承認済みの quantity を加算する
            if ($record['lms_ticket_application_status'] == LmsTicketApplication::STATUS_APPROVED)
                $result[$record['title_no']][$key]['quantity'] += $record['quantity'];
        }

        return $result;
    }

    /**
     * @param $ticketRecords
     * @return array
     */
    private function GetUseCount($ticketRecords): array
    {
        $lmsTicketIds = [];
        foreach ($ticketRecords as $record) $lmsTicketIds[] = $record['id'];

        if (empty($lmsTicketIds)) return [];

        $records = (new LmsTicketGroupViewModel())->GetUseCountsByLmsTicketIds($lmsTicketIds);

        $result = [];
        foreach ($records as $record) $result[$record['lms_ticket_id']] = $record['use_count_total'];

        return $result;
    }

    /**
     * @param $lmsTicketId
     * @return array|null
     */
    public function GetTicket($lmsTicketId)
    {
        $result = (new LmsTicketModel)->GetById($lmsTicketId);

        if (empty($result)) return null;

        $ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();
        $result['name'] = $ticketTypes[$result['title_no']]['name'];
        $result['quantity'] = 0;

        $applicationRecords = (new LmsTicketApplicationViewModel())->GetUndeletedTicketListByLmsTicketId($lmsTicketId);

        // 申請情報を当て込む
        foreach ($applicationRecords as $record)
        {
            // 承認済みの quantity を加算する
            if ($record['lms_ticket_application_status'] == LmsTicketApplication::STATUS_APPROVED)
                $result['quantity'] += $record['quantity'];
        }

        $useCounts = $this->GetUseCount([$result]);
        $result['use_count'] = $useCounts[$result['id']] ?? 0;

        return $result;
    }

    /**
     * @param $lmsTicketId
     * @return array
     */
    public function GetTicketGroups($lmsTicketId): array
    {
        return (new LmsTicketGroupViewModel())->GetUndeletedTicketGroupList($lmsTicketId);
    }

    /**
     * @param $teacherId
     * @return array
     */
    public function GetTeachersTicketHierarchy($teacherId): array
    {
        $result = [];

        $ticketTypes = (new LmsTicket())->GetAvailableTicketTypes();
        foreach ($ticketTypes as $ticketType)
        {
            $titleNo = $ticketType['title_no'];

            $result[ $titleNo ] = [
                'title_no' => $ticketType['title_no'],
                'name' => $ticketType['name'],
                'lms_tickets' => []
            ];
        }

        $ticketRecords = (new LmsTicketModel())->GetUndeletedTicketList($teacherId);
        foreach ($ticketRecords as $record)
        {
            $titleNo = $record['title_no'];
            $lmsTicketId = $record['id'];

            if (!isset($result[ $titleNo ])) continue;

            $result[ $titleNo ]['lms_tickets'][ $lmsTicketId ] = [
                'lms_ticket_id' => $record['id'],
                'title' => "{$record['expire_year']}年{$record['expire_month']}月まで",
                'lms_ticket_groups' => []
            ];
        }

        $groups = (new LmsTicketGroupViewModel())->GetUndeletedTeachersTicketGroupList($teacherId);
        foreach ($groups as $group)
        {
            $titleNo = $group['title_no'];
            $lmsTicketId = $group['lms_ticket_id'];
            $groupId = $group['id'];

            if (!isset($result[ $titleNo ])) continue;

            $result[ $titleNo ]['lms_tickets'][ $lmsTicketId ]['lms_ticket_groups'][$groupId] = [
                'name' => $group['name'],
                'lms_code' => $group['lms_code']
            ];
        }

        return $result;
    }

    /**
     * @param $teacherId
     * @return array
     */
    public function GetTeachersOnigiriTicket($teacherId): array
    {
        return (new LmsTicketGroupViewModel())->GetUndeletedTeachersTicketGroupListWithTitleNo($teacherId, LmsTicket::TITLE_NO_ONIGIRI);
    }

    /**
     * @param $teacherId
     * @return bool
     */
    public function HaveOnigiriTicket($teacherId): bool
    {
        $records = $this->GetTeachersOnigiriTicket($teacherId);
        return !empty($records);
    }
}