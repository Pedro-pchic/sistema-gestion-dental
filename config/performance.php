<?php

return [
    'enabled' => (bool) env('PERFORMANCE_LOG_ENABLED', false),
    'slow_query_ms' => (float) env('PERFORMANCE_SLOW_QUERY_MS', 100),
];
