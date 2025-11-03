<?php

namespace IizunaLMS\Admin\LmsTickets;

use IizunaLMS\Controllers\AdminTeacherController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\Data\LmsTicketData;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;
use IizunaLMS\Requests\RequestParamLmsTicketGroup;

class AdminLmsTicketRegister
{
    const SCHOOL_TICKET_GROUP_NAME = '無料チケット';

    /**
     * @param RequestParamLmsTicketApplication $params
     * @param $teacherId
     * @return int|mixed
     */
    public function GrantLmsTicket(RequestParamLmsTicketApplication $params, $teacherId)
    {
        $teacher = (new AdminTeacherController())->GetById($teacherId);

        // 既に登録済みのLMSチケットかどうか確認する
        $record = (new LmsTicketModel())->GetUndeletedTicketWithYearAndMonth(
            $teacherId,
            $params->title_no,
            $params->expire_year,
            $params->expire_month
        );

        $lmsTicketId = 0;

        if (empty($record))
        {
            // まずは lms_ticket を登録する
            $errorCodes = $this->CheckValidateParameters($params);
            if (!empty($errorCodes)) return $errorCodes[0];

            $result = $this->AddLmsTicket($params, $teacherId, $teacher['school_id']);
            if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

            // その後、それに紐づく lms_ticket_application を登録する
            $lmsTicketId = PDOHelper::GetPDO()->lastInsertId();
        }
        else
        {
            $lmsTicketId = $record['id'];

            // まだ無効状態のLMSチケットの場合は有効に変更する
            if ($record['status'] == LmsTicket::STATUS_DISABLE) {
                $result = (new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_ENABLE);

                if (!$result) return Error::ERROR_ADMIN_LMS_TICKET_APPLICATION_UPDATE_FAILED;
            }
        }

        $params->lms_ticket_id = $lmsTicketId;
        return $this->AddApplication($params);
    }

    /**
     * @param RequestParamLmsTicketApplication $params
     * @param $schoolId
     * @return int|mixed
     */
    public function GrantLmsTicketForSchool(RequestParamLmsTicketApplication $params, $schoolId)
    {
        // まずは lms_ticket を登録する
        $errorCodes = $this->CheckValidateParameters($params);
        if (!empty($errorCodes)) return $errorCodes[0];

        $result = $this->AddLmsTicket($params, null, $schoolId);
        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

        // その後、それに紐づく lms_ticket_application を登録する
        $lmsTicketId = PDOHelper::GetPDO()->lastInsertId();

        $params->lms_ticket_id = $lmsTicketId;
        if ($this->AddApplication($params) != Error::ERROR_NONE) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

        // さらに、それに紐づく lms_ticket_group を登録する
        $ticketGroupParams = new RequestParamLmsTicketGroup();
        $ticketGroupParams->lms_ticket_id = $lmsTicketId;
        $ticketGroupParams->name = self::SCHOOL_TICKET_GROUP_NAME;
        $ticketGroupParams->quantity = $params->quantity;

        return (new LmsTicketGroupRegister())->CheckAndCreateLmsCodeAndAdd($ticketGroupParams);
    }

    /**
     * @param RequestParamLmsTicketApplication $params
     * @return array
     */
    private function CheckValidateParameters(RequestParamLmsTicketApplication $params): array
    {
        $errors = [];

        if (empty($params->title_no)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;
        else if (!in_array($params->title_no, LmsTicket::$AvailableTitleNos)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->expire_year)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->expire_month)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (!checkdate($params->expire_month, 1, $params->expire_year)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if (!is_numeric($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if ($params->quantity < 1) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;

        return $errors;
    }

    /**
     * @param $teacherId
     * @param RequestParamLmsTicketApplication $params
     * @return bool
     */
    private function AddLmsTicket(RequestParamLmsTicketApplication $params, $teacherId=null, $schoolId=null): bool
    {
        $data = $params->ToArray();
        $data['teacher_id'] = $teacherId;
        $data['school_id'] = $schoolId;
        $data['status'] = LmsTicket::STATUS_ENABLE;

        $lmsTicket = new LmsTicketData($data);

        return (new LmsTicketModel())->Add($lmsTicket);
    }

    /**
     * @param RequestParamLmsTicketApplication $params
     * @return int
     */
    private function AddApplication(RequestParamLmsTicketApplication $params)
    {
        $errorCodes = (new AdminLmsTicketApplicationRegister())->CheckValidateParameters($params);
        if (!empty($errorCodes)) return $errorCodes[0];

        $data = $params->ToArray();
        $data['status'] = LmsTicketApplication::STATUS_APPROVED;
        $data['type'] = LmsTicketApplication::TYPE_GRANTED_ADMINISTRATOR;

        $result = (new AdminLmsTicketApplicationRegister())->AddLmsTicketApplication($data);

        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ADD_FAILED;

        return Error::ERROR_NONE;
    }
}