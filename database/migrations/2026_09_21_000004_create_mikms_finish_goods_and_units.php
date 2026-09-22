<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Produk Finish Good (5 produk)
        Schema::create('mikms_finish_goods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();  // MLK, ROBO, JIMU, ERBO, ALK
            $table->string('name', 150);
            $table->enum('type', ['assembled', 'direct']); // assembled = dari modul, direct = dari vendor
            $table->boolean('can_rent')->default(true);
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Unit Registry — inti sistem, setiap unit fisik punya 1 baris
        Schema::create('mikms_units', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 30)->unique(); // MLK-00001, JIMU-00032
            $table->foreignId('product_id')->constrained('mikms_finish_goods')->cascadeOnDelete();
            $table->enum('status', ['qc_pending', 'in_stock', 'dispatched', 'returned', 'repair', 'disposed'])
                  ->default('qc_pending');
            $table->enum('source_type', ['production', 'direct_input']);
            $table->unsignedBigInteger('source_id')->nullable(); // FK ke mikms_productions atau mikms_direct_inputs
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index('serial_number');
        });

        // 3. Direct Input — untuk Jimu Trackbot, Erboblox, Arduino Learning Kit
        Schema::create('mikms_direct_inputs', function (Blueprint $table) {
            $table->id();
            $table->date('input_date');
            $table->foreignId('product_id')->constrained('mikms_finish_goods')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('supplier', 150)->nullable();
            $table->string('received_by', 100);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. QC Checklist Template per Produk
        Schema::create('mikms_qc_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('mikms_finish_goods')->cascadeOnDelete();
            $table->string('check_item', 255); // "Power ON — LED indikator menyala"
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'active']);
        });

        // 5. Dispatch — pengeluaran unit dari gudang (ringan, opsional destination)
        Schema::create('mikms_dispatches', function (Blueprint $table) {
            $table->id();
            $table->date('dispatch_date');
            $table->foreignId('unit_id')->constrained('mikms_units')->cascadeOnDelete();
            $table->enum('type', ['sewa', 'beli'])->default('beli');
            $table->string('destination', 255)->nullable(); // nama tujuan, bebas isi
            $table->string('dispatched_by', 100);
            $table->text('notes')->nullable();
            $table->timestamp('returned_at')->nullable(); // diisi saat unit kembali (sewa)
            $table->timestamps();
        });

        // 6. Alter mikms_qc_logs — ganti module_id dengan unit_id, tambah checklist JSON
        Schema::table('mikms_qc_logs', function (Blueprint $table) {
            // Hapus FK lama ke module
            $table->dropForeign(['module_id']);
            $table->dropColumn(['module_id', 'target_box_code']);

            // Tambah relasi ke unit
            $table->foreignId('unit_id')->nullable()->after('qc_date')
                  ->constrained('mikms_units')->nullOnDelete();
            $table->string('qc_type', 50)->default('production')->after('unit_id');
            $table->json('checklist_results')->nullable()->after('status_qc');
            // checklist_results format: [{"item": "Power ON", "pass": true}, ...]
        });

        // 7. Alter mikms_returns — hapus school_name, tambah unit_id
        Schema::table('mikms_returns', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'box_code', 'problematic_item_code',
                                'problematic_item_name', 'problematic_quantity']);
            $table->foreignId('unit_id')->nullable()->after('return_date')
                  ->constrained('mikms_units')->nullOnDelete();
            $table->enum('condition', ['LENGKAP', 'RUSAK', 'HILANG'])->change();
        });

        // 8. Alter mikms_shipments — hapus school_name dan field distribusi
        // (mikms_shipments dipertahankan sebagai log historis tapi dibersihkan)
        Schema::table('mikms_shipments', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'received_by_school', 'program_name', 'box_code', 'program_code']);
            $table->foreignId('unit_id')->nullable()->after('shipment_date')
                  ->constrained('mikms_units')->nullOnDelete();
            $table->string('destination', 255)->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        // Restore shipments
        Schema::table('mikms_shipments', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'destination']);
            $table->string('box_code', 50)->nullable();
            $table->string('program_code', 50)->nullable();
            $table->string('program_name', 100)->nullable();
            $table->string('school_name', 150)->nullable();
            $table->string('received_by_school', 100)->nullable();
        });

        // Restore returns
        Schema::table('mikms_returns', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id']);
            $table->string('box_code', 50)->nullable();
            $table->string('school_name', 150)->nullable();
            $table->string('problematic_item_code', 50)->nullable();
            $table->string('problematic_item_name', 150)->nullable();
            $table->integer('problematic_quantity')->default(0);
        });

        // Restore qc_logs
        Schema::table('mikms_qc_logs', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'qc_type', 'checklist_results']);
            $table->foreignId('module_id')->nullable()->constrained('mikms_modules')->nullOnDelete();
            $table->string('target_box_code', 50)->nullable();
        });

        Schema::dropIfExists('mikms_dispatches');
        Schema::dropIfExists('mikms_qc_checklist_templates');
        Schema::dropIfExists('mikms_direct_inputs');
        Schema::dropIfExists('mikms_units');
        Schema::dropIfExists('mikms_finish_goods');
    }
};
