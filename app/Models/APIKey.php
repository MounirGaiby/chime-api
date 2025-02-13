<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class APIKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'provider_type',
        'key',
        'is_active'
    ];

    protected $hidden = [
        'key'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
} 