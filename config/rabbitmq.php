<?php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'queues' => [
        'c2b_notifications' => env('RABBITMQ_C2B_QUEUE', 'pbsys.mpesa.c2b.notifications'),
        'transaction_status_results' => env('RABBITMQ_TRANSACTION_STATUS_QUEUE', 'pbsys.mpesa.transaction_status.results'),
    ],
];
