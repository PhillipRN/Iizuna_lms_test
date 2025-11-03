<?php

namespace IizunaLMS\Errors;

class ErrorMessage
{
    private static $messageFilePath = __DIR__ . '/../error_message.json';
    private static $unknownError = 'Unknown error';

    /**
     * @param $errorCode
     * @return mixed|string
     */
    public static function GetMessage($errorCode)
    {
        $jsonString = file_get_contents(self::$messageFilePath);
        $messageData = json_decode($jsonString, true);

        return (isset($messageData[$errorCode]))
            ? $messageData[$errorCode]
            : self::$unknownError;
    }

    /**
     * @param $errorCodes
     * @return array
     */
    public static function GetMessages($errorCodes)
    {
        $result = [];

        for ($i=0; $i<count($errorCodes); ++$i)
        {
            $result[] = self::GetMessage($errorCodes[$i]);
        }

        return $result;
    }
}