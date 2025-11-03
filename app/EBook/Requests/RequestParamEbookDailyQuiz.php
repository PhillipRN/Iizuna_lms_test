<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookDailyQuiz extends RequestParams implements IRequestParams
{
    public $titleNoArray;
    public $level;

    function __construct()
    {
        $titleNoString = $this->GetPostParam('t');
        if (!empty($titleNoString)) $this->titleNoArray = explode('_', $titleNoString);
        $this->level = $this->GetPostParam('l');
    }

    public function IsValid(): bool
    {
        return (!empty($this->titleNoArray) && !empty($this->level));
    }
}