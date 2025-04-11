<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->vector('vector', 1536); // Dimensión típica para embeddings de LLMs
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
}; 