<?php

namespace App\Services\AI\Providers;

use InvalidArgumentException;

class DeepseekProvider extends BaseProvider
{
    public function chat(string $message, string $model = null, float $temperature = null)
    {
        $model = $model ?? $this->config['default_model'];

        if (!$this->validateModel($model)) {
            throw new InvalidArgumentException('Invalid model specified');
        }

        if ($temperature !== null && !$this->validateTemperature($model, $temperature)) {
            throw new InvalidArgumentException('Invalid temperature for the specified model');
        }

        $temperature = $temperature ?? $this->getDefaultTemperature($model);
        
        $response = $this->http->post($this->getModelEndpoint($model), [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant'],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $temperature,
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        $responseData = $response->json();
        
        // Transform the response to match the expected format
        return (object) [
            'choices' => [
                (object) [
                    'message' => (object) [
                        'content' => $responseData['choices'][0]['message']['content'],
                        'reasoning_content' => $responseData['choices'][0]['message']['reasoning_content'] ?? null
                    ]
                ]
            ],
            'usage' => (object) $responseData['usage']
        ];
    }

    public function getModels()
    {
        $response = $this->http->get($this->config['models_endpoint']);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch models from DeepSeek API');
        }

        return $response->json();
    }

    public function chatStream(string $message, string $model = null, float $temperature = null)
    {
        $model = $model ?? $this->config['default_model'];

        if (!$this->validateModel($model)) {
            throw new InvalidArgumentException('Invalid model specified');
        }

        if ($temperature !== null && !$this->validateTemperature($model, $temperature)) {
            throw new InvalidArgumentException('Invalid temperature for the specified model');
        }

        $temperature = $temperature ?? $this->getDefaultTemperature($model);
        
        $response = $this->http->withOptions([
            'stream' => true,
        ])->post($this->getModelEndpoint($model), [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant'],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $temperature,
            'stream' => true,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $response->toPsrResponse()->getBody();
    }
} 