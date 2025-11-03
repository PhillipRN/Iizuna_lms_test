<?php

namespace IizunaLMS\Admin\LmsTickets;

use IizunaLMS\Errors\Error;
use IizunaLMS\LmsTickets\Data\LmsTicketApplicationData;
use IizunaLMS\Models\LmsTicketApplicationModel;
use IizunaLMS\Models\LmsTicketModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

class AdminLmsTicketApplicationRegister
{
    /**
     * @param RequestParamLmsTicketApplication $params
     * @return array
     */
    public function CheckValidateParameters(RequestParamLmsTicketApplication $params): array
    {
        $errors = [];

        if (empty($params->lms_ticket_id)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        $record = (new LmsTicketModel())->GetById($params->lms_ticket_id);
        if (empty($record)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER;

        if (empty($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if (!is_numeric($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;
        else if ($params->quantity < 1) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY;

        return $errors;
    }

    /**
     * @param $data
     * @return bool
     */
    public function AddLmsTicketApplication($data): bool
    {
        $lmsTicketApplication = new LmsTicketApplicationData($data);

        return (new LmsTicketApplicationModel())->Add($lmsTicketApplication);
    }
}