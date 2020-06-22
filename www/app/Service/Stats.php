<?php

namespace App\Service;

use App\Dao\SurfersLogs;
use App\Dao\SurfersLogsBySeconds;
use App\Dao\SurfersLogsBySecondsParts;
use Illuminate\Support\Collection;

class Stats
{

    const STATUS_ENTER = 1;
    const STATUS_EXIT = 2;

    public static function getStatusesFromStatsCollection(Collection $col): array
    {
        return array_map(function ($row) {
            return $row->status;
        }, $col->toArray());
    }

    public static function calculateInterval(string $from, string $to): AggregatedInterval
    {
        $statuses = self::fetchInterval($from, $to);
        return self::calculateIntervalData($statuses);
    }

    public static function calculateIntervalData(array $statuses): AggregatedInterval
    {
        $statsInterval = new AggregatedInterval();
        $statsInterval = array_reduce($statuses, function (AggregatedInterval $interval, $status) {
            if ($status === self::STATUS_ENTER) {
                $interval->trackEnter();
            } else {
                $interval->trackExit();
            }
            return $interval;
        }, $statsInterval);

        return $statsInterval;
    }

    public static function aggregateIntervals(array $intervals): AggregatedInterval
    {
        $aggInterval = new AggregatedInterval();

        $res = array_reduce($intervals, function (AggregatedInterval $aggInterval, AggregatedInterval $interval) {
            $intervalFullMax = $aggInterval->getSurfersAtTheEnd() + $interval->getMaxSurfers();
            $fullMax = $intervalFullMax > $aggInterval->getMaxSurfers() ? $intervalFullMax : $aggInterval->getMaxSurfers();
            $fullEndCounter = $aggInterval->getSurfersAtTheEnd() + $interval->getSurfersAtTheEnd();
            return new AggregatedInterval($interval->getDatetime(), $fullMax, $fullEndCounter);
        }, $aggInterval);

        return $res;
    }

    public static function logStatus(int $status, string $datetime = null): void
    {
        SurfersLogs::add($datetime, $status);
    }

    public static function storeAggregation(string $datetime, int $maxSurfers, int $surfersAtTheEnd)
    {
        SurfersLogsBySeconds::add($datetime, $maxSurfers, $surfersAtTheEnd);
    }

    public static function storeAggregationPart(string $datetime, int $maxSurfers, int $surfersAtTheEnd, int $part)
    {
        SurfersLogsBySecondsParts::add($datetime, $maxSurfers, $surfersAtTheEnd, $part);
    }

    public static function getIntervalPartsToAggregate(): array
    {
        $intervalPartsData = SurfersLogsBySecondsParts::getNextSecondParts();
        $intervalParts = [];
        foreach ($intervalPartsData as $intervalPartData) {
            $intervalParts[] = new AggregatedInterval(
                $intervalPartData->datetime,
                $intervalPartData->max_surfers,
                $intervalPartData->surfers_at_the_end
            );
        }
        return $intervalParts;
    }

    public static function getLastAggregatedInterval(): AggregatedInterval
    {
        $lastAggregatedIntervalData = SurfersLogsBySeconds::getLastEntry();
        if ($lastAggregatedIntervalData) {
            return new AggregatedInterval(
                $lastAggregatedIntervalData->datetime,
                $lastAggregatedIntervalData->max_surfers,
                $lastAggregatedIntervalData->surfers_at_the_end
            );
        }
        return new AggregatedInterval();
    }

    public static function fetchFirstLogEntry()
    {
        return SurfersLogs::getFirstEntry();
    }

    public static function isPartAggregated(string $second, int $part): bool
    {
        return SurfersLogsBySecondsParts::isPartExists($second, $part);
    }

    public static function removePart(string $second, int $part): void
    {
        SurfersLogsBySecondsParts::removePart($second, $part);
    }

    public static function getIntervalSize(string $from, string $to): int
    {
        $logSize = SurfersLogs::getLogsSize($from, $to);
        return $logSize->count;
    }

    public static function fetchInterval(string $from, string $to, int $limit = null, int $offset = null): array
    {
        $logRows = SurfersLogs::getLogs($from, $to, $limit, $offset);
        return self::getStatusesFromStatsCollection($logRows);
    }

    public static function secondHasLogs(string $second): bool
    {
        return SurfersLogs::newerLogsExist($second);
    }

    public static function getCachedInterval(string $from, string $to): AggregatedInterval
    {
        $lastDatetime = null;
        $maxSurfers = 0;
        $surfersAtTheEnd = 0;

        $cachedIntervalsSummary = SurfersLogsBySeconds::getMaxAggregation($from, $to);

        if ($cachedIntervalsSummary->datetime) {
            $lastDatetime = $cachedIntervalsSummary->datetime;
            $maxSurfers = $cachedIntervalsSummary->max_surfers;
            $cachedIntervalEnd = SurfersLogsBySeconds::getSurfersAtTheEndByDatetime($lastDatetime);
            $surfersAtTheEnd = $cachedIntervalEnd->surfers_at_the_end;
        }
        $aggregatedInterval = new AggregatedInterval($lastDatetime, $maxSurfers, $surfersAtTheEnd);
        return $aggregatedInterval;
    }
}