<?php

namespace App\Service;

class DateHelpers
{

    private static $secondsFormat = 'Y-m-d\TH:i:s';

    public static function areDateDiffInInterval(int $timestampStart,
                                                 int $timestampEnd,
                                                 int $minSeconds,
                                                 int $maxSeconds): bool
    {
        $dateStart = \DateTime::createFromFormat('U', $timestampStart);
        $dateEnd = \DateTime::createFromFormat('U', $timestampEnd);
        $diff = $dateEnd->getTimestamp() - $dateStart->getTimestamp();
        return $diff >= $minSeconds && $diff <= $maxSeconds;
    }

    public static function getDatetime(int $timestamp): string
    {
        $date = \DateTime::createFromFormat( 'U', $timestamp);
        return $date->format(self::$secondsFormat);
    }

    public static function getSecond(string $datetime): string
    {
        $date = new \DateTime($datetime);
        return $date->format(self::$secondsFormat);
    }

    public static function addSecond(string $datetime): string
    {
        $date = new \DateTime($datetime);
        $second = new \DateInterval('PT1S');
        $date->add($second);
        return $date->format(self::$secondsFormat);
    }
}