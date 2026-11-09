<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->foreignId('production_plan_id')->nullable()->after('customer_id')->constrained('production_plans')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->after('production_plan_id')->constrained('products')->nullOnDelete();
            $table->string('order_type', 32)->default('SCHOOL')->index()->after('transaction_kind');
            $table->string('barcode_prefix', 64)->nullable()->after('status');
            $table->unsignedInteger('start_unit_no')->nullable()->after('barcode_prefix');
            $table->unsignedInteger('end_unit_no')->nullable()->after('start_unit_no');
            $table->unsignedInteger('total_units')->default(1)->after('end_unit_no');
            $table->json('unit_codes_json')->nullable()->after('total_units');
        });
    }

    public function down(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropForeign(['production_plan_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'production_plan_id',
                'product_id',
                'order_type',
                'barcode_prefix',
                'start_unit_no',
                'end_unit_no',
                'total_units',
                'unit_codes_json',
            ]);
        });
    }
};
