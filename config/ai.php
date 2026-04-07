<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider & Model
    |--------------------------------------------------------------------------
    | Read from .env:  AI_PROVIDER=gemini  AI_MODEL=gemini-2.5-flash
    */
    'provider' => env('AI_PROVIDER', 'gemini'),
    'model'    => env('AI_MODEL', 'gemini-2.5-flash'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    | Gemini analysis prompts with context can take 30-90 seconds.
    */
    'timeout' => (int) env('AI_TIMEOUT', 120),
];
