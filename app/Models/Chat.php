<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message',
        'response',
        'reasoning_content',
        'model',
        'tokens_used',
        'temperature'
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'temperature' => 'float',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
} 