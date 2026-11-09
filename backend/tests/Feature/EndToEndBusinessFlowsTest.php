<?php

namespace Tests\Feature;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\MasterData\Models\Customer;
use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductSerial;
use App\Domains\MasterData\Models\Supplier;
use App\Domains\MasterData\Models\Vendor;
use App\Domains\Warehouse\Models\Location;
use App\Domains\Warehouse\Models\Warehouse;
use App\Models\User;
use App\Shared\Idempotency\IdempotencyService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end integration tests covering Release Gates B, C, D, and E
 * (`agent_docs/11_TESTING_STRATEGY.md` AC-004 to AC-014, `12_IMPLEMENTATION_ROADMAP.md`).
 */
final class EndToEndBusinessFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_procurement_partial_grn_qc_split_and_price_history_preservation(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();

        $po = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'order_date' => '2026-11-09',
            'items' => [
                ['product_id' => $comp16->id, 'ordered_qty' => '50.0000', 'unit_price' => '18000.00', 'uom_id' => $comp16->base_uom_id],
            ],
        ])->assertCreated()->json('data');

        $poItemId = (int) DB::table('purchase_order_items')->where('purchase_order_id', $po['id'])->value('id');

        // Partial receipt: 30 physically received (28 accepted, 2 rejected) at actual price 19000.00 (AC-007, AC-008).
        $grn = $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/purchase-orders/{$po['id']}/goods-receipts",
            [
                'receiving_location_id' => $rackA1->id,
                'items' => [
                    [
                        'purchase_order_item_id' => $poItemId,
                        'received_qty' => '30.0000',
                        'accepted_qty' => '28.0000',
                        'rejected_qty' => '2.0000',
                        'actual_unit_price' => '19000.00',
                    ],
                ],
            ],
            [IdempotencyService::HEADER => 'grn-001'],
        )->assertCreated()->json('data');

        $this->assertSame('PARTIAL', $grn['po_status']);

        // Original PO price remains 18000.00 while receipt price history records 19000.000000 (AC-008).
        $this->assertDatabaseHas('purchase_order_items', ['id' => $poItemId, 'unit_price' => '18000.00', 'received_qty' => '28.0000']);
        $this->assertDatabaseHas('purchase_cost_histories', ['product_id' => $comp16->id, 'unit_price' => '19000.000000']);

        // Accepted 28 goes to AVAILABLE (120 + 28 = 148); rejected 2 goes to QUARANTINE (not available — AC-007).
        $avail = InventoryBalance::query()
            ->where('product_id', $comp16->id)
            ->where('location_id', $rackA1->id)
            ->where('stock_status', StockStatus::AVAILABLE->value)
            ->firstOrFail();
        $quar = InventoryBalance::query()
            ->where('product_id', $comp16->id)
            ->where('location_id', $rackA1->id)
            ->where('stock_status', StockStatus::QUARANTINE->value)
            ->firstOrFail();

        $this->assertSame('148.0000', $avail->on_hand_qty);
        $this->assertSame('2.0000', $quar->on_hand_qty);
        $this->assertSame('0.0000', $quar->availableQty());
    }

    public function test_gap_vendor_dispatch_partial_receipt_and_reconciliation(): void
    {
        // Exact scenario from 03_BUSINESS_PROCESS.md §4 and AC-004, AC-005, AC-006, AC-007:
        // 50 units dispatched to GAP → 47 returned (45 accepted, 2 damaged), 3 remain outstanding at vendor.
        $wm = User::query()->where('email', 'warehouse@erlass.test')->firstOrFail();
        $vendor = Vendor::query()->firstOrFail();
        $mainWh = Warehouse::query()->where('code', 'WH-POP')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();
        $gapLoc = Location::query()->where('code', 'GAP.EXT.01')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();

        $vo = $this->actingAs($wm, 'sanctum')->postJson(
            '/api/v1/vendor-orders',
            [
                'vendor_id' => $vendor->id,
                'warehouse_id' => $mainWh->id,
                'source_location_id' => $rackA1->id,
                'vendor_location_id' => $gapLoc->id,
                'items' => [
                    ['product_id' => $comp16->id, 'sent_quantity' => '50.0000', 'uom_id' => $comp16->base_uom_id],
                ],
            ],
            [IdempotencyService::HEADER => 'gap-dispatch-50'],
        )->assertCreated()->json('data');

        // AC-004: 50 moved to external vendor location with AT_VENDOR status.
        $atVendor = InventoryBalance::query()
            ->where('product_id', $comp16->id)
            ->where('location_id', $gapLoc->id)
            ->where('stock_status', StockStatus::AT_VENDOR->value)
            ->firstOrFail();
        $this->assertSame('50.0000', $atVendor->on_hand_qty);

        $voItemId = (int) DB::table('vendor_order_items')->where('vendor_order_id', $vo['id'])->value('id');

        $receipt = $this->actingAs($wm, 'sanctum')->postJson(
            "/api/v1/vendor-orders/{$vo['id']}/receipts",
            [
                'receiving_location_id' => $rackA1->id,
                'items' => [
                    [
                        'vendor_order_item_id' => $voItemId,
                        'received_quantity' => '47.0000',
                        'accepted_quantity' => '45.0000',
                        'damaged_quantity' => '2.0000',
                    ],
                ],
            ],
            [IdempotencyService::HEADER => 'gap-receipt-47'],
        )->assertCreated()->json('data');

        $this->assertSame('PARTIAL_RETURNED', $receipt['vendor_order_status']);

        // AC-005 & AC-006: 45 completed, 2 damaged, 3 still outstanding at vendor (50 = 45 + 3 + 2).
        $this->assertDatabaseHas('vendor_order_items', [
            'id' => $voItemId,
            'sent_quantity' => '50.0000',
            'completed_quantity' => '45.0000',
            'outstanding_quantity' => '3.0000',
            'damaged_quantity' => '2.0000',
        ]);
        $this->assertSame('3.0000', $atVendor->fresh()?->on_hand_qty);

        // AC-007: Damaged 2 units are in DAMAGED status and have 0 availableQty().
        $damagedBal = InventoryBalance::query()
            ->where('product_id', $comp16->id)
            ->where('location_id', $rackA1->id)
            ->where('stock_status', StockStatus::DAMAGED->value)
            ->firstOrFail();
        $this->assertSame('2.0000', $damagedBal->on_hand_qty);
        $this->assertSame('0.0000', $damagedBal->availableQty());
    }

    public function test_rental_serial_overlap_prevention_quarantine_on_return_and_qc_discrepancy(): void
    {
        $rentalStaff = User::query()->where('email', 'rental@erlass.test')->firstOrFail();
        $customer = Customer::query()->firstOrFail();
        $mlk = Product::query()->where('code', '1001')->firstOrFail();
        $serial1 = ProductSerial::query()->where('serial_number', 'MLK-1001-SN0001')->firstOrFail();
        $rackC1 = Location::query()->where('code', 'POP.L3R3C1.M01')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();

        $order = $this->actingAs($rentalStaff, 'sanctum')->postJson('/api/v1/rental-orders', [
            'transaction_kind' => 'RENTAL',
            'customer_id' => $customer->id,
            'project_name' => 'Workshop Robotika SMAN 1',
            'group_leader' => 'Rina GL',
            'sales_pic' => 'Adi Sales',
            'start_date' => '2026-11-10',
            'expected_return_date' => '2026-11-17',
            'product_id' => $mlk->id,
            'product_serial_id' => $serial1->id,
            'source_location_id' => $rackC1->id,
            'expected_contents' => [
                ['product_id' => $comp16->id, 'expected_qty' => '4.0000'],
            ],
        ])->assertCreated()->json('data');

        // AC-013: Overlapping rental order for the same serial is rejected with HTTP 409.
        $this->actingAs($rentalStaff, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $customer->id,
            'start_date' => '2026-11-15',
            'expected_return_date' => '2026-11-20',
            'product_id' => $mlk->id,
            'product_serial_id' => $serial1->id,
            'source_location_id' => $rackC1->id,
            'expected_contents' => [['product_id' => $comp16->id, 'expected_qty' => '4.0000']],
        ])->assertStatus(409)->assertJsonPath('code', 'RENTAL_SERIAL_OVERLAPPING_BOOKING');

        // Dispatch the reserved serial.
        $this->actingAs($rentalStaff, 'sanctum')->postJson(
            "/api/v1/rental-orders/{$order['id']}/dispatch",
            ['reservation_id' => $order['reservation_id']],
            [IdempotencyService::HEADER => 'rnt-disp-001'],
        )->assertOk();

        // Receive return: unit goes to QUARANTINE, not AVAILABLE (06_BUSINESS_RULES.md #56).
        $ret = $this->actingAs($rentalStaff, 'sanctum')->postJson(
            "/api/v1/rental-orders/{$order['id']}/returns",
            ['return_location_id' => $rackC1->id],
            [IdempotencyService::HEADER => 'rnt-ret-001'],
        )->assertCreated()->json('data');

        $this->assertSame('QUARANTINE', $serial1->fresh()?->current_status);

        // AC-014: Return QC finds only 3 of 4 sensors returned (1 missing @ 18500.00 reference price).
        $qc = $this->actingAs($rentalStaff, 'sanctum')->postJson(
            "/api/v1/rental-returns/{$ret['return_id']}/inspect",
            [
                'contents' => [
                    ['product_id' => $comp16->id, 'actual_qty' => '3.0000', 'damaged_qty' => '0.0000'],
                ],
            ],
            [IdempotencyService::HEADER => 'rnt-qc-001'],
        )->assertOk()->json('data');

        $this->assertFalse($qc['passed']);
        $this->assertSame('18500.00', $qc['total_estimated_claim_amount']);
        $this->assertDatabaseHas('quality_discrepancies', [
            'product_id' => $comp16->id,
            'discrepancy_type' => 'MISSING',
            'quantity' => '1.0000',
            'estimated_claim_amount' => '18500.00',
        ]);

        // Unit stays in QUARANTINE because QC found a missing component.
        $this->assertSame('QUARANTINE', $serial1->fresh()?->current_status);
    }

    public function test_production_mrp_material_issue_unused_return_stage_reconciliation_and_costing(): void
    {
        // Covers Gate C & Gate E (AC-009, AC-010, AC-011, AC-012 + zero-good-output safety).
        $pm = User::query()->where('email', 'production@erlass.test')->firstOrFail();
        $wm = User::query()->where('email', 'warehouse@erlass.test')->firstOrFail();
        $mlk = Product::query()->where('code', '1001')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();

        // 1. Create Production Plan for 40 MLKs and run MRP (40 MLKs need 160 × SP.PART.ER.016 + 25 safety − 120 available = 65 net shortage).
        $plan = $this->actingAs($pm, 'sanctum')->postJson('/api/v1/production-plans', [
            'title' => 'Rencana Produksi November',
            'plan_date' => '2026-11-09',
            'due_date' => '2026-11-30',
            'items' => [
                ['product_id' => $mlk->id, 'target_qty' => '40.0000', 'uom_id' => $mlk->base_uom_id],
            ],
        ])->assertCreated()->json('data');

        $mrp = $this->actingAs($pm, 'sanctum')
            ->postJson("/api/v1/production-plans/{$plan['id']}/mrp-runs")
            ->assertCreated()
            ->json('data');

        $mrp16 = collect($mrp['items'])->firstWhere('product_code', 'SP.PART.ER.016');
        $this->assertSame('160.0000', $mrp16['gross_requirement']);
        $this->assertSame('120.0000', $mrp16['available_qty']);
        $this->assertSame('25.0000', $mrp16['safety_stock_qty']);
        $this->assertSame('65.0000', $mrp16['net_requirement']);

        // 2. Release Production Order for 5 MLKs (order_no pattern: 1001-YYMMDD-01).
        $order = $this->actingAs($pm, 'sanctum')->postJson('/api/v1/production-orders/release', [
            'product_id' => $mlk->id,
            'target_qty' => '5.0000',
        ])->assertCreated()->json('data');

        $this->assertStringStartsWith('1001-', $order['order_no']);
        $this->assertCount(3, $order['work_orders']);
        $finalWoId = (int) collect($order['work_orders'])->last()['id'];

        // 3. Issue 22 × SP.PART.ER.016 @ 18500.00 (= 407,000.00).
        $this->actingAs($wm, 'sanctum')->postJson(
            "/api/v1/work-orders/{$finalWoId}/material-issues",
            [
                'items' => [
                    ['product_id' => $comp16->id, 'location_id' => $rackA1->id, 'quantity' => '22.0000', 'uom_id' => $comp16->base_uom_id],
                ],
            ],
            [IdempotencyService::HEADER => 'wo-issue-001'],
        )->assertCreated();

        // 4. Return 2 unused SP.PART.ER.016 → net consumed is 20 × 18,500 = 370,000.00 (AC-009).
        $issueItemId = (int) DB::table('material_issue_items')->value('id');
        $costAfterReturn = $this->actingAs($wm, 'sanctum')->postJson(
            "/api/v1/material-issue-items/{$issueItemId}/return-unused",
            ['quantity' => '2.0000'],
            [IdempotencyService::HEADER => 'wo-return-001'],
        )->assertOk()->json('data');

        $this->assertSame('370000.00', $costAfterReturn['total_material_cost']);
        // Zero good output so far → cost per good unit is safely null (11_TESTING_STRATEGY.md).
        $this->assertNull($costAfterReturn['material_cost_per_good_unit']);

        // 5. Unreconciled stage result (Input 5 != Good 4 + Reject 0) is rejected (03_BUSINESS_PROCESS.md §2).
        $this->actingAs($pm, 'sanctum')->postJson("/api/v1/work-orders/{$finalWoId}/results", [
            'input_qty' => '5.0000',
            'good_qty' => '4.0000',
        ])->assertStatus(422)->assertJsonPath('code', 'PRODUCTION_STAGE_QUANTITY_UNRECONCILED');

        // 6. Reconciled result: Input 5 = 4 Good + 1 Reject.
        // Material consumed on the rejected unit remains in total_material_cost (370,000.00),
        // so material_cost_per_good_unit = 370,000 / 4 = 92,500.000000 (AC-010, AC-011, AC-012).
        $summary = $this->actingAs($pm, 'sanctum')->postJson("/api/v1/work-orders/{$finalWoId}/results", [
            'input_qty' => '5.0000',
            'good_qty' => '4.0000',
            'reject_qty' => '1.0000',
        ])->assertCreated()->json('data');

        $this->assertSame('370000.00', $summary['total_material_cost']);
        $this->assertSame('4.0000', $summary['total_good_qty']);
        $this->assertSame('1.0000', $summary['total_reject_qty']);
        $this->assertSame('92500.000000', $summary['material_cost_per_good_unit']);
    }
}
