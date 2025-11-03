<?php

namespace IizunaLMS\Helpers;

use IizunaLMS\Errors\Error;

class PDOHelper
{
    private static $pdo;
    private static $onigiri_pdo;

    /**
     * @return \PDO
     */
    public static function GetPDO()
    {
        if (self::$pdo == null)
        {
            try
            {
                self::$pdo = new \PDO(
                    "mysql:dbname=".DB_NAME.";host=".DB_HOST.";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    ]
                );
            }
            catch (\PDOException $e)
            {
                header('Content-Type: text/plain; charset=UTF-8', true, 500);
                exit($e->getMessage());
            }
        }

        return self::$pdo;
    }

    /**
     * @return \PDO
     */
    public static function GetOnigiriPDO()
    {
        if (self::$onigiri_pdo == null)
        {
            try
            {
                self::$onigiri_pdo = new \PDO(
                    "mysql:dbname=".ONIGIRI_DB_NAME.";host=".ONIGIRI_DB_HOST.";charset=utf8mb4",
                    ONIGIRI_DB_USER,
                    ONIGIRI_DB_PASS,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    ]
                );
            }
            catch (\PDOException $e)
            {
                header('Content-Type: text/plain; charset=UTF-8', true, 500);
                exit($e->getMessage());
            }
        }

        return self::$onigiri_pdo;
    }

    /**
     * @param \PDOStatement $sth
     * @param null $executeParam
     * @return bool
     */
    public static function ExecuteWithTry(\PDOStatement $sth, $executeParam=null)
    {
        try
        {
            $sth->execute($executeParam);

            return true;
        }
        catch (\PDOException $e)
        {
            if (DEBUG_MODE)
            {
                header('Content-Type: text/plain; charset=UTF-8', true, 500);
                echo self::GetExecuteSQL($sth);
                echo $e->getMessage();
                echo "\n\n";
                error_log(debug_print_backtrace());
            }

            error_log($e->getMessage());
            error_log(self::GetExecuteSQL($sth));

            Error::ShowErrorJson(Error::ERROR_DB_UNKNOWN);
            exit();
        }
    }

    /**
     * @param \PDO $pdo
     * @return false|string
     */
    public static function GetLastInsertId(\PDO $pdo)
    {
        return $pdo->lastInsertId();
    }

    /**
     * 実行したSQLを取得する
     * @param \PDOStatement $sth
     * @return mixed|void
     */
    public static function GetExecuteSQL(\PDOStatement $sth)
    {
        if (DEBUG_MODE)
        {
            preg_match('/(?<=Sent SQL:).*?(?=Params:)/s',  self::PDO_DebugStrParams($sth), $matches);
            return $matches[0];
        }
    }

    private static function PDO_DebugStrParams(\PDOStatement $sth) {
        ob_start();
        $sth->debugDumpParams();
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}