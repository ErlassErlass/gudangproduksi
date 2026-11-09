<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_barcode_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('batch_no', 64)->unique();
            $table->string('barcode', 120);
            $table->unsignedInteger('quantity');
            $table->timestamp('printed_at');
            $table->unsignedInteger('reprint_count')->default(0);
            $table->timestamp('last_reprinted_at')->nullable();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'printed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcode_prints');
    }
};
