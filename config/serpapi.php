<?php

return [

    'enabled' => env('SERPAPI_ENABLED', true),

    'api_key' => env('SERPAPI_KEY'),

    'limit' => (int) env('SERPAPI_LIMIT', 12),

    'timeout' => (int) env('SERPAPI_TIMEOUT', 20),

    'gl' => env('SERPAPI_GL', 'de'),

    'hl' => env('SERPAPI_HL', 'en'),

];
