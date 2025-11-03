<?php

namespace IizunaLMS\Models;

interface IStudentLoginTokenModel
{
    public function Add($StudentLoginToken);
    public function GetByLoginToken($loginToken);
}