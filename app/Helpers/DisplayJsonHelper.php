<?php
namespace IizunaLMS\Helpers;

use IizunaLMS\Errors\ErrorMessage;

class DisplayJsonHelper
{
    public static function ShowAndExit($data)
    {
        self::AddHeaderCORS();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ShowErrorAndExit($errorCode)
    {
        $data = [
            'error' => [
                'code' => $errorCode,
                'message' => ErrorMessage::GetMessage($errorCode)
            ]
        ];

        self::ShowAndExit($data);
    }

    public static function ShowErrorMessageAndExit($errorMessage)
    {
        $data = [
            'error' => [
                'message' => $errorMessage
            ]
        ];

        self::ShowAndExit($data);
    }

    private static function AddHeaderCORS()
    {
        // NOTE: HTTP_REFERER を用いてアクセス元を判別して header 出力を切り替える方法を考えたが、
        //       そもそも HTTP_REFERER を取れない場合は対応できないため、やむなしで * 指定にする
        header('Access-Control-Allow-Origin: *');

        // ベーシック認証下用対応
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: *, Authorization, Content-Type');
    }
}