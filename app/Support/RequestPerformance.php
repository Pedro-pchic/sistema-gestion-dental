<?php

namespace App\Support;

class RequestPerformance
{
    public int $queryCount = 0;

    public int $slowQueryCount = 0;

    public float $queryTimeMs = 0;

    public float $slowestQueryMs = 0;

    public function recordQuery(float $durationMs, float $slowQueryMs): void
    {
        $this->queryCount++;
        $this->queryTimeMs += $durationMs;
        $this->slowestQueryMs = max($this->slowestQueryMs, $durationMs);

        if ($durationMs >= $slowQueryMs) {
            $this->slowQueryCount++;
        }
    }
}
