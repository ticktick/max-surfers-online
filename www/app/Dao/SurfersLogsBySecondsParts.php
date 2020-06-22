<?php

namespace App\Dao;

use DB;

class SurfersLogsBySecondsParts
{

    const TABLE = 'surfers_logs_by_seconds_parts';
    const DATETIME_FIELD = 'datetime';
    const PART_FIELD = 'part';
    const MAX_SURFERS_FIELD = 'max_surfers';
    const SURFERS_AT_THE_END_FIELD = 'surfers_at_the_end';

    public static function add(string $datetime, int $maxSurfers, int $surfersAtTheEnd, int $part)
    {
        DB::table(self::TABLE)->insert(
            [
                self::DATETIME_FIELD => $datetime,
                self::PART_FIELD => $part,
                self::MAX_SURFERS_FIELD => $maxSurfers,
                self::SURFERS_AT_THE_END_FIELD => $surfersAtTheEnd
            ]
        );
    }

    public static function getNextSecondParts()
    {
        return DB::table(self::TABLE)
            ->where(self::DATETIME_FIELD, DB::raw('(select MIN(' . self::DATETIME_FIELD . ') from ' . self::TABLE . ')'))
            ->orderBy(self::PART_FIELD, 'asc')
            ->get();
    }

    public static function isPartExists(string $second, int $part): bool
    {
        return DB::table(self::TABLE)
            ->where([self::DATETIME_FIELD => $second, self::PART_FIELD => $part])
            ->exists();
    }

    public static function removePart(string $second, int $part): void
    {
        DB::table(self::TABLE)
            ->where([self::DATETIME_FIELD => $second, self::PART_FIELD => $part])
            ->delete();
    }
}