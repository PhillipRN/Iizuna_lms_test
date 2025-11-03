<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookFlashCard extends RequestParams implements IRequestParams
{
    public $titleNo;
    public $chapterArray;
    public $number;
    public $no_tag_ja;
    public $no_tag_en;
    public $isRandom;

    function __construct()
    {
        $this->titleNo = $this->GetPostParam('t');
        $this->number = $this->GetPostParam('n', 0);
        $this->no_tag_ja = $this->GetPostParam('nt_ja', 0);
        $this->no_tag_en = $this->GetPostParam('nt_en', 0);
        $this->isRandom = ($this->GetPostParam('r', 1) == 1);
        $chapterString = $this->GetPostParam('c');
        if (!empty($chapterString)) $this->chapterArray = explode('_', $chapterString);
    }

    public function IsValid(): bool
    {
        if (empty($this->titleNo) || empty($this->chapterArray)) return false;

        if (count($this->chapterArray) >= 3) return false;

        return true;
    }
}