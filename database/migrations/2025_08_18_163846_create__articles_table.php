<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->enum('status', ['draft', 'ready', 'published', 'failed'])->default('draft');
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_articles');
    }
};
