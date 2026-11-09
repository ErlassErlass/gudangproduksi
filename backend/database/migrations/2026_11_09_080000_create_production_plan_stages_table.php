<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plan_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('name', 160);
            $table->decimal('weight_percentage', 5, 2)->default(0.00); // 0 - 100%
            $table->string('status', 24)->default('PENDING')->index(); // PENDING | IN_PROGRESS | COMPLETED
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_stages');
    }
};
