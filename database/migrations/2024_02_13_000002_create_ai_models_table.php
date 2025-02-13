<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->onDelete('cascade');
            $table->string('name');
            $table->string('display_name');
            $table->string('endpoint')->default('/v1/chat/completions');
            $table->float('min_temperature')->default(0.1);
            $table->float('max_temperature')->default(1.0);
            $table->float('default_temperature')->default(0.7);
            $table->boolean('can_reason')->default(false);
            $table->boolean('can_access_web')->default(false);
            $table->boolean('supports_files')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();
            
            $table->unique(['provider_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
}; 