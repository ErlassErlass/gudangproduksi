<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plan_stages', function (Blueprint $table) {
            $table->decimal('produced_qty', 18, 4)->default(0)->after('weight_percentage');
            $table->string('output_category', 24)->nullable()->after('produced_qty'); // KURANG | SESUAI | LEBIH
        });
    }

    public function down(): void
    {
        Schema::table('production_plan_stages', function (Blueprint $table) {
            $table->dropColumn(['produced_qty', 'output_category']);
        });
    }
};
