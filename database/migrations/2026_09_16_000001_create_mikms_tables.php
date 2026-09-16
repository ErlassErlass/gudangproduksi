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
        // 1. Master Modul
        Schema::create('mikms_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Bill of Materials (BOM)
        Schema::create('mikms_bom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('mikms_modules')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('box_category', 100)->nullable();
            $table->integer('quantity')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        // 3. Master Box Kit
        Schema::create('mikms_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('box_code', 50)->unique();
            $table->string('category', 100);
            $table->string('program_code', 50)->nullable();
            $table->string('status', 30)->default('READY'); // RAW, READY, IN_MODULE, IN_BOX, ON_LOAN, RETURNED, REPAIR, DAMAGED, DISPOSED
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Form Produksi Modul
        Schema::create('mikms_productions', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->foreignId('module_id')->constrained('mikms_modules')->cascadeOnDelete();
            $table->integer('quantity_produced')->default(1);
            $table->string('produced_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Form QC Modul
        Schema::create('mikms_qc_logs', function (Blueprint $table) {
            $table->id();
            $table->date('qc_date');
            $table->foreignId('module_id')->constrained('mikms_modules')->cascadeOnDelete();
            $table->string('target_box_code', 50)->nullable();
            $table->string('status_qc', 30); // LOLOS, TIDAK LOLOS
            $table->text('defect_notes')->nullable();
            $table->string('checked_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Form Pengiriman Sekolah
        Schema::create('mikms_shipments', function (Blueprint $table) {
            $table->id();
            $table->date('shipment_date');
            $table->string('box_code', 50);
            $table->string('program_code', 50)->nullable();
            $table->string('program_name', 100)->nullable();
            $table->string('school_name', 150);
            $table->integer('quantity_box')->default(1);
            $table->string('shipped_by', 100);
            $table->string('received_by_school', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Form Pengembalian dari Sekolah
        Schema::create('mikms_returns', function (Blueprint $table) {
            $table->id();
            $table->date('return_date');
            $table->string('box_code', 50);
            $table->string('school_name', 150);
            $table->string('condition', 30); // LENGKAP, RUSAK, HILANG
            $table->string('problematic_item_code', 50)->nullable();
            $table->string('problematic_item_name', 150)->nullable();
            $table->integer('problematic_quantity')->default(0);
            $table->string('received_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Form Repair
        Schema::create('mikms_repairs', function (Blueprint $table) {
            $table->id();
            $table->date('repair_date');
            $table->string('item_code', 50);
            $table->string('item_name', 150);
            $table->string('asset_id', 50)->nullable();
            $table->string('damage_type', 150);
            $table->string('repair_action', 255);
            $table->integer('quantity')->default(1);
            $table->string('repair_result', 30); // BERHASIL, GAGAL
            $table->string('repaired_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Form Stock Opname
        Schema::create('mikms_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->date('opname_date');
            $table->string('item_code', 50);
            $table->string('item_name', 150);
            $table->integer('system_quantity')->default(0);
            $table->integer('physical_quantity')->default(0);
            $table->integer('difference')->default(0);
            $table->string('difference_reason', 255)->nullable();
            $table->string('counted_by', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikms_stock_opnames');
        Schema::dropIfExists('mikms_repairs');
        Schema::dropIfExists('mikms_returns');
        Schema::dropIfExists('mikms_shipments');
        Schema::dropIfExists('mikms_qc_logs');
        Schema::dropIfExists('mikms_productions');
        Schema::dropIfExists('mikms_boxes');
        Schema::dropIfExists('mikms_bom');
        Schema::dropIfExists('mikms_modules');
    }
};
