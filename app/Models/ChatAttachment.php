<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'type',
        'name',
        'path',
        'url',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }
} 