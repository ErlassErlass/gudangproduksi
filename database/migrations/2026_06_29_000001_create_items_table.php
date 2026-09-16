<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('satuan', 20)->default('pcs');
            $table->string('produk', 50)->nullable()->comment('Group/kategori: Modul, Microbit, Sewa, Marketing, Umum');
            $table->string('komponen', 50)->nullable()->comment('Sub-group: Modul Scratch, Beginner Micro, dll');
            $table->string('lokasi_default', 50)->nullable()->comment('Lokasi penyimpanan default');
            $table->integer('min_stok')->default(0)->comment('Minimum stok warning threshold');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('produk');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
