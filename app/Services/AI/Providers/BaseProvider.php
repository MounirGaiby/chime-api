<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;

abstract class BaseProvider
{
    protected $config;
    protected $http;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json',
        ])->baseUrl($config['base_url']);
    }

    abstract public function chat(string $message, string $model = null, float $temperature = null);
    abstract public function getModels();
    
    public function validateModel(string $model): bool
    {
        return isset($this->config['allowed_models'][$model]);
    }

    public function validateTemperature(string $model, float $temperature): bool
    {
        $range = $this->config['allowed_models'][$model]['temperature_range'];
        return $temperature >= $range[0] && $temperature <= $range[1];
    }

    public function getDefaultTemperature(string $model): float
    {
        return $this->config['allowed_models'][$model]['default_temperature'];
    }

    protected function getModelEndpoint(string $model): string
    {
        return $this->config['allowed_models'][$model]['endpoint'];
    }
} 