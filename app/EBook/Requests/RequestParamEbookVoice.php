<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookVoice extends RequestParams implements IRequestParams
{
    public $titleNo;
    public $page;

    function __construct()
    {
        $this->titleNo = $this->GetPostParam('t');
        $this->page = $this->GetPostParam('p');
    }

    public function IsValid(): bool
    {
        return (!empty($this->titleNo) && !empty($this->page));
    }
}