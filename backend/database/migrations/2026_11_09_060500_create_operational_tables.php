<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4–9 operational tables:
 *  - Procurement (PR, PO, GRN, Purchase Cost Histories)
 *  - Production Planning / MRP & Production Execution (Plans, MRP Runs, Orders, Work Orders,
 *    Material Requests/Issues, Production Results, Waste, Production Cost Summaries/Items)
 *  - Quality Control & Repair Orders
 *  - Vendor / GAP Processing & Shipments
 *  - Rental & Loan Orders / Returns
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Procurement ---
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no', 64)->unique();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | SUBMITTED | APPROVED | REJECTED
            $table->date('needed_by')->nullable();
            $table->text('justification')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained('purchase_requisitions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->decimal('estimated_unit_price', 18, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 64)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained('purchase_requisitions')->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | SUBMITTED | APPROVED | PARTIAL | RECEIVED | CANCELLED
            $table->char('currency_code', 3)->default('IDR');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('ordered_qty', 18, 4);
            $table->decimal('received_qty', 18, 4)->default(0);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no', 64)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('receiving_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('quarantine_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | INSPECTED | POSTED
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->decimal('received_qty', 18, 4);
            $table->decimal('accepted_qty', 18, 4)->default(0);
            $table->decimal('rejected_qty', 18, 4)->default(0);
            $table->decimal('actual_unit_price', 18, 6);
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_cost_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('goods_receipt_item_id')->nullable()->constrained('goods_receipt_items')->nullOnDelete();
            $table->decimal('unit_price', 18, 6);
            $table->char('currency_code', 3)->default('IDR');
            $table->timestamp('effective_at');
            $table->timestamps();
            $table->index(['product_id', 'effective_at']);
        });

        // --- Production Planning / MRP & Execution ---
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_no', 64)->unique();
            $table->string('title', 160);
            $table->date('plan_date');
            $table->date('due_date');
            $table->string('priority', 16)->default('NORMAL'); // LOW | NORMAL | HIGH | URGENT
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | SUBMITTED | APPROVED | REJECTED | CANCELLED
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('project_name', 160)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('target_qty', 18, 4);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->date('due_date')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('production_mrp_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->cascadeOnDelete();
            $table->unsignedInteger('run_no');
            $table->string('status', 24)->default('COMPLETED');
            $table->timestamp('calculated_at');
            $table->json('parameters_json')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();
        });

        Schema::create('production_mrp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('production_mrp_runs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('gross_requirement', 18, 4);
            $table->decimal('available_qty', 18, 4);
            $table->decimal('reserved_qty', 18, 4);
            $table->decimal('confirmed_incoming_qty', 18, 4);
            $table->decimal('safety_stock_qty', 18, 4);
            $table->decimal('net_requirement', 18, 4);
            $table->decimal('suggested_purchase_qty', 18, 4);
            $table->timestamps();
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 64)->unique();
            $table->foreignId('production_plan_item_id')->nullable()->constrained('production_plan_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('target_qty', 18, 4);
            $table->foreignId('bom_version_id')->constrained('bom_versions')->restrictOnDelete();
            $table->json('bom_snapshot_json');
            $table->json('stage_snapshot_json')->nullable();
            $table->string('status', 24)->default('PLANNED')->index(); // PLANNED | RELEASED | IN_PROGRESS | ON_HOLD | COMPLETED | CLOSED | CANCELLED
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_no', 64)->unique();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->string('stage_code', 48);
            $table->string('stage_name', 120);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->foreignId('output_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('target_qty', 18, 4);
            $table->string('status', 24)->default('PENDING')->index(); // PENDING | IN_PROGRESS | COMPLETED
            $table->boolean('qc_required')->default(true);
            $table->boolean('qc_passed')->default(false);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 64)->unique();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | SUBMITTED | APPROVED | PARTIAL | FULFILLED | REJECTED | CANCELLED
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained('material_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('requested_qty', 18, 4);
            $table->decimal('approved_qty', 18, 4)->default(0);
            $table->decimal('issued_qty', 18, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_no', 64)->unique();
            $table->foreignId('material_request_id')->constrained('material_requests')->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('POSTED');
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->constrained('material_issues')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->decimal('issued_qty', 18, 4);
            $table->decimal('returned_unused_qty', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 6);
            $table->decimal('total_cost', 18, 2);
            $table->timestamps();
        });

        Schema::create('production_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->timestamp('result_date');
            $table->decimal('input_qty', 18, 4);
            $table->decimal('good_qty', 18, 4)->default(0);
            $table->decimal('reject_qty', 18, 4)->default(0);
            $table->decimal('scrap_qty', 18, 4)->default(0);
            $table->decimal('wip_remaining_qty', 18, 4)->default(0);
            $table->decimal('approved_process_loss_qty', 18, 4)->default(0);
            $table->foreignId('output_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('production_cost_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->unique()->constrained('production_orders')->cascadeOnDelete();
            $table->decimal('total_material_cost', 18, 2)->default(0);
            $table->decimal('total_good_qty', 18, 4)->default(0);
            $table->decimal('total_reject_qty', 18, 4)->default(0);
            $table->decimal('material_cost_per_good_unit', 18, 6)->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
        });

        Schema::create('production_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('actual_quantity', 18, 4); // signed: positive on issue, negative on unused return
            $table->decimal('unit_cost', 18, 6);
            $table->decimal('total_cost', 18, 2);
            $table->foreignId('inventory_transaction_item_id')->unique()->constrained('inventory_transaction_items')->restrictOnDelete();
            $table->timestamps();
        });

        // --- Vendor / GAP, QC, Rental, Distribution ---
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_order_no', 64)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('processing_type', 64)->default('ASSEMBLY');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('vendor_location_id')->constrained('locations')->restrictOnDelete();
            $table->date('expected_return_date')->nullable();
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | DISPATCHED | PARTIAL_RETURNED | COMPLETED | CLOSED
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('sent_quantity', 18, 4);
            $table->decimal('completed_quantity', 18, 4)->default(0);
            $table->decimal('outstanding_quantity', 18, 4)->default(0);
            $table->decimal('damaged_quantity', 18, 4)->default(0);
            $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 64)->unique();
            $table->foreignId('vendor_order_id')->constrained('vendor_orders')->restrictOnDelete();
            $table->foreignId('receiving_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->string('receipt_status', 24)->default('QUARANTINE')->index(); // QUARANTINE | INSPECTED | POSTED
            $table->foreignId('inventory_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_receipt_id')->constrained('vendor_receipts')->cascadeOnDelete();
            $table->foreignId('vendor_order_item_id')->constrained('vendor_order_items')->restrictOnDelete();
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('accepted_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->decimal('outstanding_quantity', 18, 4)->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('rental_orders', function (Blueprint $table) {
            $table->id();
            $table->string('rental_no', 64)->unique();
            $table->string('transaction_kind', 16)->default('RENTAL'); // RENTAL | LOAN
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('project_name', 160)->nullable();
            $table->string('group_leader', 120)->nullable();
            $table->string('sales_pic', 120)->nullable();
            $table->date('start_date');
            $table->date('expected_return_date');
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | RESERVED | DISPATCHED | RETURNED_PENDING_QC | COMPLETED
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained('rental_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->json('expected_contents_json'); // kit composition snapshot
            $table->decimal('dispatched_qty', 18, 4)->default(0);
            $table->decimal('returned_qty', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('rental_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 64)->unique();
            $table->foreignId('rental_order_id')->constrained('rental_orders')->restrictOnDelete();
            $table->foreignId('return_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('returned_at');
            $table->string('status', 24)->default('QUARANTINE'); // QUARANTINE | INSPECTED_PASSED | INSPECTED_ISSUE
            $table->timestamps();
        });

        Schema::create('rental_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_return_id')->constrained('rental_returns')->cascadeOnDelete();
            $table->foreignId('rental_order_item_id')->constrained('rental_order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->decimal('expected_qty', 18, 4);
            $table->decimal('actual_qty', 18, 4);
            $table->decimal('missing_qty', 18, 4)->default(0);
            $table->decimal('damaged_qty', 18, 4)->default(0);
            $table->string('condition_notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_no', 64)->unique();
            $table->string('inspection_type', 32); // INCOMING | PRODUCTION_STAGE | FINISHED_GOODS | RENTAL_RETURN | VENDOR_RETURN
            $table->string('source_document_type', 64);
            $table->unsignedBigInteger('source_document_id');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('inspected_at');
            $table->string('status', 24)->default('PENDING'); // PENDING | PASSED | FAILED | CONDITIONAL
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['source_document_type', 'source_document_id'], 'quality_source_document_idx');
        });

        Schema::create('quality_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('discrepancy_type', 24); // MISSING | DAMAGED | EXCESS | WRONG_ITEM
            $table->decimal('quantity', 18, 4);
            $table->decimal('reference_unit_price', 18, 2)->nullable();
            $table->decimal('estimated_claim_amount', 18, 2)->nullable();
            $table->string('resolution_status', 24)->default('OPEN');
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_no', 64)->unique();
            $table->string('shipment_type', 32); // CUSTOMER | RENTAL | VENDOR | INTERNAL
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->string('source_document_type', 64);
            $table->unsignedBigInteger('source_document_id');
            $table->string('status', 24)->default('DRAFT')->index(); // DRAFT | IN_TRANSIT | DELIVERED
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('tracking_reference', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('quality_discrepancies');
        Schema::dropIfExists('quality_inspections');
        Schema::dropIfExists('rental_return_items');
        Schema::dropIfExists('rental_returns');
        Schema::dropIfExists('rental_order_items');
        Schema::dropIfExists('rental_orders');
        Schema::dropIfExists('vendor_receipt_items');
        Schema::dropIfExists('vendor_receipts');
        Schema::dropIfExists('vendor_order_items');
        Schema::dropIfExists('vendor_orders');
        Schema::dropIfExists('production_cost_items');
        Schema::dropIfExists('production_cost_summaries');
        Schema::dropIfExists('production_results');
        Schema::dropIfExists('material_issue_items');
        Schema::dropIfExists('material_issues');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('production_mrp_items');
        Schema::dropIfExists('production_mrp_runs');
        Schema::dropIfExists('production_plan_items');
        Schema::dropIfExists('production_plans');
        Schema::dropIfExists('purchase_cost_histories');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};
