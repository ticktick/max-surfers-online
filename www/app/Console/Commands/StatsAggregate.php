<?php

namespace App\Console\Commands;

use App\Service\AggregatedInterval;
use App\Service\Stats;

class StatsAggregate extends StatsAggregateCommand
{

    protected $signature = 'stats:aggregate';

    protected $description = 'Aggregates stats intervals from parts aggregated by workers';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        while (true) {
            $needAggregate = true;
            $intervalParts = Stats::getIntervalPartsToAggregate();
            if (count($intervalParts) !== self::NUMBER_OF_PROCESSES) {
                $needAggregate = false;
            }

            $newStatsInterval = Stats::aggregateIntervals($intervalParts);
            $lastAggregatedInterval = Stats::getLastAggregatedInterval();

            if ($this->isIntervalOutdated($lastAggregatedInterval, $newStatsInterval)) {
                $needAggregate = false;
            }

            if ($needAggregate) {
                $newStatsInterval->mergePreviousInterval($lastAggregatedInterval);
                $this->storeAggregatedInterval($newStatsInterval);
            }

            $this->wait(300);
        }
    }

    private function isIntervalOutdated(AggregatedInterval $lastInterval, AggregatedInterval $newInterval)
    {
        if (!$lastInterval->isExists()) {
            return false;
        }
        return $newInterval->getDatetime() <= $lastInterval->getDatetime();
    }

    private function storeAggregatedInterval(AggregatedInterval $statsInterval): void
    {
        Stats::storeAggregation(
            $statsInterval->getDatetime(),
            $statsInterval->getMaxSurfers(),
            $statsInterval->getSurfersAtTheEnd()
        );
    }

}