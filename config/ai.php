<?php

return [
    'default_provider' => 'deepseek',
    
    'providers' => [
        'deepseek' => [
            'api_key' => env('OPENAI_DEEPSEEK_API_KEY'),
            'base_url' => 'https://api.deepseek.com',
            'allowed_models' => [
                'deepseek-chat' => [
                    'temperature_range' => [0.1, 1.0],
                    'default_temperature' => 0.7,
                    'endpoint' => '/v1/chat/completions',
                ],
                'deepseek-reasoner' => [
                    'temperature_range' => [0.1, 0.8],
                    'default_temperature' => 0.5,
                    'endpoint' => '/v1/chat/completions',
                ],
            ],
            'default_model' => 'deepseek-chat',
            'models_endpoint' => '/models',
        ],
        // Future providers can be added here
        // 'openai' => [ ... ],
        // 'anthropic' => [ ... ],
    ],
]; 