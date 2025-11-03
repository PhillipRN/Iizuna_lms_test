<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\IStudentAccessTokenModel;
use IizunaLMS\Models\IStudentAutoLoginTokenModel;
use IizunaLMS\Models\IStudentLoginTokenModel;
use IizunaLMS\Models\StudentAccessTokenModel;
use IizunaLMS\Models\StudentAccessTokenModelDynamoDB;
use IizunaLMS\Models\StudentAuthorizationKeyModel;
use IizunaLMS\Models\StudentAutoLoginTokenModel;
use IizunaLMS\Models\StudentAutoLoginTokenModelDynamoDB;
use IizunaLMS\Models\StudentLoginTokenModel;
use IizunaLMS\Models\StudentLoginTokenModelDynamoDB;
use IizunaLMS\Models\StudentRefreshTokenModel;
use IizunaLMS\Students\Datas\StudentAccessTokenData;
use IizunaLMS\Students\Datas\StudentAutoLoginTokenData;
use IizunaLMS\Students\Datas\StudentLoginTokenData;
use IizunaLMS\Students\Datas\StudentRefreshTokenData;

class StudentAuthorization
{
    const KEY_AUTHORIZATION_KEY = 'authorization_key';

    const ACCESS_TOKEN_LIMIT_INTERVAL = 3600 * 24;
    const LOGIN_TOKEN_LIMIT_INTERVAL = 60;
    const AUTO_LOGIN_TOKEN_LIMIT_INTERVAL = 3600 * 24 * 30;

    /**
     * @param $authorizationKey
     * @return mixed
     */
    public function GetAuthorizationKeyEffectiveRecord($authorizationKey)
    {
        return ($this->GetStudentAuthorizationKeyModel())
            ->GetByAuthorizationKey($authorizationKey);
    }

    /**
     * @param $authorizationKey
     * @return mixed
     */
    public function GetAuthorizationKeyRecord($authorizationKey)
    {
        return ($this->GetStudentAuthorizationKeyModel())->GetByKeyValue(
            self::KEY_AUTHORIZATION_KEY,
            $authorizationKey
        );
    }

    /**
     * @param $authorizationKey
     * @return bool
     */
    public function DeleteAuthorizationKeyRecord($authorizationKey)
    {
        return ($this->GetStudentAuthorizationKeyModel())
            ->DeleteByKeyValue(
                self::KEY_AUTHORIZATION_KEY,
                $authorizationKey
            );

    }

    /**
     * @param $refreshToken
     * @return mixed
     */
    public function GetRefreshTokenEffectiveRecord($refreshToken)
    {
        $hashedRefreshToken = StringHelper::GetHashedString($refreshToken);
        return ($this->GetStudentRefreshTokenModel())
            ->GetByRefreshToken($hashedRefreshToken);
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function GetAccessTokenEffectiveRecord($accessToken)
    {
        $hashedAccessToken = StringHelper::GetHashedString($accessToken);
        return ($this->GetStudentAccessTokenModel())
            ->GetByAccessToken($hashedAccessToken);
    }

    /**
     * @param $studentId
     * @param $accessToken
     * @return bool
     */
    public function AddAccessToken($studentId, $accessToken)
    {
        // 有効期限
        $dateTime = new \DateTime();
        $dateTime->modify('+' . self::ACCESS_TOKEN_LIMIT_INTERVAL . ' second');
        $expiredDate = $dateTime->format("Y-m-d H:i:s");

        $StudentAccessToken = new StudentAccessTokenData([
            'student_id' => $studentId,
            'access_token' => $accessToken,
            'create_date' => date("Y-m-d H:i:s"),
            'expire_date' => $expiredDate
        ]);

        return $this->GetStudentAccessTokenModel()->Add($StudentAccessToken);
    }

    /**
     * @param $studentId
     * @param $refreshToken
     * @return bool
     */
    public function AddRefreshToken($studentId, $refreshToken)
    {
        $StudentRefreshToken = new StudentRefreshTokenData([
            'student_id' => $studentId,
            'refresh_token' => $refreshToken,
            'create_date' => date("Y-m-d H:i:s"),
            'expire_date' => '9999-12-31'
        ]);

        return $this->GetStudentRefreshTokenModel()->Add($StudentRefreshToken);
    }

    /**
     * @param $studentId
     * @param $loginToken
     * @return bool
     */
    public function AddLoginToken($studentId, $loginToken)
    {
        // 有効期限
        $dateTime = new \DateTime();
        $dateTime->modify('+' . self::LOGIN_TOKEN_LIMIT_INTERVAL . ' second');
        $expiredDate = $dateTime->format("Y-m-d H:i:s");

        $StudentLoginToken = new StudentLoginTokenData([
            'student_id' => $studentId,
            'login_token' => $loginToken,
            'create_date' => date("Y-m-d H:i:s"),
            'expire_date' => $expiredDate
        ]);

        return $this->GetStudentLoginTokenModel()->Add($StudentLoginToken);
    }

    /**
     * @param $studentId
     * @param $autoLoginToken
     * @return mixed
     */
    public function AddAutoLoginToken($studentId, $autoLoginToken)
    {
        // 有効期限
        $dateTime = new \DateTime();
        $dateTime->modify('+' . self::AUTO_LOGIN_TOKEN_LIMIT_INTERVAL . ' second');
        $expiredDate = $dateTime->format("Y-m-d H:i:s");

        $StudentAutoLoginToken = new StudentAutoLoginTokenData([
            'student_id' => $studentId,
            'auto_login_token' => $autoLoginToken,
            'create_date' => date("Y-m-d H:i:s"),
            'expire_date' => $expiredDate
        ]);

        return $this->GetStudentAutoLoginTokenModel()->Add($StudentAutoLoginToken);
    }

    private ?StudentAuthorizationKeyModel $_StudentAuthorizationKeyModel = null;
    private function GetStudentAuthorizationKeyModel(): StudentAuthorizationKeyModel
    {
        if ($this->_StudentAuthorizationKeyModel != null) return $this->_StudentAuthorizationKeyModel;
        $this->_StudentAuthorizationKeyModel = new StudentAuthorizationKeyModel();
        return $this->_StudentAuthorizationKeyModel;
    }

    private $_StudentAccessTokenModel;
    private function GetStudentAccessTokenModel(): IStudentAccessTokenModel
    {
        if ($this->_StudentAccessTokenModel != null) return $this->_StudentAccessTokenModel;
        $this->_StudentAccessTokenModel =
            (USE_DYNAMO_DB) ? new StudentAccessTokenModelDynamoDB()
                            : new StudentAccessTokenModel();
        return $this->_StudentAccessTokenModel;
    }

    private $_StudentRefreshTokenModel;
    private function GetStudentRefreshTokenModel(): StudentRefreshTokenModel
    {
        if ($this->_StudentRefreshTokenModel != null) return $this->_StudentRefreshTokenModel;
        $this->_StudentRefreshTokenModel = new StudentRefreshTokenModel();
        return $this->_StudentRefreshTokenModel;
    }

    private $_StudentLoginTokenModel;
    private function GetStudentLoginTokenModel(): IStudentLoginTokenModel
    {
        if ($this->_StudentLoginTokenModel != null) return $this->_StudentLoginTokenModel;
        $this->_StudentLoginTokenModel =
            (USE_DYNAMO_DB) ? new StudentLoginTokenModelDynamoDB()
                            : new StudentLoginTokenModel();
        return $this->_StudentLoginTokenModel;
    }

    private $_StudentAutoLoginTokenModel;
    private function GetStudentAutoLoginTokenModel(): IStudentAutoLoginTokenModel
    {
        if ($this->_StudentAutoLoginTokenModel != null) return $this->_StudentAutoLoginTokenModel;
        $this->_StudentAutoLoginTokenModel =
            (USE_DYNAMO_DB) ? new StudentAutoLoginTokenModelDynamoDB()
                : new StudentAutoLoginTokenModel();
        return $this->_StudentAutoLoginTokenModel;
    }
}