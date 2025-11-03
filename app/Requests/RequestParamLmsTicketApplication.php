<?php

namespace IizunaLMS\Requests;


class RequestParamLmsTicketApplication extends RequestParams
{
    public $lms_ticket_id; // 親 LMS チケットID
    public $title_no;
    public $expire_year;
    public $expire_month;
    public $quantity;

    function __construct()
    {
        $this->lms_ticket_id = $this->GetPostParam('lms_ticket_id', 0);
        $this->title_no = $this->GetPostParam('title_no');
        $this->expire_year = $this->GetPostParam('expire_year');
        $this->expire_month = $this->GetPostParam('expire_month');
        $this->quantity = $this->GetPostParam('quantity');
    }
}