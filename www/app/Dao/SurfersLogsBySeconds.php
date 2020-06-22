<?php

namespace App\Dao;

use DB;

class SurfersLogsBySeconds
{

    const TABLE = 'surfers_logs_by_seconds';
    const DATETIME_FIELD = 'datetime';
    const MAX_SURFERS_FIELD = 'max_surfers';
    const SURFERS_AT_THE_END_FIELD = 'surfers_at_the_end';

    public static function add(string $datetime, int $maxSurfers, int $surfersAtTheEnd)
    {
        DB::table(self::TABLE)->insert(
            [
                self::DATETIME_FIELD => $datetime,
                self::MAX_SURFERS_FIELD => $maxSurfers,
                self::SURFERS_AT_THE_END_FIELD => $surfersAtTheEnd
            ]
        );
    }

    public static function getLastEntry()
    {
        return DB::table(self::TABLE)
            ->orderBy(self::DATETIME_FIELD, 'desc')
            ->limit(1)
            ->first();
    }

    public static function getMaxAggregation(string $from, string $to)
    {
        return DB::table(self::TABLE)
            ->select(
                DB::raw('MAX(' . self::MAX_SURFERS_FIELD . ') AS ' . self::MAX_SURFERS_FIELD),
                DB::raw('MAX(' . self::DATETIME_FIELD . ') AS ' . self::DATETIME_FIELD)
            )
            ->where([[self::DATETIME_FIELD, '>=', $from], [self::DATETIME_FIELD, '<', $to]])
            ->first();
    }

    public static function getSurfersAtTheEndByDatetime(string $datetime)
    {
        return DB::table(self::TABLE)
            ->select(self::SURFERS_AT_THE_END_FIELD)
            ->where(self::DATETIME_FIELD, $datetime)
            ->first();
    }
}