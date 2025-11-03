<?php

namespace IizunaLMS\Helpers;

use Aws\DynamoDb\DynamoDbClient;

class DynamoDBHelper
{
    public static function GetClient()
    {
        $config = [
            'region'  => 'ap-northeast-1',
            'version' => 'latest'
        ];

        // ローカル開発環境用のendpoint設定（config.iniに DYNAMO_DB_ENDPOINT が設定されている場合）
        if (defined('DYNAMO_DB_ENDPOINT') && DYNAMO_DB_ENDPOINT) {
            $config['endpoint'] = DYNAMO_DB_ENDPOINT;
            $config['credentials'] = [
                'key'    => 'dummy',
                'secret' => 'dummy',
            ];
        } else {
            // 本番環境はAWS認証情報を使用
            $config['profile'] = 'default';
            $config['region'] = 'ap-northeast-3';
        }

        return new DynamoDbClient($config);
    }
}