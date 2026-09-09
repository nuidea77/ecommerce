<?php

return [
    // When true, no calls are made to QPay; invoices are simulated locally.
    'mock' => env('QPAY_MOCK', true),
    'base_url' => env('QPAY_BASE_URL', 'https://merchant.qpay.mn/v2'),
    'username' => env('QPAY_USERNAME'),
    'password' => env('QPAY_PASSWORD'),
    'invoice_code' => env('QPAY_INVOICE_CODE'),
    'callback_url' => env('QPAY_CALLBACK_URL'),
    'invoice_ttl_minutes' => (int) env('QPAY_INVOICE_TTL', 30),
];
