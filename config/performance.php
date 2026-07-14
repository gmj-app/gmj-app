<?php

return [
    'slow_request_logging' => [
        'enabled' => (bool) env('PERFORMANCE_LOG_ENABLED', false),
        'threshold_ms' => max(1, (int) env('PERFORMANCE_SLOW_REQUEST_MS', 1000)),
    ],
];
