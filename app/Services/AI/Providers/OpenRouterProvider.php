<?php

namespace App\Services\AI\Providers;

use InvalidArgumentException;
use App\Models\AIModel;

class OpenRouterProvider extends BaseProvider
{
    public function chat(string $message, string $model = null, float $temperature = null, array $previousMessages = [], array $files = null)
    {
        // Get default model if none specified
        if (!$model) {
            $model = AIModel::where('provider_id', $this->provider->id)
                ->where('is_active', true)
                ->first()
                ->name;
        }

        if (!$this->validateModel($model)) {
            throw new InvalidArgumentException('Invalid model specified');
        }

        if ($temperature !== null && !$this->validateTemperature($model, $temperature)) {
            throw new InvalidArgumentException('Invalid temperature for the specified model');
        }

        $temperature = $temperature ?? $this->getDefaultTemperature($model);

        // Build messages array with history and handle files if present
        $messages = $previousMessages;

        if ($files && $this->modelSupportsFiles($model)) {
            $content = [];
            $content[] = ['type' => 'text', 'text' => $message];

            foreach ($files as $file) {
                if ($file['type'] === 'image') {
                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $file['url']
                        ]
                    ];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        $response = $this->http->post($this->getModelEndpoint($model), [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        $responseData = $response->json();

        return (object) [
            'choices' => [
                (object) [
                    'message' => (object) [
                        'content' => $responseData['choices'][0]['message']['content']
                    ]
                ]
            ],
            'usage' => (object) $responseData['usage']
        ];
    }

    private function modelSupportsFiles(string $model): bool
    {
        $aiModel = AIModel::where('name', $model)
            ->where('provider_id', $this->provider->id)
            ->first();

        if (!$aiModel) {
            \Log::debug('Model not found', ['model' => $model, 'provider_id' => $this->provider->id]);
            return false;
        }

        // Check both the model's supports_files field and additional_settings
        $additionalSupport = !empty($aiModel->additional_settings['supports_files']);

        \Log::debug('Model file support check', [
            'model' => $model,
            'supports_files' => $aiModel->supports_files,
            'additional_settings' => $aiModel->additional_settings,
            'result' => ($aiModel->supports_files || $additionalSupport)
        ]);

        return $aiModel->supports_files || $additionalSupport;
    }

    public function chatStream(string $message, string $model = null, float $temperature = null)
    {
        if (!$model) {
            $model = AIModel::where('provider_id', $this->provider->id)
                ->where('is_active', true)
                ->first()
                ->name;
        }

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
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $temperature,
            'stream' => true,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        return $response->toPsrResponse()->getBody();
    }
}
