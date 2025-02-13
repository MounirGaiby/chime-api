<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AIProviderType;

class AIProvider extends Model
{
    use HasFactory;

    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'base_url',
        'implementation_class'
    ];

    public function models()
    {
        return $this->hasMany(AIModel::class, 'provider_id');
    }

    public function getApiKey()
    {
        return APIKey::where('provider_type', $this->name)
            ->where('is_active', true)
            ->value('key');
    }
} 