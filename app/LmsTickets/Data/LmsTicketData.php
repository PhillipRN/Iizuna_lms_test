<?php

namespace IizunaLMS\LmsTickets\Data;

use IizunaLMS\LmsTickets\LmsTicket;

class LmsTicketData
{
    public $id;
    public $school_id;
    public $teacher_id;
    public $title_no;
    public $expire_year;
    public $expire_month;
    public $status;

    function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->school_id = $data['school_id'] ?? 0;
        $this->teacher_id = $data['teacher_id'] ?? 0;
        $this->title_no = $data['title_no'];
        $this->expire_year = $data['expire_year'];
        $this->expire_month = $data['expire_month'];
        $this->status = $data['status'] ?? LmsTicket::STATUS_DISABLE;
    }
}