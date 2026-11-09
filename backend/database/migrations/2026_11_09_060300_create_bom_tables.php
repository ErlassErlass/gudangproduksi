<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — BOM and Production Stage Template tables (agent_docs/05_DATABASE_SCHEMA.md §6).
 * Supports both STANDARD_MLK (Component → WIP Module → WIP Box → Finished Good) and FLEXIBLE BOMs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_stage_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name', 120);
            $table->string('product_scope', 48)->default('MLK');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_stage_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_template_id')->constrained('production_stage_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('stage_code', 48);
            $table->string('name', 120);
            $table->foreignId('output_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->boolean('qc_required')->default(true);
            $table->timestamps();
            $table->unique(['stage_template_id', 'sequence'], 'pst_items_template_sequence_unique');
        });

        Schema::create('bom_headers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('bom_type', 24)->default('FLEXIBLE'); // STANDARD_MLK | FLEXIBLE
            $table->foreignId('stage_template_id')->nullable()->constrained('production_stage_templates')->nullOnDelete();
            $table->string('status', 24)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('bom_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_header_id')->constrained('bom_headers')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | REVIEW | ACTIVE | RETIRED
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['bom_header_id', 'version_no']);
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_version_id')->constrained('bom_versions')->cascadeOnDelete();
            $table->foreignId('parent_bom_item_id')->nullable()->constrained('bom_items')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->string('item_type', 24)->default('COMPONENT'); // COMPONENT | SUBASSEMBLY
            $table->decimal('quantity', 18, 4);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->decimal('scrap_rate', 8, 4)->default(0); // e.g. 0.0500 = 5%
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->index(['bom_version_id', 'sequence']);
        });

        Schema::create('bom_item_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_item_id')->constrained('bom_items')->cascadeOnDelete();
            $table->foreignId('alternative_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->boolean('requires_approval')->default(true);
            $table->timestamps();
        });

        Schema::create('bom_stage_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_version_id')->constrained('bom_versions')->cascadeOnDelete();
            $table->foreignId('stage_template_item_id')->constrained('production_stage_template_items')->restrictOnDelete();
            $table->foreignId('input_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('output_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_stage_mappings');
        Schema::dropIfExists('bom_item_alternatives');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bom_versions');
        Schema::dropIfExists('bom_headers');
        Schema::dropIfExists('production_stage_template_items');
        Schema::dropIfExists('production_stage_templates');
    }
};
