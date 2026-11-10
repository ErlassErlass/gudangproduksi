<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plan_stages', function (Blueprint $table) {
            $table->decimal('target_units', 18, 4)->nullable()->after('weight_percentage');
            $table->date('planned_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_stages', function (Blueprint $table) {
            $table->dropColumn(['target_units', 'planned_date']);
        });
    }
};
