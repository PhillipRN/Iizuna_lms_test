<?php

namespace IizunaLMS\Models;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use IizunaLMS\Helpers\DynamoDBHelper;

class StudentLoginTokenModelDynamoDB implements IStudentLoginTokenModel
{
    public function Add($StudentLoginToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        // DynamoDBではttlを用いて自動削除する
        $ttl = strtotime($StudentLoginToken->expire_date);
        $item = $marshaler->marshalJson(<<<JSON
{
    "login_token": "{$StudentLoginToken->login_token}",
    "student_id": "{$StudentLoginToken->student_id}",
    "create_date": "{$StudentLoginToken->create_date}",
    "ttl": {$ttl}
}
JSON);
        $params = [
         'TableName' => DYNAMO_DB_LOGIN_TOKEN_TABLE,
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
     * @param $loginToken
     * @return array|\Aws\DynamoDb\BinaryValue|\Aws\DynamoDb\NumberValue|\Aws\DynamoDb\SetValue|int|\stdClass|null
     */
    public function GetByLoginToken($loginToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        $item = $marshaler->marshalJson(<<<JSON
{
    "login_token": "{$loginToken}"
}
JSON);

        $params = [
            'TableName' => DYNAMO_DB_LOGIN_TOKEN_TABLE,
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