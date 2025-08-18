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
        Schema::create('publish_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->string('feed_name');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->json('compliance_results'); // Results of all checks
            $table->json('error_details')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('external_id')->nullable(); // ID from external feed
            $table->timestamps();
            
            $table->index(['article_id', 'feed_name']);
            $table->index(['status', 'attempted_at']);
            $table->index('feed_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_publish_attempts');
    }
};
