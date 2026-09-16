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
        // Stok modul jadi (M01-M11) yang sudah dirakit dan siap dipakai
        Schema::create('mikms_module_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('mikms_modules')->cascadeOnDelete();
            $table->integer('stock_ready')->default(0);
            $table->timestamps();

            $table->unique('module_id');
        });

        // Riwayat pesanan paket (Package Order) dengan log deduction cascading
        Schema::create('mikms_package_orders', function (Blueprint $table) {
            $table->id();
            $table->date('order_date');
            $table->string('program_code', 50); // MLK, ROBOTIC
            $table->string('program_name', 100)->nullable();
            $table->integer('package_qty')->default(1);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('status', 30)->default('COMPLETED'); // PENDING, PROCESSING, COMPLETED, CANCELLED
            $table->json('deduction_log')->nullable(); // Full breakdown: modules from stock vs raw assembled
            $table->string('ordered_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Riwayat adjustment stok modul jadi
        Schema::create('mikms_module_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('mikms_modules')->cascadeOnDelete();
            $table->string('type', 30); // production_in, package_out, manual_adjust, return_in
            $table->integer('quantity'); // positive = masuk, negative = keluar
            $table->integer('stock_before')->default(0);
            $table->integer('stock_after')->default(0);
            $table->string('reference_type', 50)->nullable(); // mikms_productions, mikms_package_orders, manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('performed_by', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikms_module_stock_logs');
        Schema::dropIfExists('mikms_package_orders');
        Schema::dropIfExists('mikms_module_stocks');
    }
};
