<?php

namespace IizunaLMS\Helpers;

use DateTimeImmutable;

class PeriodHelper
{
    const PERIOD_OPEN_DATE = '1000-01-01 00:00:00';
    const PERIOD_EXPIRE_DATE = '9999-12-31 00:00:00';

    public static function ConvertDisplayOpenDate($openDate)
    {
        if ($openDate == self::PERIOD_OPEN_DATE) return '';
        else return $openDate;
    }

    public static function ConvertDisplayExpireDate($expireDate)
    {
        if ($expireDate == self::PERIOD_EXPIRE_DATE) return '';
        else return $expireDate;
    }

    public static function CalculateCountDownSeconds($currentTime, $expireDateTime, $timeLimit)
    {
        $timeLimitSeconds = $timeLimit * 60;

        // 期限がない場合は制限時間のまま
        if (empty($expireDateTime)) return $timeLimitSeconds;

        $isExpired = (strtotime($expireDateTime) < $currentTime);

        // 制限時間を計算する
        $countDownSeconds = 0;
        $expireDate = date_timestamp_get(new DateTimeImmutable($expireDateTime));
        $remainTime = $expireDate - $currentTime;

        if ($isExpired) {
            // 期限切れの場合、制限時間は設定そのまま
            $countDownSeconds = $timeLimitSeconds;
        } else {
            $countDownSeconds = ($timeLimitSeconds != 0 && $remainTime > $timeLimitSeconds) ? $timeLimitSeconds : $remainTime;
        }

        return $countDownSeconds;
    }
}