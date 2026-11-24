<?php

declare(strict_types=1);

namespace AbTesting\Analysis;

final class HolmCorrection
{
    public static function adjust(array $pValues): array
    {
        $count = count($pValues);
        $order = array_keys($pValues);
        usort($order, static fn (int $left, int $right): int => $pValues[$left] <=> $pValues[$right]);
        $adjusted = $pValues;
        $runningMaximum = 0.0;
        foreach ($order as $rank => $index) {
            $runningMaximum = max($runningMaximum, min(1.0, ($count - $rank) * $pValues[$index]));
            $adjusted[$index] = $runningMaximum;
        }
        return $adjusted;
    }
}
