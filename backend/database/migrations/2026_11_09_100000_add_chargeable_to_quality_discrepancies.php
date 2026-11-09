<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->string('customer_name', 160)->nullable()->after('product_id');
            $table->decimal('total_claim_amount', 18, 2)->default(0)->after('status');
        });

        Schema::table('quality_discrepancies', function (Blueprint $table) {
            $table->boolean('is_chargeable')->default(true)->after('estimated_claim_amount');
            $table->string('component_tier', 32)->nullable()->after('discrepancy_type'); // BOX | MODULE | COMPONENT
        });
    }

    public function down(): void
    {
        Schema::table('quality_discrepancies', function (Blueprint $table) {
            $table->dropColumn(['is_chargeable', 'component_tier']);
        });

        Schema::table('quality_inspections', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'total_claim_amount']);
        });
    }
};
