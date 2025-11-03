<?php

namespace IizunaLMS\Models;

use IizunaLMS\Students\Datas\StudentAutoLoginTokenData;

interface IStudentAutoLoginTokenModel
{
    public function Add(StudentAutoLoginTokenData $StudentLoginToken);
    public function GetByAutoLoginToken($loginToken);
}