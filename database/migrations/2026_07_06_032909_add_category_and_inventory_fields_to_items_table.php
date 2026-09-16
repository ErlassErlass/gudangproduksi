<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Kategori Barang — FK ke master kategori
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('categories')
                  ->nullOnDelete();

            // Jenis Barang: INV = Inventory (barang fisik), SVC = Service (jasa)
            $table->enum('jenis_barang', ['INV', 'SVC'])
                  ->default('INV')
                  ->after('satuan');

            // UPC / Barcode (kode external, berbeda dengan kode internal QR)
            $table->string('upc_barcode', 100)
                  ->nullable()
                  ->after('jenis_barang');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'jenis_barang', 'upc_barcode']);
        });
    }
};
