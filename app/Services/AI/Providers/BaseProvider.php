<?php

namespace App\Services\AI\Providers;

use App\Models\APIKey;
use App\Models\AIModel;
use App\Models\AIProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseProvider
{
    protected $provider;
    protected $http;
    protected $apiKey;

    public function __construct(AIProvider $provider)
    {
        $this->provider = $provider;
        $this->apiKey = $this->getApiKey();

        if (!$this->apiKey) {
            throw new \Exception('No valid API key found for provider');
        }

        $this->http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->baseUrl($provider->base_url);
    }

    public function getProvider(): AIProvider
    {
        return $this->provider;
    }

    protected function getApiKey()
    {
        return APIKey::where('provider_type', strtolower($this->provider->name))
            ->where('is_active', true)
            ->value('key');
    }

    protected function getModelEndpoint(string $modelName): string
    {
        $model = AIModel::where('name', $modelName)
            ->where('provider_id', $this->provider->id)
            ->first();

        return $model ? $model->endpoint : '/chat/completions';
    }

    public function validateModel(string $modelName): bool
    {
        $exists = AIModel::where('name', $modelName)
            ->where('provider_id', $this->provider->id)
            ->where('is_active', true)
            ->exists();

        Log::debug('Model validation', [
            'model_name' => $modelName,
            'provider_id' => $this->provider->id,
            'exists' => $exists
        ]);

        return $exists;
    }

    public function validateTemperature(string $modelName, float $temperature): bool
    {
        $model = AIModel::where('name', $modelName)
            ->where('provider_id', $this->provider->id)
            ->first();

        return $model && $temperature >= $model->min_temperature &&
               $temperature <= $model->max_temperature;
    }

    public function getDefaultTemperature(string $modelName): float
    {
        return AIModel::where('name', $modelName)
            ->where('provider_id', $this->provider->id)
            ->value('default_temperature') ?? 0.7;
    }

    public function getModels()
    {
        return AIModel::where('provider_id', $this->provider->id)
            ->where('is_active', true)
            ->get();
    }

    abstract public function chat(string $message, string $model = null, float $temperature = null, array $previousMessages = []);
}
