<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('sales_name', 120)->nullable()->index()->after('name');
        });

        Schema::create('sales_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no', 64)->unique();
            $table->string('group_leader', 120)->index();
            $table->string('sales_name', 120)->index();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->date('loan_date');
            $table->date('return_date')->nullable();
            $table->string('status', 24)->default('DIPINJAM')->index(); // DIPINJAM | DIKEMBALIKAN
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('receipt_no', 64)->nullable()->unique();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_loans');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('sales_name');
        });
    }
};
