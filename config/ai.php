<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),
    'model' => env('AI_MODEL', 'llama-3.1-8b-instant'),
    'key' => env('GROQ_API_KEY') ?: env('OPENAI_API_KEY'),
    'groq_url' => 'https://api.groq.com/openai/v1/chat/completions',
];
