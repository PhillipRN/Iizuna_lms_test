<?php

namespace IizunaLMS\Messages;

class Message
{
    private static $messageFilePath = __DIR__ . '/../message.json';

    /**
     * @return mixed|string
     */
    public static function GetMessageData()
    {
        $jsonString = file_get_contents(self::$messageFilePath);
        return json_decode($jsonString, true);
    }
}