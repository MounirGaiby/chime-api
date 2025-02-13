<?php

namespace App\Enums;

enum AIProviderType: string
{
    case DEEPSEEK = 'deepseek';
    case OPENROUTER = 'openrouter';

    public function getImplementationClass(): string
    {
        return match($this) {
            self::DEEPSEEK => \App\Services\AI\Providers\DeepseekProvider::class,
            self::OPENROUTER => \App\Services\AI\Providers\OpenRouterProvider::class,
        };
    }

    public function getBaseUrl(): string
    {
        return match($this) {
            self::DEEPSEEK => 'https://api.deepseek.com',
            self::OPENROUTER => 'https://openrouter.ai/api/v1',
        };
    }
} 