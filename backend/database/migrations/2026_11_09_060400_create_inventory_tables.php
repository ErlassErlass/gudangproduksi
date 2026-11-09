<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Inventory Engine tables (agent_docs/05_DATABASE_SCHEMA.md §5).
 *
 * `dimension_key` is a normalized non-nullable string (`{product}:{location}:{batch|0}:{serial|0}:{status}`)
 * so MySQL and SQLite enforce a strict 1-row-per-dimension unique constraint even when `batch_id`
 * and `product_serial_id` are NULL (05_DATABASE_SCHEMA.md §5 implementation note).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->string('dimension_key', 191)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->string('stock_status', 24)->default('AVAILABLE');
            $table->decimal('on_hand_qty', 18, 4)->default(0);
            $table->decimal('reserved_qty', 18, 4)->default(0);
            $table->decimal('avg_unit_cost', 18, 6)->default(0);
            $table->unsignedBigInteger('row_version')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'stock_status']);
            $table->index(['location_id', 'stock_status']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 64)->unique();
            $table->string('transaction_type', 32)->index();
            $table->string('status', 24)->default('POSTED')->index(); // DRAFT | POSTED | REVERSED
            $table->timestamp('transaction_date');
            $table->string('source_document_type', 64);
            $table->unsignedBigInteger('source_document_id');
            $table->string('idempotency_key', 128)->unique();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_document_type', 'source_document_id'], 'inv_tx_source_document_idx');
            $table->index('transaction_date');
        });

        Schema::create('inventory_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->string('stock_status_from', 24)->nullable();
            $table->string('stock_status_to', 24)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 6)->nullable();
            $table->decimal('total_cost', 18, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no', 64)->unique();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('consumed_qty', 18, 4)->default(0);
            $table->string('status', 24)->default('ACTIVE')->index();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_no', 64)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 24)->default('DRAFT');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->decimal('book_qty', 18, 4);
            $table->decimal('counted_qty', 18, 4)->nullable();
            $table->decimal('variance_qty', 18, 4)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no', 64)->unique();
            $table->foreignId('stock_opname_id')->nullable()->constrained('stock_opnames')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('reason', 255);
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | SUBMITTED | APPROVED | POSTED | REJECTED
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->string('direction', 8); // IN | OUT
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 6)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_transaction_items');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_balances');
    }
};
