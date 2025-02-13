<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'provider_id',
        'name',
        'display_name',
        'endpoint',
        'min_temperature',
        'max_temperature',
        'default_temperature',
        'can_reason',
        'can_access_web',
        'supports_files',
        'is_active',
        'additional_settings'
    ];

    protected $casts = [
        'min_temperature' => 'float',
        'max_temperature' => 'float',
        'default_temperature' => 'float',
        'can_reason' => 'boolean',
        'can_access_web' => 'boolean',
        'supports_files' => 'boolean',
        'is_active' => 'boolean',
        'additional_settings' => 'array'
    ];

    public function provider()
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }
} 