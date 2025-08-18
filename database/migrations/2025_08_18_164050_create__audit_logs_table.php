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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'publish_attempt', 'compliance_check', 'rule_update'
            $table->foreignId('article_id')->nullable()->constrained()->onDelete('set null');
            $table->string('feed_name')->nullable();
            $table->json('context'); // Additional context data
            $table->string('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['event_type', 'created_at']);
            $table->index('article_id');
            $table->index('feed_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_audit_logs');
    }
};
