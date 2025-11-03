<?php

namespace IizunaLMS\LmsTickets\Data;

use IizunaLMS\LmsTickets\LmsTicketApplication;

class LmsTicketApplicationData
{
    public $id;
    public $lms_ticket_id;
    public $quantity;
    public $status;
    public $type;

    function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->lms_ticket_id = $data['lms_ticket_id'];
        $this->quantity = $data['quantity'];
        $this->status = $data['status'] ?? LmsTicketApplication::STATUS_APPLICATION;
        $this->type = $data['type'] ?? LmsTicketApplication::TYPE_APPLICATION_BY_TEACHER;
    }
}