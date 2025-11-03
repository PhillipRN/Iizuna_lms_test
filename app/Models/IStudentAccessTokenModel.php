<?php

namespace IizunaLMS\Models;

interface IStudentAccessTokenModel
{
    public function Add($StudentAccessToken);
    public function GetByAccessToken($accessToken);
}