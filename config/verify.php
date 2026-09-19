<?php

/*
 |--------------------------------------------------------------------------
 | verify.mn — Mobile-Originated SMS phone verification (Mongolia)
 |--------------------------------------------------------------------------
 | Flow: POST /sessions (phone + one-time 6-digit code) -> user sends that code
 | by SMS to shortcode 144773 -> we confirm with GET /sessions/{id} until
 | sessionStatus === VERIFIED (poll every 3s and/or on the GET callback).
 | Every SMS costs the user 150₮, so the UI stops the send action the moment
 | the session is VERIFIED and creates a fresh session (new code) on EXPIRED.
 */

return [
    // Local simulation when no API key is configured (development only).
    'mock' => env('VERIFY_MOCK', false),

    // Require customers to have a verified phone before placing an order.
    'require_for_checkout' => env('VERIFY_REQUIRE_FOR_CHECKOUT', true),

    'api_key' => env('VERIFY_MN_API_KEY'),
    'base_url' => rtrim(env('VERIFY_MN_BASE_URL', 'https://api.verify.mn'), '/'),

    // Public https URL verify.mn should GET when the SMS arrives. Leave empty
    // to rely on polling only (never register a URL that is not reachable).
    'callback_url' => env('VERIFY_MN_CALLBACK_URL'),

    // Optional reply SMS (<=160 ASCII chars). Carrier-dependent: Unitel sends
    // only the default reply, Lime sends none — never treat it as confirmation.
    'response_sms' => env('VERIFY_MN_RESPONSE_SMS'),

    'shortcode' => '144773',
    'poll_interval' => 3,       // seconds; verify.mn asks not to poll faster
    'session_ttl' => 300,       // seconds; server-side TTL of a session
    'http_timeout' => 10,       // seconds per API request
];
