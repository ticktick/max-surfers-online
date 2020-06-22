<?php

namespace App\Console\Commands;

use App\Exceptions\NothingToAggregate;
use App\Service\AggregatedInterval;
use App\Service\DateHelpers;
use App\Service\Stats;

class StatsAggregateWorker extends StatsAggregateCommand
{
    protected $signature = 'stats:aggregate-worker {num}';

    protected $description = 'This worker makes pre-aggregation which then aggregated fully by stats:aggregate';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $processNum = (int)$this->argument('num');
        if ($processNum + 1 > self::NUMBER_OF_PROCESSES) {
            throw new \LogicException('Only ' . self::NUMBER_OF_PROCESSES . ' workers supported');
        }

        while (true) {
            $lastAggregatedInterval = Stats::getLastAggregatedInterval();

            $needAggregate = true;
            try {
                $from = $this->getNextSecondToAggregate($lastAggregatedInterval);
                $partAggregated = Stats::isPartAggregated($from, $processNum);
                // Проверяем, что данные по секунде полностью в логе и уже началась логгироваться следующая секунда
                $intervalFinished = Stats::secondHasLogs(DateHelpers::addSecond($from));
            } catch (NothingToAggregate $e) {
                $needAggregate = false;
            }

            if ($needAggregate) {
                $needAggregate = !$partAggregated && $intervalFinished;
            }

            if (!$needAggregate) {
                $this->wait(300);
                continue;
            }

            if ($lastAggregatedInterval->isExists()) {
                Stats::removePart($lastAggregatedInterval->getDatetime(), $processNum);
            }

            $to = DateHelpers::addSecond($from);

            $totalRowsCount = Stats::getIntervalSize($from, $to);
            $rowsCountToProcess = $this->getNumberOfRowsToProcess($totalRowsCount);
            $fromRow = $this->getStartRowNumber($processNum, $rowsCountToProcess);

            $logRows = Stats::fetchInterval($from, $to, $rowsCountToProcess, $fromRow);
            $interval = Stats::calculateIntervalData($logRows);

            Stats::storeAggregationPart($from,
                $interval->getMaxSurfers(),
                $interval->getSurfersAtTheEnd(),
                $processNum
            );

            $this->wait(300);
        }

    }

    private function getNumberOfRowsToProcess(int $numberOfRows): int
    {
        return ceil($numberOfRows / self::NUMBER_OF_PROCESSES);
    }

    private function getStartRowNumber(int $processNum, int $numberOfRowsToProcess): int
    {
        return $numberOfRowsToProcess * $processNum;
    }

    /**
     * @param AggregatedInterval $lastAggregatedInterval
     * @return string
     * @throws NothingToAggregate
     */
    private function getNextSecondToAggregate(AggregatedInterval $lastAggregatedInterval): string
    {
        $lastAggregatedIntervalExists = $lastAggregatedInterval->isExists();

        $nextLogIntervalToAggregate = null;
        if ($lastAggregatedIntervalExists) {
            $nextLogIntervalToAggregate = DateHelpers::addSecond($lastAggregatedInterval->getDatetime());
        } else {
            $firstLogEntry = Stats::fetchFirstLogEntry();
            if ($firstLogEntry) {
                $firstItemLogDatetime = $firstLogEntry->datetime;
                $nextLogIntervalToAggregate = DateHelpers::getSecond($firstItemLogDatetime);
            } else {
                throw new NothingToAggregate();
            }
        }

        return $nextLogIntervalToAggregate;
    }
}