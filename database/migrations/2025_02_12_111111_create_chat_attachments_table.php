<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->string('type'); // file, image, url, etc.
            $table->string('name');
            $table->string('path')->nullable(); // for files
            $table->text('url')->nullable(); // for web links
            $table->text('metadata')->nullable(); // JSON field for additional data
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_attachments');
    }
}; 