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
        Schema::create('feed_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 'MSN', 'GoogleNews', 'AppleNews'
            $table->string('display_name');
            $table->json('configuration'); // Feed-specific settings
            $table->json('compliance_rules'); // Array of rule names
            $table->boolean('is_active')->default(true);
            $table->string('api_endpoint')->nullable();
            $table->json('api_credentials')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_feed_configs');
    }
};
