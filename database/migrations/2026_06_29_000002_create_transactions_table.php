<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->integer('qty')->unsigned();
            $table->date('transaction_date');

            // Common fields
            $table->string('no_dokumen', 50)->nullable()->comment('Nomor dokumen/surat jalan');
            $table->string('no_po', 50)->nullable()->comment('Nomor Purchase Order');
            $table->string('no_prn', 50)->nullable()->comment('Nomor Purchase Request');
            $table->string('job_number', 50)->nullable()->comment('Nomor Job/Order');
            $table->string('transfer_order', 50)->nullable()->comment('Nomor Transfer Order / P.R.O');

            // Masuk-specific
            $table->string('sumber', 100)->nullable()->comment('Asal barang masuk (supplier/pengirim)');

            // Keluar-specific
            $table->string('penerima', 100)->nullable()->comment('Nama penerima barang');
            $table->string('keperluan', 100)->nullable()->comment('Keperluan/tujuan penggunaan');

            // Metadata
            $table->string('lokasi', 50)->nullable()->comment('Lokasi gudang');
            $table->string('petugas', 100)->nullable()->comment('Nama petugas yang input');
            $table->text('catatan')->nullable();

            // Sync tracking (untuk offline support)
            $table->string('client_id', 50)->nullable()->unique()->comment('Client-generated ID for dedup');

            $table->timestamps();

            $table->index('tipe');
            $table->index('transaction_date');
            $table->index(['item_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
