<?php

namespace IizunaLMS\EBook\Requests;

class RequestParams
{
    protected function GetOrPostParam($key)
    {
        if (isset($_GET[$key]))
            return $_GET[$key];

        if (isset($_POST[$key]))
            return $_POST[$key];

        return null;
    }

    protected function GetPostParam($key, $default=null)
    {
        if (isset($_POST[$key]))
            return $_POST[$key];

        return $default;
    }
}