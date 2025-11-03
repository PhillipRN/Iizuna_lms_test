<?php

namespace IizunaLMS\LmsTickets\Data;

use IizunaLMS\LmsTickets\LmsTicketGroup;

class LmsTicketGroupData
{
    public $id;
    public $lms_ticket_id;
    public $lms_code_id;
    public $name;
    public $quantity;
    public $status;

    function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->lms_code_id = $data['lms_code_id'];
        $this->lms_ticket_id = $data['lms_ticket_id'];
        $this->name = $data['name'];
        $this->quantity = $data['quantity'];
        $this->status = $data['status'] ?? LmsTicketGroup::STATUS_ENABLE;
    }
}