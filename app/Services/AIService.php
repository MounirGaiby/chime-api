<?php

namespace App\Services;

use App\Services\AI\Providers\DeepseekProvider;
use InvalidArgumentException;

class AIService
{
    private $provider;
    private $config;

    public function __construct()
    {
        $this->config = config('ai');
        $this->initializeProvider();
    }

    private function initializeProvider()
    {
        $providerName = $this->config['default_provider'];
        $providerConfig = $this->config['providers'][$providerName];

        switch ($providerName) {
            case 'deepseek':
                $this->provider = new DeepseekProvider($providerConfig);
                break;
            // Add more providers here as needed
            default:
                throw new InvalidArgumentException("Unsupported AI provider: {$providerName}");
        }
    }

    public function chat(string $message, string $model = null, float $temperature = null, array $previousMessages = [])
    {
        return $this->provider->chat($message, $model, $temperature, $previousMessages);
    }

    public function getModels()
    {
        return $this->provider->getModels();
    }

    public function validateModel(string $model): bool
    {
        return $this->provider->validateModel($model);
    }

    public function validateTemperature(string $model, float $temperature): bool
    {
        return $this->provider->validateTemperature($model, $temperature);
    }

    public function getDefaultTemperature(string $model): float
    {
        return $this->provider->getDefaultTemperature($model);
    }

    public function chatStream(string $message, string $model = null, float $temperature = null)
    {
        return $this->provider->chatStream($message, $model, $temperature);
    }
} 