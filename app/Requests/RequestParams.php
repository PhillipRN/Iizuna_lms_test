<?php

namespace IizunaLMS\Requests;

class RequestParams
{
    protected function GetOrPostParam($key)
    {
        if (!empty($_GET[$key]))
            return $_GET[$key];

        if (!empty($_POST[$key]))
            return $_POST[$key];

        return null;
    }

    protected function GetPostParam($key, $default=null)
    {
        if (!empty($_POST[$key]))
            return $_POST[$key];

        return $default;
    }

    public function ToArray()
    {
        $result = [];

        foreach ($this as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }
}