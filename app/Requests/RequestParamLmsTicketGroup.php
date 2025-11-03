<?php

namespace IizunaLMS\Requests;


class RequestParamLmsTicketGroup extends RequestParams
{
    public $lms_ticket_id; // 親 LMS チケットID
    public $name;
    public $quantity;

    function __construct()
    {
        $this->lms_ticket_id = $this->GetPostParam('lms_ticket_id', 0);
        $this->name = $this->GetPostParam('name');
        $this->quantity = $this->GetPostParam('quantity');
    }
}