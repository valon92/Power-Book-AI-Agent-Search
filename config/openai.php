<?php

return [

    'api_key' => env('OPENAI_API_KEY'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'enabled' => env('OPENAI_ENABLED', true),

    'timeout' => (int) env('OPENAI_TIMEOUT', 20),

];
