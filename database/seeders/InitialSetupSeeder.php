<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\APIKey;
use App\Enums\AIProviderType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@chime.ai',
            'password' => Hash::make('admin@2024'),
            'is_super_admin' => true
        ]);

        // Parse API keys from env
        $apiKeysJson = env('MODELS_API_KEY');
        if (!$apiKeysJson) {
            Log::warning('No API keys found in environment variables');
            return;
        }

        try {
            $apiKeys = json_decode($apiKeysJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format in MODELS_API_KEY: ' . json_last_error_msg());
            }

            // Create API Keys if valid
            foreach ($apiKeys as $provider => $key) {
                if (!empty($key)) {
                    APIKey::create([
                        'provider_type' => str_replace('-api-key', '', $provider),
                        'key' => $key,
                        'is_active' => true
                    ]);
                }
            }

            // Only create providers and models if they have valid API keys
            if (APIKey::where('provider_type', 'deepseek')->exists()) {
                $deepseekProvider = AIProvider::create([
                    'name' => AIProviderType::DEEPSEEK->value,
                    'base_url' => AIProviderType::DEEPSEEK->getBaseUrl(),
                    'implementation_class' => AIProviderType::DEEPSEEK->getImplementationClass(),
                    'is_default' => true
                ]);

                // Create models in database instead of config
                $deepseekProvider->models()->createMany([
                    [
                        'name' => 'deepseek-chat',
                        'display_name' => 'Deepseek Chat',
                        'endpoint' => '/chat/completions',
                        'min_temperature' => 0.1,
                        'max_temperature' => 1.0,
                        'default_temperature' => 0.7,
                        'can_reason' => false,
                        'can_access_web' => true,
                        'supports_files' => false,
                        'is_active' => true
                    ],
                    [
                        'name' => 'deepseek-reasoner',
                        'display_name' => 'Deepseek Reasoner',
                        'endpoint' => '/chat/completions',
                        'min_temperature' => 0.1,
                        'max_temperature' => 0.8,
                        'default_temperature' => 0.5,
                        'can_reason' => true,
                        'can_access_web' => true,
                        'supports_files' => false,
                        'is_active' => true
                    ]
                ]);
            }

            if (APIKey::where('provider_type', 'openrouter')->exists()) {
                AIProvider::create([
                    'name' => AIProviderType::OPENROUTER->value,
                    'base_url' => AIProviderType::OPENROUTER->getBaseUrl(),
                    'implementation_class' => AIProviderType::OPENROUTER->getImplementationClass()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error seeding API keys: ' . $e->getMessage());
            throw $e;
        }
    }
} 