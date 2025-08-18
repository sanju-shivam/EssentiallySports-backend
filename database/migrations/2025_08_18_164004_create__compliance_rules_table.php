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
        Schema::create('compliance_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('validator_class');
            $table->json('parameters')->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Lower numbers = higher priority
            $table->timestamps();
            
            $table->index(['name', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_compliance_rules');
    }
};
