<?php

namespace App\Http\Controllers;

use App\Service\AggregatedInterval;
use App\Service\DateHelpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Service\Stats;

class StatsController extends Controller
{
    public function track(Request $request)
    {
        $status = $request->get('status');
        if (!in_array($status, [Stats::STATUS_ENTER, Stats::STATUS_EXIT])) {
            throw new \InvalidArgumentException(sprintf('Please specify status parameter %s (enter) of %s (exit)',
                Stats::STATUS_ENTER,
                Stats::STATUS_EXIT
            ));
        }

        Stats::logStatus($status);
        return response()->json([
            'res' => 'ok',
            'status' => $status,
        ]);
    }

    public function show(Request $request)
    {
        $from = (int)$request->get('from');
        $to = (int)$request->get('to');

        if (!$from || !$to) {
            throw new \InvalidArgumentException('Please specify both "from" and "to" parameters');
        }

        if (!DateHelpers::areDateDiffInInterval($from, $to, 1,  24*60*60)) {
            throw new \InvalidArgumentException('Interval between "from" and "to" should be from 1 sec to 1 day');
        }

        $from = DateHelpers::getDatetime($from);
        $to = DateHelpers::getDatetime($to);

        // Ищем, что есть в агрегированном
        $cachedIntervalStats = Stats::getCachedInterval($from, $to);
        if ($cachedIntervalStats->isExists() && $cachedIntervalStats->getDatetime() < $to) {
            // Добираем недостающую статистику из лога
            $logStats = Stats::calculateInterval(DateHelpers::addSecond($cachedIntervalStats->getDatetime()), $to);
        } else {
            // Берем все из лога, если кеш пустой
            // Если from позже начала лога, то статистика будет некорректной,
            // но я исхожу из условия, что данные приходят и агрегируются регулярно, чтобы не усложнять
            $logStats = Stats::calculateInterval($from, $to);
        }

        $aggInterval = new AggregatedInterval($to, $logStats->getMaxSurfers(), $logStats->getSurfersAtTheEnd());
        $aggInterval->mergePreviousInterval($cachedIntervalStats);
        $resultInterval = $aggInterval;

        return response()->json([
            'max_surfers' => $resultInterval->getMaxSurfers(),
        ]);
    }
}