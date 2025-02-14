<?php

namespace App\Services\AI\Providers;

use InvalidArgumentException;

class DeepseekProvider extends BaseProvider
{
    public function chat(string $message, string $model = null, float $temperature = null, array $previousMessages = [])
    {
        $model = $model ?? $this->config['default_model'];

        if (!$this->validateModel($model)) {
            throw new InvalidArgumentException('Invalid model specified');
        }

        if ($temperature !== null && !$this->validateTemperature($model, $temperature)) {
            throw new InvalidArgumentException('Invalid temperature for the specified model');
        }

        $temperature = $temperature ?? $this->getDefaultTemperature($model);

        // Build messages array with history
        $messages = $previousMessages;
        $messages[] = ['role' => 'user', 'content' => $message];

        // Calculate total tokens (approximate)
        $totalTokens = $this->calculateApproximateTokens($messages);
        if ($totalTokens > 10000) { // DeepSeek's limit
            throw new \Exception('Conversation is too long. Please start a new one.');
        }

        $response = $this->http->post($this->getModelEndpoint($model), [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        $responseData = $response->json();

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

    private function calculateApproximateTokens(array $messages): int
    {
        $totalChars = 0;
        foreach ($messages as $message) {
            $totalChars += strlen($message['content']);
        }
        // Rough approximation: 1 token ≈ 4 characters
        return (int) ceil($totalChars / 4);
    }

    private function processFileContent($file): string
    {
        $content = '';
        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'txt':
                $content = file_get_contents($file->getRealPath());
                break;
            case 'pdf':
                // You might want to use a PDF parser library here
                $content = 'PDF content: ' . file_get_contents($file->getRealPath());
                break;
            // Add more file type handlers as needed
            default:
                $content = 'File content type not supported: ' . $extension;
        }

        return $content;
    }

    public function getModels()
    {
        $response = $this->http->get($this->config['models_endpoint']);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch models from DeepSeek API');
        }

        return $response->json();
    }

    public function chatStream(string $message, string $model = null, float $temperature = null, array $previousMessages = [])
    {
        $model = $model ?? $this->config['default_model'];

        if (!$this->validateModel($model)) {
            throw new InvalidArgumentException('Invalid model specified');
        }

        if ($temperature !== null && !$this->validateTemperature($model, $temperature)) {
            throw new InvalidArgumentException('Invalid temperature for the specified model');
        }

        $temperature = $temperature ?? $this->getDefaultTemperature($model);

        // Build messages array with history
        $messages = $previousMessages;
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $this->http->withOptions([
            'stream' => true,
        ])->post($this->getModelEndpoint($model), [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => true,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        return $response->toPsrResponse()->getBody();
    }
}
