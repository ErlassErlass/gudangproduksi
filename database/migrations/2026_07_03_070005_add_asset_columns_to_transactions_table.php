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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('asset_id')->after('item_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('vendor_id')->after('asset_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('customer_id')->after('vendor_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('location_id')->after('customer_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('tipe_detail', ['pembelian', 'sewa_keluar', 'sewa_kembali', 'mutasi_lokasi', 'pembuangan'])->after('tipe')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['asset_id', 'vendor_id', 'customer_id', 'location_id', 'tipe_detail']);
        });
    }
};
