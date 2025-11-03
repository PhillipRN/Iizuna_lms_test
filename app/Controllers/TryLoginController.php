<?php
namespace IizunaLMS\Controllers;

use IizunaLMS\Models\TryLoginModel;

/**
 * Class TryLoginController
 */
class TryLoginController
{
    const LOGIN_FAILED_LIMIT = 5;
    const LOGIN_FAILED_LIMIT_INTERVAL = 300; // 5分

    /**
     * @param $ip
     * @return bool
     */
    public function CheckEnableLoginIp($ip, $isAdmin=false)
    {
        $TryLoginModel = new TryLoginModel();
        $tryLoginData = $TryLoginModel->GetByIp($ip, $isAdmin);

        if (!empty($tryLoginData))
        {
            // 同一IPから規定回数を越えたログインはブロックする
            if ($tryLoginData["count"] >= self::LOGIN_FAILED_LIMIT) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $ip
     * @param false $isAdmin
     * @return bool
     */
    public function CountUpByIp($ip, $isAdmin=false)
    {
        $TryLoginModel = new TryLoginModel();
        $record = $TryLoginModel->GetByIp($ip, $isAdmin);

        if (empty($record))
        {
            // 期限の切れている不要レコード削除
            $this->DeleteByIp($ip, $isAdmin);

            // デフォルトは有効期限ほぼ無制限
            $expiredDate = "9999-12-31 23:59:59";

            // 管理画面ではない場合のみ緩和
            if (!$isAdmin)
            {
                $dateTime = new \DateTime();
                $dateTime->modify('+' . self::LOGIN_FAILED_LIMIT_INTERVAL . ' second');
                $expiredDate = $dateTime->format("Y-m-d H:i:s");
            }

            return $TryLoginModel->Add(null, $ip, $isAdmin, $expiredDate);
        }
        else
        {
            return $TryLoginModel->CountUpById($record["id"]);
        }
    }

    /**
     * @param $ip
     * @param false $isAdmin
     * @return bool
     */
    public function DeleteByIp($ip, $isAdmin=false)
    {
        $TryLoginModel = new TryLoginModel();
        return $TryLoginModel->DeleteByIp($ip, $isAdmin);
    }
}