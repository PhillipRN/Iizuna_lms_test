<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookInformation extends RequestParams implements IRequestParams
{
    public $titleNo;

    function __construct()
    {
        $this->titleNo = $this->GetOrPostParam('t');
    }

    public function IsValid(): bool
    {
        return (!empty($this->titleNo));
    }
}