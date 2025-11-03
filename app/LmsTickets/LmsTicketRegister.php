<?php

namespace IizunaLMS\LmsTickets;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\Data\LmsTicketData;
use IizunaLMS\Models\LmsTicketModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

class LmsTicketRegister
{
    /**
     * @param $teacherId
     * @param RequestParamLmsTicketApplication $params
     * @return array
     */
    public function CheckValidateParameters($teacherId, RequestParamLmsTicketApplication $params): array
    {
        $errors = [];

        // 既に登録済みの期間の場合はエラーにする
        if (!empty($teacherId))
        {
            $record = (new LmsTicketModel())->GetUndeletedTicketWithYearAndMonth(
                $teacherId,
                $params->title_no,
                $params->expire_year,
                $params->expire_month
            );

            if (!empty($record)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ALREADY_ADD;
        }

        if (empty($params->title_no)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;
        else if (!in_array($params->title_no, LmsTicket::$AvailableTitleNos)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->expire_year)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->expire_month)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (!checkdate($params->expire_month, 1, $params->expire_year)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;
        else
        {
            $expireDate = (new \DateTime("{$params->expire_year}/{$params->expire_month}/01"))
                ->modify('+1 months');

            // 有効期限が既に切れている場合はエラー
            if (new \DateTime() >= $expireDate) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_OUTDATED;
        }



        if (empty($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if (!is_numeric($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if ($params->quantity < 1) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;

        return $errors;
    }

    /**
     * @param $teacherId
     * @param $schoolId
     * @param RequestParamLmsTicketApplication $params
     * @return int|mixed
     */
    public function AddNewLmsTicket($teacherId, $schoolId, RequestParamLmsTicketApplication $params)
    {
        // まずは lms_ticket を登録する
        $errorCodes = $this->CheckValidateParameters($teacherId, $params);
        if (!empty($errorCodes)) return $errorCodes[0];

        $result = $this->AddLmsTicket($teacherId, $schoolId, $params);
        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

        // その後、それに紐づく lms_ticket_application を登録する
        $lmsTicketId = PDOHelper::GetPDO()->lastInsertId();

        $params->lms_ticket_id = $lmsTicketId;
        return $this->AddApplication($params);
    }

    /**
     * @param RequestParamLmsTicketApplication $params
     * @return int|mixed
     */
    public function AddApplication(RequestParamLmsTicketApplication $params)
    {
        $errorCodes = (new LmsTicketApplicationRegister())->CheckValidateParameters($params);

        if (!empty($errorCodes)) return $errorCodes[0];
        $result = (new LmsTicketApplicationRegister())->AddLmsTicketApplication($params);

        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

        return Error::ERROR_NONE;
    }

    /**
     * @param $teacherId
     * @param $schoolId
     * @param RequestParamLmsTicketApplication $params
     * @return bool
     */
    private function AddLmsTicket($teacherId, $schoolId, RequestParamLmsTicketApplication $params): bool
    {
        $data = $params->ToArray();
        $data['teacher_id'] = $teacherId;
        $data['school_id'] = $schoolId;

        $lmsTicket = new LmsTicketData($data);

        return (new LmsTicketModel())->Add($lmsTicket);
    }

    /**
     * @param $lmsTicketId
     * @param $status
     * @return bool
     */
    public function UpdateStatus($lmsTicketId, $status): bool
    {
        $data = [
            'id' => $lmsTicketId,
            'status' => $status,
        ];

        return (new LmsTicketModel())->Update($data);
    }
}