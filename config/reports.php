<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Report data sources
    |--------------------------------------------------------------------------
    |
    | Configure the table and column names used by the built-in order and
    | customer summaries. Adapt these to your application's schema.
    |
    */
    'orders' => [
        'table' => 'orders',
        'id' => 'id',
        'customer_id' => 'customer_id',
        'total' => 'total',
        'status' => 'status',
        'created_at' => 'created_at',
        'paid_statuses' => ['paid', 'completed'],
    ],
    'customers' => [
        'table' => 'users',
        'id' => 'id',
        'created_at' => 'created_at',
    ],
];
