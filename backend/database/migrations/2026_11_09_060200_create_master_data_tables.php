<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Master Data tables (agent_docs/05_DATABASE_SCHEMA.md §4 and §5).
 * Quantities use DECIMAL(18,4), money DECIMAL(18,2), unit cost DECIMAL(18,6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 80);
            $table->string('dimension', 32)->default('COUNT');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->foreignId('to_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->decimal('factor', 18, 8);
            $table->timestamps();
            $table->unique(['from_uom_id', 'to_uom_id']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Catalog lookup tables (normalized instead of SD/SMP/SMA boolean columns).
        foreach (['channels', 'education_levels', 'class_levels', 'program_types'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('code', 48)->unique();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('legacy_code', 64)->nullable()->index();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('product_type', 24)->index();     // PRODUCT | SERVICE | PROGRAM
            $table->string('physical_type', 24)->index();    // PHYSICAL | DIGITAL | NON_INVENTORY
            $table->string('fulfillment_type', 24)->index(); // SELL | RENTAL | INTERNAL | MIXED
            $table->string('tracking_method', 24)->index();  // QUANTITY | SERIALIZED | BATCH | NONE
            $table->foreignId('base_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->foreignId('program_type_id')->nullable()->constrained('program_types')->nullOnDelete();
            $table->decimal('safety_stock_qty', 18, 4)->default(0);
            $table->decimal('reference_purchase_price', 18, 2)->nullable();
            $table->boolean('is_manufactured')->default(false);
            $table->boolean('is_purchasable')->default(true);
            $table->boolean('is_sellable')->default(false);
            $table->boolean('is_rentable')->default(false);
            $table->string('status', 24)->default('ACTIVE')->index();
            $table->timestamps();
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->string('component_role', 48)->default('MEMBER');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('price_type', 32); // PURCHASE_REF | SELL | RENTAL | COMPENSATION
            $table->decimal('amount', 18, 2);
            $table->char('currency_code', 3)->default('IDR');
            $table->foreignId('channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'price_type', 'effective_from']);
        });

        Schema::create('product_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('business_year')->nullable();
            $table->string('status', 32);
            $table->string('notes', 255)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('barcode', 120)->unique();
            $table->string('barcode_type', 24)->default('CODE128');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 120);
            $table->string('warehouse_type', 32)->default('INTERNAL'); // INTERNAL | EXTERNAL_VENDOR | CUSTOMER
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('barcode', 120)->nullable()->unique();
            $table->string('name', 120);
            $table->string('location_type', 32); // BUILDING | FLOOR | ROOM | RACK | BIN | VENDOR | TRANSIT | QUARANTINE
            $table->boolean('is_external')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });

        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('serial_number', 120)->unique();
            $table->string('barcode', 120)->nullable()->unique();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('current_status', 32)->default('AVAILABLE')->index();
            $table->decimal('acquisition_cost', 18, 6)->nullable();
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('batch_number', 80);
            $table->date('manufactured_at')->nullable();
            $table->date('expiry_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'batch_number']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name', 160);
            $table->string('customer_type', 32)->default('SCHOOL'); // SCHOOL | ORGANIZATION | INDIVIDUAL
            $table->string('school_name', 160)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 48)->nullable();
            $table->string('email', 160)->nullable();
            $table->json('address_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name', 160);
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 48)->nullable();
            $table->string('email', 160)->nullable();
            $table->json('address_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name', 160);
            $table->string('vendor_type', 32)->default('GAP');
            $table->string('contact_name', 120)->nullable();
            $table->string('phone', 48)->nullable();
            $table->json('address_json')->nullable();
            $table->foreignId('default_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 48)->unique();
            $table->string('name', 160);
            $table->string('department', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('product_status_histories');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('products');
        foreach (['program_types', 'class_levels', 'education_levels', 'channels'] as $lookup) {
            Schema::dropIfExists($lookup);
        }
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('uom_conversions');
        Schema::dropIfExists('uoms');
    }
};
