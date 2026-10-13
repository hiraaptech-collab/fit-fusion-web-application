<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // The original OpenAI-compatible provider; credentials are server-side only.
    'fusion_ai' => [
        'api_key' => env('AI_API_KEY'),
        'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai'),
        'model' => env('AI_MODEL', 'openai/gpt-oss-20b'),
        'timeout' => 25,
        'connect_timeout' => 5,
        'requests_per_minute' => 20,
        'allow_guest_provider' => env('AI_ALLOW_GUEST_PROVIDER', false),
        'user_requests_per_minute' => (int) env('AI_USER_REQUESTS_PER_MINUTE', 5),
        'user_requests_per_day' => (int) env('AI_USER_REQUESTS_PER_DAY', 60),
        'guest_requests_per_day' => (int) env('AI_GUEST_REQUESTS_PER_DAY', 5),
        'global_requests_per_minute' => (int) env('AI_GLOBAL_REQUESTS_PER_MINUTE', 20),
        'global_requests_per_day' => (int) env('AI_GLOBAL_REQUESTS_PER_DAY', 1000),
        'global_tokens_per_minute' => (int) env('AI_GLOBAL_TOKENS_PER_MINUTE', 8000),
        'global_tokens_per_day' => (int) env('AI_GLOBAL_TOKENS_PER_DAY', 150000),
        'max_input_bytes' => (int) env('AI_MAX_INPUT_BYTES', 4000),
        'concurrent_requests' => (int) env('AI_CONCURRENT_REQUESTS', 8),
    ],

];


