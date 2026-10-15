<?php

return [
    'sender_number' => env('WA_SENDER_NUMBER', ''),
    'provider' => env('WA_PROVIDER', 'evolution'),
    'evolution' => [
        'url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'key' => env('EVOLUTION_API_KEY', ''),
        'instance' => env('EVOLUTION_INSTANCE', 'credit-hp'),
    ],
    'fonnte' => [
        'url' => env('WA_GATEWAY_URL', 'https://api.fonnte.com/send'),
        'token' => env('WA_GATEWAY_TOKEN', ''),
    ],
    'reminders' => [
        'enabled' => (bool) env('WA_REMINDERS_ENABLED', false),
        'time' => env('WA_REMINDER_TIME', '09:00'),
        'timezone' => env('WA_REMINDER_TIMEZONE', 'Asia/Jakarta'),
        'max_attempts' => 3,
        'retry_minutes' => 15,
    ],
];
