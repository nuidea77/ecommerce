<?php

/*
 |--------------------------------------------------------------------------
 | verify.mn identity verification
 |--------------------------------------------------------------------------
 | The integration follows the standard OAuth 2.0 / OpenID Connect
 | authorization-code flow: the customer is redirected to verify.mn, confirms
 | their identity (e-Mongolia / DAN), and is sent back to our callback with a
 | code that we exchange for the verified profile (register number, names).
 |
 | Endpoint URLs and claim names are configurable so they can be aligned with
 | the merchant documentation you receive from verify.mn without code changes.
 | With VERIFY_MOCK=true nothing leaves the server: a local simulation page
 | stands in for verify.mn so the whole flow can be exercised in development.
 */

return [
    'mock' => env('VERIFY_MOCK', true),

    // Require customers to be verified before placing an order.
    'require_for_checkout' => env('VERIFY_REQUIRE_FOR_CHECKOUT', true),

    'client_id' => env('VERIFY_CLIENT_ID'),
    'client_secret' => env('VERIFY_CLIENT_SECRET'),
    'redirect_uri' => env('VERIFY_REDIRECT_URI'),
    'scopes' => env('VERIFY_SCOPES', 'openid profile'),

    'authorize_url' => env('VERIFY_AUTHORIZE_URL', 'https://verify.mn/oauth/authorize'),
    'token_url' => env('VERIFY_TOKEN_URL', 'https://verify.mn/oauth/token'),
    'userinfo_url' => env('VERIFY_USERINFO_URL', 'https://verify.mn/oauth/userinfo'),

    // Claim names returned by the userinfo endpoint (adjust to verify.mn's schema).
    'claims' => [
        'subject' => env('VERIFY_CLAIM_SUBJECT', 'sub'),
        'register_number' => env('VERIFY_CLAIM_REGISTER', 'register_number'),
        'last_name' => env('VERIFY_CLAIM_LAST_NAME', 'last_name'),
        'first_name' => env('VERIFY_CLAIM_FIRST_NAME', 'first_name'),
        'phone' => env('VERIFY_CLAIM_PHONE', 'phone_number'),
    ],
];
