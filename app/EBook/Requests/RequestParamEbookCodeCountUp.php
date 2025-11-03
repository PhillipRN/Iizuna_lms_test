<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookCodeCountUp extends RequestParams implements IRequestParams
{
    const LMS_CODE_SEPARATOR = '_';

    public $lmsCodes;

    function __construct()
    {
        $lmsCode = $this->GetPostParam('lms_code');
        $this->lmsCodes = (!empty($lmsCode)) ? explode(self::LMS_CODE_SEPARATOR, $this->GetPostParam('lms_code')) : [];
    }

    public function IsValid(): bool
    {
        return (!empty($this->lmsCodes));
    }
}