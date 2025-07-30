<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | This tells Laravel to look for a variable named GEMINI_API_KEY
    | in your .env file and use its value as the API key.
    |
    */
    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Organization (Optional)
    |--------------------------------------------------------------------------
    |
    | This is optional. You can leave it as is.
    |
    */
    'organization' => env('GEMINI_ORGANIZATION'),

];
