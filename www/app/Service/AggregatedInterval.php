<?php

namespace App\Service;

class AggregatedInterval
{

    private $exists = false;
    private $datetime;
    private $maxSurfers;
    private $surfersAtTheEnd;
    private $previousIntervalMerged = false;

    public function __construct(string $datetime = null, int $maxSurfers = 0, int $surfersAtTheEnd = 0)
    {
        $this->datetime = $datetime;
        $this->maxSurfers = $maxSurfers;
        $this->surfersAtTheEnd = $surfersAtTheEnd;
        if ($datetime) {
            $this->exists = true;
        }
    }

    public function isExists(): bool
    {
        return $this->exists;
    }

    public function getDatetime(): string
    {
        return $this->datetime;
    }

    public function getMaxSurfers(): int
    {
        return $this->maxSurfers;
    }

    public function getSurfersAtTheEnd(): int
    {
        return $this->surfersAtTheEnd;
    }

    public function trackEnter(): void
    {
        $this->surfersAtTheEnd += 1;
        if ($this->surfersAtTheEnd > $this->maxSurfers) {
            $this->maxSurfers = $this->surfersAtTheEnd;
        }
    }

    public function trackExit(): void
    {
        $this->surfersAtTheEnd -= 1;
    }

    public function mergePreviousInterval(AggregatedInterval $previousInterval): void
    {
        if (!$this->previousIntervalMerged) {
            $this->maxSurfers += $previousInterval->getSurfersAtTheEnd();
            if ($previousInterval->getMaxSurfers() > $this->maxSurfers) {
                $this->maxSurfers = $previousInterval->getMaxSurfers();
            }
            $this->surfersAtTheEnd += $previousInterval->getSurfersAtTheEnd();
            $this->previousIntervalMerged = true;
        }
    }
}