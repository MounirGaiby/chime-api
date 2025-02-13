<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type');
            $table->string('key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['provider_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
}; 