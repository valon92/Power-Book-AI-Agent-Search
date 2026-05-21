<?php

return [

    'enabled' => env('EBAY_ENABLED', true),

    'client_id' => env('EBAY_CLIENT_ID'),

    'client_secret' => env('EBAY_CLIENT_SECRET'),

    'sandbox' => env('EBAY_SANDBOX', false),

    'marketplace_id' => env('EBAY_MARKETPLACE_ID', 'EBAY_DE'),

    'limit' => (int) env('EBAY_SEARCH_LIMIT', 12),

    'timeout' => (int) env('EBAY_TIMEOUT', 15),

];
