<?php

namespace IizunaLMS\Models;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use IizunaLMS\Helpers\DynamoDBHelper;

class StudentAccessTokenModelDynamoDB implements IStudentAccessTokenModel
{
    public function Add($StudentAccessToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        // DynamoDBではttlを用いて自動削除する
        $ttl = strtotime($StudentAccessToken->expire_date);
        $item = $marshaler->marshalJson(<<<JSON
{
    "access_token": "{$StudentAccessToken->access_token}",
    "student_id": "{$StudentAccessToken->student_id}",
    "create_date": "{$StudentAccessToken->create_date}",
    "ttl": {$ttl}
}
JSON);
        $params = [
         'TableName' => DYNAMO_DB_ACCESS_TOKEN_TABLE,
         'Item' => $item
        ];

        try {
            $result = $client->putItem($params);
            return true;
        } catch(DynamoDbException $e){
            throw new DynamoDbException ($e->getMessage());
        }
    }

    /**
     * @param $accessToken
     * @return array|\Aws\DynamoDb\BinaryValue|\Aws\DynamoDb\NumberValue|\Aws\DynamoDb\SetValue|int|\stdClass|null
     */
    public function GetByAccessToken($accessToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        $item = $marshaler->marshalJson(<<<JSON
{
    "access_token": "{$accessToken}"
}
JSON);

        $params = [
            'TableName' => DYNAMO_DB_ACCESS_TOKEN_TABLE,
            'Key' => $item
        ];

        $result = $client->getItem($params);

        try {
            if ($result->get('Item') != null)
            {
                return $marshaler->unmarshalItem($result->get('Item'));
            }
            else
            {
                return null;
            }
        } catch(DynamoDbException $e){
            throw new DynamoDbException ($e->getMessage());
        }
    }
}