<?php

return [

    'api_key' => env('OPENAI_API_KEY'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),

    'vision_enabled' => env('OPENAI_VISION_ENABLED', true),

    'enabled' => env('OPENAI_ENABLED', true),

    'timeout' => (int) env('OPENAI_TIMEOUT', 20),

];
