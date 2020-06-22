<?php

namespace App\Dao;

use DB;

class SurfersLogs
{

    const TABLE = 'surfers_logs';
    const DATETIME_FIELD = 'datetime';
    const STATUS_FIELD = 'status';

    public static function add(string $datetime = null, int $status = null)
    {
        if (!$datetime) {
            $datetime = DB::raw('NOW()');
        }
        DB::table(self::TABLE)->insert(
            [
                self::DATETIME_FIELD => $datetime,
                self::STATUS_FIELD => $status,
            ]
        );
    }

    public static function getLogsSize(string $from, string $to)
    {
        $logs = DB::table(self::TABLE)
            ->select(DB::raw('COUNT(' . self::DATETIME_FIELD . ')'))
            ->where([[self::DATETIME_FIELD, '>=', $from], [self::DATETIME_FIELD, '<', $to]]);
        return $logs->first();
    }

    public static function getLogs(string $from, string $to, int $limit = null, int $offset = null)
    {
        $logs = DB::table(self::TABLE)
            ->where([[self::DATETIME_FIELD, '>=', $from], [self::DATETIME_FIELD, '<', $to]])
            ->orderBy(self::DATETIME_FIELD);
        if (!is_null($limit) && !is_null($offset)) {
            $logs->skip($offset)->take($limit);
        }
        return $logs->get();
    }

    public static function getFirstEntry()
    {
        return DB::table(self::TABLE)
            ->orderBy(self::DATETIME_FIELD, 'asc')
            ->limit(1)
            ->first();
    }

    public static function newerLogsExist(string $from)
    {
        return DB::table(self::TABLE)
            ->where(self::DATETIME_FIELD, '>', $from)
            ->limit(1)
            ->exists();
    }
}