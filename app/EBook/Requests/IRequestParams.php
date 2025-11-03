<?php

namespace IizunaLMS\EBook\Requests;

interface IRequestParams
{
    /**
     * @return bool
     */
    public function IsValid():bool;
}