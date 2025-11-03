<?php

namespace IizunaLMS\Models;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use IizunaLMS\Helpers\DynamoDBHelper;
use IizunaLMS\Students\Datas\StudentAutoLoginTokenData;

class StudentAutoLoginTokenModelDynamoDB implements IStudentAutoLoginTokenModel
{
    public function Add(StudentAutoLoginTokenData $StudentAutoLoginToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        // DynamoDBではttlを用いて自動削除する
        $ttl = strtotime($StudentAutoLoginToken->expire_date);
        $item = $marshaler->marshalJson(<<<JSON
{
    "auto_login_token": "{$StudentAutoLoginToken->auto_login_token}",
    "student_id": "{$StudentAutoLoginToken->student_id}",
    "create_date": "{$StudentAutoLoginToken->create_date}",
    "ttl": {$ttl}
}
JSON);
        $params = [
         'TableName' => DYNAMO_DB_AUTO_LOGIN_TOKEN_TABLE,
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
     * @param $autoLoginToken
     * @return array|\Aws\DynamoDb\BinaryValue|\Aws\DynamoDb\NumberValue|\Aws\DynamoDb\SetValue|int|\stdClass|null
     */
    public function GetByAutoLoginToken($autoLoginToken)
    {
        $client = DynamoDBHelper::GetClient();
        $marshaler = new Marshaler();

        $item = $marshaler->marshalJson(<<<JSON
{
    "auto_login_token": "{$autoLoginToken}"
}
JSON);

        $params = [
            'TableName' => DYNAMO_DB_AUTO_LOGIN_TOKEN_TABLE,
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