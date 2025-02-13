<?php

namespace App\Services;

use App\Models\AIProvider;
use App\Models\AIModel;
use InvalidArgumentException;

class AIService
{
    private $provider;

    public function __construct()
    {
        $defaultProvider = AIProvider::where('is_default', true)->first();
        if (!$defaultProvider) {
            throw new \Exception('No default AI provider configured');
        }

        $providerClass = $defaultProvider->implementation_class;
        $this->provider = new $providerClass($defaultProvider);
    }

    private function getProviderForModel(string $model = null)
    {
        if (!$model) {
            return $this->provider;
        }

        // Find the model in the database
        $aiModel = AIModel::with('provider')->where('name', $model)->first();
        if (!$aiModel) {
            return $this->provider;
        }

        // If it's the same provider, return current provider
        if ($aiModel->provider_id === $this->provider->getProvider()->id) {
            return $this->provider;
        }

        // Create new provider instance
        $providerClass = $aiModel->provider->implementation_class;
        return new $providerClass($aiModel->provider);
    }

    public function chat(string $message, string $model = null, float $temperature = null, array $previousMessages = [])
    {
        $provider = $this->getProviderForModel($model);
        return $provider->chat($message, $model, $temperature, $previousMessages);
    }

    public function validateModel(string $model): bool
    {
        $provider = $this->getProviderForModel($model);
        return $provider->validateModel($model);
    }

    public function validateTemperature(string $model, float $temperature): bool
    {
        $provider = $this->getProviderForModel($model);
        return $provider->validateTemperature($model, $temperature);
    }

    public function getDefaultTemperature(string $model): float
    {
        $provider = $this->getProviderForModel($model);
        return $provider->getDefaultTemperature($model);
    }

    public function chatStream(string $message, string $model = null, float $temperature = null)
    {
        $provider = $this->getProviderForModel($model);
        return $provider->chatStream($message, $model, $temperature);
    }

    public function getModels()
    {
        return AIModel::with('provider')
            ->where('is_active', true)
            ->get();
    }
} 