<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

abstract class StatsAggregateCommand extends Command
{
    // Должно совпадать со значением numprocs в конфиге supervisor-а, иначе не будет агрегироваться
    public const NUMBER_OF_PROCESSES = 5;

    public function __construct()
    {
        parent::__construct();
    }

    protected function wait(int $millisecs)
    {
        time_nanosleep(0, $millisecs * 1000 * 1000);
    }
}