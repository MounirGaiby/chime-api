<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Conversation;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function chat(Request $request, Conversation $conversation)
    {
        Log::channel('chat')->info('Chat request received', [
            'user_id' => auth()->id(),
            'conversation_id' => $conversation->id,
            'request_data' => $request->all(),
            'files' => $request->hasFile('attachments') ? 'Has files' : 'No files',
        ]);

        if ($conversation->user_id !== auth()->id()) {
            Log::channel('chat')->warning('Unauthorized chat attempt', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversation->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Log the raw request for debugging
            Log::channel('attachments')->debug('Raw request', [
                'files' => $request->allFiles(),
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
            ]);

            $request->validate([
                'message' => 'required|string',
                'model' => 'nullable|string',
                'temperature' => 'nullable|numeric|min:0|max:1'
            ]);

            if ($request->model && !$this->aiService->validateModel($request->model)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected model is invalid.'
                ], 422);
            }

            // Process message with attachments
            $messageWithAttachments = $request->message;
            if ($request->hasFile('attachments')) {
                Log::channel('attachments')->info('Processing attachments', [
                    'attachment_count' => count($request->file('attachments')),
                ]);

                foreach ($request->file('attachments') as $index => $file) {
                    Log::channel('attachments')->info('Processing file', [
                        'index' => $index,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    // Add file content to message
                    $fileContent = $this->aiService->provider->processFileContent($file);
                    $messageWithAttachments .= "\n\nFile content (" . $file->getClientOriginalName() . "):\n" . $fileContent;
                }
            }

            // Get conversation history
            $previousMessages = [];
            $previousChats = $conversation->chats()
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($previousChats as $previousChat) {
                $previousMessages[] = ['role' => 'user', 'content' => $previousChat->message];
                $previousMessages[] = ['role' => 'assistant', 'content' => $previousChat->response];
            }

            // Check total token count
            $totalTokens = $conversation->chats()->sum('tokens_used');
            if ($totalTokens > 8000) { // Set a reasonable limit
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation is too long. Please start a new one.',
                    'total_tokens' => $totalTokens
                ], 400);
            }

            $response = $this->aiService->chat(
                $messageWithAttachments,
                $request->model,
                $request->temperature,
                $previousMessages
            );

            $chat = $conversation->chats()->create([
                'message' => $request->message,
                'response' => $response->choices[0]->message->content,
                'reasoning_content' => $response->choices[0]->message->reasoning_content ?? null,
                'model' => $request->model ?? config('ai.providers.deepseek.default_model'),
                'tokens_used' => $response->usage->total_tokens,
                'temperature' => $request->temperature ?? $this->aiService->getDefaultTemperature(
                    $request->model ?? config('ai.providers.deepseek.default_model')
                ),
            ]);

            // Handle attachments
            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachment) {
                    $attachmentData = [
                        'type' => $attachment['type'],
                        'name' => $attachment['name'] ?? null,
                    ];

                    if ($attachment['type'] === 'url') {
                        $attachmentData['url'] = $attachment['url'];
                    } else {
                        $path = $attachment['file']->store('chat-attachments', 'public');
                        $attachmentData['path'] = $path;
                    }

                    $chat->attachments()->create($attachmentData);
                }
            }

            $conversation->update(['last_message_at' => now()]);

            // Add token warning if needed
            $warningMessage = null;
            if ($totalTokens > 6000) {
                $warningMessage = 'Conversation is getting long. Consider starting a new one soon.';
            }

            return response()->json([
                'success' => true,
                'data' => $chat->load('attachments'),
                'warning' => $warningMessage,
                'total_tokens' => $totalTokens + $response->usage->total_tokens
            ]);

        } catch (\Exception $e) {
            Log::channel('chat')->error('Chat error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getModels()
    {
        try {
            $apiModels = $this->aiService->getModels();
            $configuredModels = config('ai.providers.deepseek.allowed_models');
            
            // Filter API models to only show configured ones
            $availableModels = array_intersect(
                array_column($apiModels['data'], 'id'),
                array_keys($configuredModels)
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'models' => $availableModels,
                    'default_model' => config('ai.providers.deepseek.default_model'),
                    'model_configs' => $configuredModels,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $chats = $conversation->chats()
            ->orderBy('created_at', 'asc')  // oldest first for chat flow
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'chats' => $chats
            ]
        ]);
    }

    public function chatStream(Request $request, Conversation $conversation)
    {
        if ($conversation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $request->validate([
                'message' => 'required|string',
                'model' => ['nullable', 'string', Rule::in(['deepseek-chat', 'deepseek-reasoner'])],
                'temperature' => 'nullable|numeric|between:0.1,1.0',
            ]);

            $stream = $this->aiService->chatStream(
                $request->message,
                $request->model,
                $request->temperature
            );

            return response()->stream(function () use ($stream, $conversation, $request) {
                $fullResponse = '';
                $fullReasoningContent = '';
                $tokens = 0;

                while (!$stream->eof()) {
                    $line = trim($stream->read(4096));
                    if (empty($line)) continue;

                    $lines = explode("\n", $line);
                    foreach ($lines as $jsonLine) {
                        if (empty($jsonLine)) continue;
                        if (strpos($jsonLine, 'data: ') === 0) {
                            $jsonLine = substr($jsonLine, 6);
                        }
                        
                        try {
                            $chunk = json_decode($jsonLine, true);
                            if (json_last_error() !== JSON_ERROR_NONE) continue;

                            if (isset($chunk['choices'][0]['delta']['reasoning_content'])) {
                                $content = $chunk['choices'][0]['delta']['reasoning_content'];
                                $fullReasoningContent .= $content;
                                
                                echo "data: " . json_encode([
                                    'reasoning_content' => $content,
                                    'done' => false
                                ]) . "\n\n";
                                
                                ob_flush();
                                flush();
                            }

                            if (isset($chunk['choices'][0]['delta']['content'])) {
                                $content = $chunk['choices'][0]['delta']['content'];
                                $fullResponse .= $content;
                                
                                echo "data: " . json_encode([
                                    'content' => $content,
                                    'done' => false
                                ]) . "\n\n";
                                
                                ob_flush();
                                flush();
                            }

                            if (isset($chunk['usage']['total_tokens'])) {
                                $tokens = $chunk['usage']['total_tokens'];
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                $chat = $conversation->chats()->create([
                    'message' => $request->message,
                    'response' => $fullResponse,
                    'reasoning_content' => $fullReasoningContent ?: null,
                    'model' => $request->model ?? config('ai.providers.deepseek.default_model'),
                    'tokens_used' => $tokens,
                    'temperature' => $request->temperature ?? $this->aiService->getDefaultTemperature(
                        $request->model ?? config('ai.providers.deepseek.default_model')
                    ),
                ]);

                $conversation->update(['last_message_at' => now()]);

                // Send final message
                echo "data: " . json_encode([
                    'content' => '',
                    'done' => true,
                    'chat' => $chat
                ]) . "\n\n";
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no' // For Nginx
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 