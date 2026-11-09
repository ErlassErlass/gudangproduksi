<?php

namespace Tests\Feature;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Services\ReservationService;
use App\Domains\MasterData\Models\Product;
use App\Domains\Production\Models\BomHeader;
use App\Domains\Production\Models\BomVersion;
use App\Domains\Production\Services\BomService;
use App\Domains\Warehouse\Models\Location;
use App\Models\User;
use App\Shared\Exceptions\DomainException;
use App\Shared\Idempotency\IdempotencyService;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies Gate A (Inventory Ledger Correctness), Master Data classification rules, and BOM
 * explosion/cycle/snapshot invariants (`agent_docs/11_TESTING_STRATEGY.md`).
 */
final class InventoryEngineAndBomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_multilevel_mlk_bom_explosion_and_snapshot_immutability(): void
    {
        $bomService = app(BomService::class);
        $mlk = Product::query()->where('code', '1001')->firstOrFail();
        $activeVersion = $bomService->findActiveVersionForProduct($mlk->id);
        $this->assertNotNull($activeVersion);

        // 1 MLK = 1 B01 = 2 M01 = (4 × SP.PART.ER.016) + (2 × SP.PART.ER.017) + (2 × 1.05 = 2.1000 × SP.PART.ER.018).
        $exploded = collect($bomService->explode($activeVersion, '5.0000'))->keyBy('product_code');

        $this->assertSame('20.0000', $exploded['SP.PART.ER.016']['gross_qty']);
        $this->assertSame('10.0000', $exploded['SP.PART.ER.017']['gross_qty']);
        $this->assertSame('10.5000', $exploded['SP.PART.ER.018']['gross_qty']);

        // Snapshot taken at order release must remain intact when a new BOM version is created (AC-003).
        $snapshot = $bomService->buildOrderSnapshot($activeVersion, '5.0000');
        $this->assertCount(3, $snapshot['stages']);
        $this->assertSame(1, $snapshot['version_no']);

        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $header = BomHeader::query()->where('code', 'BOM-M01')->firstOrFail();

        $v2 = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/boms/{$header->id}/versions", [
            'items' => [
                ['component_product_id' => $comp16->id, 'quantity' => '99.0000', 'uom_id' => $comp16->base_uom_id],
            ],
        ])->assertCreated()->json('data');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/bom-versions/{$v2['id']}/activate")
            ->assertOk();

        // Previously captured snapshot still shows 20.0000 for 5 MLKs.
        $snap16 = collect($snapshot['requirements'])->firstWhere('product_code', 'SP.PART.ER.016');
        $this->assertSame('20.0000', $snap16['gross_qty']);
    }

    public function test_bom_cycle_is_rejected(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $mod01 = Product::query()->where('code', 'M01')->firstOrFail();
        $box01 = Product::query()->where('code', 'B01')->firstOrFail();
        $modHeader = BomHeader::query()->where('code', 'BOM-M01')->firstOrFail();

        // B01 already depends on M01; making M01 depend on B01 creates a cycle M01 → B01 → M01.
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/boms/{$modHeader->id}/versions", [
            'items' => [
                [
                    'component_product_id' => $box01->id,
                    'item_type' => 'SUBASSEMBLY',
                    'quantity' => '1.0000',
                    'uom_id' => $mod01->base_uom_id,
                ],
            ],
        ])->assertStatus(422)->assertJsonPath('code', 'BOM_CYCLE_DETECTED');
    }

    public function test_program_or_service_cannot_be_posted_as_physical_stock(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $program = Product::query()->where('code', 'PROG-2027-ROBOTIK')->firstOrFail();
        $loc = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/inventory/opening-balances', [
            'destination_location_id' => $loc->id,
            'items' => [
                ['product_id' => $program->id, 'quantity' => '1.0000', 'unit_cost' => '100000'],
            ],
        ], [IdempotencyService::HEADER => 'prog-stock-attempt'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVENTORY_NON_STOCK_PRODUCT');
    }

    public function test_transfer_reservation_insufficient_stock_and_reversal_invariants(): void
    {
        $wm = User::query()->where('email', 'warehouse@erlass.test')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();
        $rackC1 = Location::query()->where('code', 'POP.L3R3C1.M01')->firstOrFail();

        // Opening seeded balance on POP.L3R3A1 is 120.0000. Reserve 30.0000 → available becomes 90.0000.
        $reservations = app(ReservationService::class);
        $reservation = $reservations->reserve('work_order', 1, $comp16->id, $rackA1->id, '30.0000', userId: $wm->id);

        $balanceA1 = InventoryBalance::query()->where('product_id', $comp16->id)->where('location_id', $rackA1->id)->firstOrFail();
        $this->assertSame('120.0000', $balanceA1->on_hand_qty);
        $this->assertSame('30.0000', $balanceA1->reserved_qty);
        $this->assertSame('90.0000', $balanceA1->availableQty());

        // Attempting to transfer 100.0000 (> 90.0000 available) must fail without partial writes.
        $this->actingAs($wm, 'sanctum')->postJson('/api/v1/inventory/transfers', [
            'source_location_id' => $rackA1->id,
            'destination_location_id' => $rackC1->id,
            'items' => [['product_id' => $comp16->id, 'quantity' => '100.0000']],
        ], [IdempotencyService::HEADER => 'trf-overdraw'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INVENTORY_INSUFFICIENT_STOCK');

        // Transferring 25.0000 succeeds and is idempotent on retry (AC-015).
        $trfResponse = $this->actingAs($wm, 'sanctum')->postJson('/api/v1/inventory/transfers', [
            'source_location_id' => $rackA1->id,
            'destination_location_id' => $rackC1->id,
            'items' => [['product_id' => $comp16->id, 'quantity' => '25.0000']],
            'notes' => 'Relokasi ke area perakitan',
        ], [IdempotencyService::HEADER => 'trf-valid-001'])->assertCreated();

        $txId = (int) $trfResponse->json('data.id');

        // Retry with the exact same Idempotency-Key does not double-deduct stock.
        $this->actingAs($wm, 'sanctum')->postJson('/api/v1/inventory/transfers', [
            'source_location_id' => $rackA1->id,
            'destination_location_id' => $rackC1->id,
            'items' => [['product_id' => $comp16->id, 'quantity' => '25.0000']],
            'notes' => 'Relokasi ke area perakitan',
        ], [IdempotencyService::HEADER => 'trf-valid-001'])->assertCreated();

        $this->assertSame('95.0000', $balanceA1->fresh()?->on_hand_qty);

        // Reversing the posted transfer restores 120.0000 on A1 and 0.0000 on C1 and links to original.
        $rev = $this->actingAs($wm, 'sanctum')->postJson(
            "/api/v1/inventory/transactions/{$txId}/reverse",
            ['reason' => 'Salah pilih rak tujuan'],
            [IdempotencyService::HEADER => 'rev-001'],
        )->assertCreated();

        $this->assertSame($txId, $rev->json('data.reversed_transaction_id'));
        $this->assertSame('120.0000', $balanceA1->fresh()?->on_hand_qty);

        // Release reservation restores full 120.0000 available.
        $reservations->release($reservation->id);
        $this->assertSame('120.0000', $balanceA1->fresh()?->availableQty());
    }

    public function test_quarantine_and_at_vendor_stock_are_not_available_for_picking(): void
    {
        $wm = User::query()->where('email', 'warehouse@erlass.test')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $gapLoc = Location::query()->where('code', 'GAP.EXT.01')->firstOrFail();

        /** @var InventoryPostingContract $inventory */
        $inventory = app(InventoryPostingContract::class);
        $inventory->post(new PostInventoryCommand(
            idempotencyKey: 'vendor-hold-001',
            transactionType: 'VENDOR_DISPATCH',
            sourceDocumentType: 'vendor_orders',
            sourceDocumentId: 1,
            postedByUserId: $wm->id,
            lines: [
                new StockMovementLine(
                    productId: $comp16->id,
                    quantity: '50.0000',
                    toLocationId: $gapLoc->id,
                    stockStatusTo: StockStatus::AT_VENDOR->value,
                    unitCost: '18500.000000',
                ),
            ],
        ));

        $atVendorBalance = InventoryBalance::query()
            ->where('product_id', $comp16->id)
            ->where('location_id', $gapLoc->id)
            ->where('stock_status', StockStatus::AT_VENDOR->value)
            ->firstOrFail();

        $this->assertSame('50.0000', $atVendorBalance->on_hand_qty);
        // Invariant #7 (06_BUSINESS_RULES.md): AT_VENDOR stock has 0 available for normal picking.
        $this->assertSame('0.0000', $atVendorBalance->availableQty());
    }

    public function test_stock_adjustment_enforces_separation_of_duties(): void
    {
        $staff = User::factory()->withRole('warehouse_staff')->create();
        $manager = User::query()->where('email', 'warehouse@erlass.test')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();

        $adj = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/inventory/adjustments', [
            'location_id' => $rackA1->id,
            'reason' => 'Temuan selisih fisik opname',
            'items' => [
                ['product_id' => $comp16->id, 'direction' => 'IN', 'quantity' => '5.0000', 'unit_cost' => '18500'],
            ],
        ])->assertCreated()->json('data');

        // Creator cannot approve their own adjustment (09_RBAC_MATRIX.md "Separation of duties").
        $this->actingAs($manager, 'sanctum')->postJson(
            "/api/v1/inventory/adjustments/{$adj['id']}/approve",
            [],
            [IdempotencyService::HEADER => 'adj-self-approve'],
        )->assertStatus(409)->assertJsonPath('code', 'ADJUSTMENT_SELF_APPROVAL_FORBIDDEN');

        // Warehouse staff without `inventory.adjustment.approve` is forbidden (403).
        $this->actingAs($staff, 'sanctum')->postJson(
            "/api/v1/inventory/adjustments/{$adj['id']}/approve",
            [],
            [IdempotencyService::HEADER => 'adj-staff-approve'],
        )->assertStatus(403);

        // Super Admin (independent approver) approves and posts to the ledger (AC-016).
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $this->actingAs($admin, 'sanctum')->postJson(
            "/api/v1/inventory/adjustments/{$adj['id']}/approve",
            [],
            [IdempotencyService::HEADER => 'adj-admin-approve'],
        )->assertOk()->assertJsonPath('data.status', 'POSTED');

        $balanceA1 = InventoryBalance::query()->where('product_id', $comp16->id)->where('location_id', $rackA1->id)->firstOrFail();
        $this->assertSame('125.0000', $balanceA1->on_hand_qty);
    }

    public function test_program_management_with_finished_good_and_service_bundle(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $mlk = Product::query()->where('code', '1001')->firstOrFail();
        $srv = Product::query()->where('code', 'SRV-TRAIN-01')->firstOrFail();
        $prog = Product::query()->where('code', 'PROG-2027-ROBOTIK')->firstOrFail();

        // Check program details contain seeded components (FG + Service)
        $res = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/products/{$prog->id}")->assertOk();
        $components = $res->json('data.components');
        $this->assertCount(2, $components);

        // Sync updated composition (change quantities and add new line)
        $syncRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/products/{$prog->id}/components", [
            'components' => [
                ['component_product_id' => $mlk->id, 'quantity' => '2.0000', 'uom_id' => $mlk->base_uom_id, 'component_role' => 'FINISHED_GOOD'],
                ['component_product_id' => $srv->id, 'quantity' => '3.0000', 'uom_id' => $srv->base_uom_id, 'component_role' => 'SERVICE'],
            ],
        ])->assertOk();

        $updatedComponents = $syncRes->json('data.components');
        $this->assertCount(2, $updatedComponents);
        $this->assertSame('2.0000', $updatedComponents[0]['quantity']);
        $this->assertSame('3.0000', $updatedComponents[1]['quantity']);
    }

    public function test_bom_by_product_and_quick_save_allows_anytime_stage_updates(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $mod01 = Product::query()->where('code', 'M01')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $comp17 = Product::query()->where('code', 'SP.PART.ER.017')->firstOrFail();

        // Query BOM by product
        $byProdRes = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/boms/by-product/{$mod01->id}")->assertOk();
        $this->assertNotNull($byProdRes->json('data.output_bom'));

        // Quick save modifies BOM items and activates immediately
        $saveRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/boms/quick-save', [
            'product_id' => $mod01->id,
            'items' => [
                ['component_product_id' => $comp16->id, 'quantity' => '3.0000', 'uom_id' => $comp16->base_uom_id, 'scrap_rate' => 0.02],
                ['component_product_id' => $comp17->id, 'quantity' => '1.0000', 'uom_id' => $comp17->base_uom_id, 'scrap_rate' => 0],
            ],
        ])->assertOk();

        $activeVersion = $saveRes->json('data.active_version');
        $this->assertNotNull($activeVersion);
        $this->assertCount(2, $activeVersion['items']);
        $this->assertSame('3.0000', $activeVersion['items'][0]['quantity']);
    }

    public function test_production_plan_with_manual_multi_product_quantities(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $mlk = Product::query()->where('code', '1001')->firstOrFail();
        $mod01 = Product::query()->where('code', 'M01')->firstOrFail();

        $planRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/production-plans', [
            'title' => 'Rencana Produksi Manual Qty',
            'plan_date' => now()->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'priority' => 'HIGH',
            'items' => [
                ['product_id' => $mlk->id, 'target_qty' => '75.0000', 'uom_id' => $mlk->base_uom_id],
                ['product_id' => $mod01->id, 'target_qty' => '150.0000', 'uom_id' => $mod01->base_uom_id],
            ],
        ])->assertCreated();

        $planId = $planRes->json('data.id');
        $this->assertNotNull($planId);

        $plansList = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/production-plans')->assertOk();
        $createdPlan = collect($plansList->json('data'))->firstWhere('id', $planId);
        $this->assertNotNull($createdPlan);
        $this->assertCount(2, $createdPlan['items']);
        $this->assertEquals(75.0, (float) $createdPlan['items'][0]['target_qty']);
        $this->assertEquals(150.0, (float) $createdPlan['items'][1]['target_qty']);
    }

    public function test_bom_assemble_stage_consumes_previous_stage_and_produces_current_stage_stock(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $mod01 = Product::query()->where('code', 'M01')->firstOrFail();
        $comp16 = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $rackA1 = Location::query()->where('code', 'POP.L3R3A1')->firstOrFail();
        $rackC1 = Location::query()->where('code', 'POP.L3R3C1.M01')->firstOrFail();

        // Initial balance: comp16 at rackA1 is 120.0000, mod01 at rackC1 is 0.0000
        $initComp = InventoryBalance::query()->where('product_id', $comp16->id)->where('location_id', $rackA1->id)->firstOrFail();
        $this->assertSame('120.0000', $initComp->on_hand_qty);

        // Assemble 5 units of Modul M01: BOM requires 2 * 5 = 10 units of comp16
        $res = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/boms/assemble-stage', [
            'product_id' => $mod01->id,
            'quantity' => '5.0000',
            'source_location_id' => $rackA1->id,
            'destination_location_id' => $rackC1->id,
            'notes' => 'Perakitan 5 unit Modul M01 untuk stok tahap ini',
        ])->assertOk();

        $this->assertNotNull($res->json('data.transaction_id'));

        // Verify comp16 reduced by 10 (120 - 10 = 110)
        $afterComp = InventoryBalance::query()->where('product_id', $comp16->id)->where('location_id', $rackA1->id)->firstOrFail();
        $this->assertSame('110.0000', $afterComp->on_hand_qty);

        // Verify mod01 increased by 5 at rackC1
        $afterMod = InventoryBalance::query()->where('product_id', $mod01->id)->where('location_id', $rackC1->id)->firstOrFail();
        $this->assertSame('5.0000', $afterMod->on_hand_qty);
    }

    public function test_document_templates_and_location_hierarchy_crud(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();

        // Document templates seeded
        $tplRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/document-templates')->assertOk();
        $this->assertNotEmpty($tplRes->json('data'));

        // Location hierarchy
        $locRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/locations')->assertOk();
        $types = collect($locRes->json('data'))->pluck('location_type')->unique()->values()->all();
        $this->assertContains('FLOOR', $types);
        $this->assertContains('ROOM', $types);
        $this->assertContains('RACK', $types);
    }

    public function test_stock_card_date_filtering_and_production_plan_stages_and_barcode(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $fg = Product::query()->where('code', '1001')->firstOrFail();

        // 1. Test generate barcode for finished good
        $bcRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/products/{$fg->id}/generate-barcode")->assertOk();
        $this->assertNotNull($bcRes->json('data.barcode'));
        $this->assertSame('CODE128', $bcRes->json('data.barcode_type'));

        // 2. Test stock-card with date filter
        $scRes = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/inventory/stock-card?product_id={$fg->id}&from_date=" . now()->subDays(7)->toDateString() . '&to_date=' . now()->toDateString())->assertOk();
        $this->assertNotNull($scRes->json('data.product'));
        $this->assertArrayHasKey('period_opening_balance', $scRes->json('data'));
        $this->assertArrayHasKey('total_in', $scRes->json('data'));
        $this->assertArrayHasKey('total_out', $scRes->json('data'));

        // 3. Test production plan stages: create, update to completed, and assert delete is prevented
        $planRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/production-plans', [
            'title' => 'Rencana Perakitan Batch 2026',
            'plan_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'priority' => 'HIGH',
            'items' => [
                [
                    'product_id' => $fg->id,
                    'target_qty' => '10.0000',
                    'uom_id' => $fg->base_uom_id,
                ],
            ],
        ])->assertCreated();
        $planId = $planRes->json('data.id');

        // Add stage 1 (40%)
        $s1 = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/production-plans/{$planId}/stages", [
            'name' => 'Pemotongan & Solder Bahan',
            'weight_percentage' => '40.00',
        ])->assertCreated();
        $s1Id = $s1->json('data.id');

        // Add stage 2 (60%)
        $s2 = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/production-plans/{$planId}/stages", [
            'name' => 'Integrasi Box & Final Testing',
            'weight_percentage' => '60.00',
        ])->assertCreated();
        $s2Id = $s2->json('data.id');

        // Adding another stage exceeding 100% must be rejected
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/production-plans/{$planId}/stages", [
            'name' => 'Tahap Berlebih',
            'weight_percentage' => '15.00',
        ])->assertStatus(422);

        // Update stage 1 to COMPLETED
        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/production-plan-stages/{$s1Id}", [
            'status' => 'COMPLETED',
        ])->assertOk();

        // Delete stage 1 (COMPLETED) must be rejected!
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/production-plan-stages/{$s1Id}")
            ->assertStatus(422);

        // Delete stage 2 (PENDING) must succeed
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/production-plan-stages/{$s2Id}")
            ->assertOk();
    }

    public function test_barcode_print_deducts_stock_and_reprint_preserves_stock_and_barcode_scan(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $location = Location::query()->where('is_active', true)->firstOrFail();

        $fg = Product::query()->create([
            'code' => 'FG-TEST-BARCODE',
            'name' => 'Produk Jadi Test Barcode',
            'product_type' => 'PRODUCT',
            'physical_type' => 'PHYSICAL',
            'fulfillment_type' => 'SELL',
            'tracking_method' => 'QUANTITY',
            'base_uom_id' => 1,
            'safety_stock_qty' => '5.0000',
            'is_sellable' => true,
        ]);

        // 1. Initial stock receive for FG: 20 units
        $this->actingAs($admin, 'sanctum')->withHeader('Idempotency-Key', 'SEED-FG-' . uniqid())->postJson('/api/v1/inventory/opening-balances', [
            'destination_location_id' => $location->id,
            'items' => [
                [
                    'product_id' => $fg->id,
                    'quantity' => '20.0000',
                    'unit_cost' => '1500000',
                ],
            ],
        ])->assertCreated();

        // Check stock is 20
        $balanceBefore = InventoryBalance::query()
            ->where('product_id', $fg->id)
            ->where('location_id', $location->id)
            ->where('stock_status', 'AVAILABLE')
            ->first();
        $this->assertEquals('20.0000', $balanceBefore->on_hand_qty);

        // 2. Print 10 barcode stickers for FG -> Must deduct 10 units
        $printRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/products/{$fg->id}/print-barcodes", [
            'quantity' => 10,
            'location_id' => $location->id,
            'notes' => 'Cetak 10 barcode batch pertama',
        ])->assertCreated();

        $batchId = $printRes->json('data.id');
        $barcode = $printRes->json('data.barcode');
        $this->assertNotEmpty($barcode);

        // Check stock is now 10
        $balanceAfter = InventoryBalance::query()
            ->where('product_id', $fg->id)
            ->where('location_id', $location->id)
            ->where('stock_status', 'AVAILABLE')
            ->first();
        $this->assertEquals('10.0000', $balanceAfter->on_hand_qty);

        // 3. Printing more than available stock (e.g. 15) must be rejected
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/products/{$fg->id}/print-barcodes", [
            'quantity' => 15,
            'location_id' => $location->id,
        ])->assertStatus(422);

        // 4. Reprint barcode batch -> Must NOT deduct stock, but increment reprint_count
        $reprintRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/barcode-prints/{$batchId}/reprint")
            ->assertOk();
        $this->assertEquals(1, $reprintRes->json('data.reprint_count'));

        // Stock must remain 10
        $balanceAfterReprint = InventoryBalance::query()
            ->where('product_id', $fg->id)
            ->where('location_id', $location->id)
            ->where('stock_status', 'AVAILABLE')
            ->first();
        $this->assertEquals('10.0000', $balanceAfterReprint->on_hand_qty);

        // 5. Check print history endpoint
        $histRes = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/products/{$fg->id}/barcode-prints")
            ->assertOk();
        $this->assertEquals(1, $histRes->json('summary.total_batches'));
        $this->assertEquals(10, $histRes->json('summary.total_quantity'));
        $this->assertNotNull($histRes->json('summary.last_printed_at'));
        $this->assertNotNull($histRes->json('summary.last_reprinted_at'));

        // 6. Scan barcode endpoint
        $scanRes = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/products/scan/{$barcode}")
            ->assertOk();
        $this->assertEquals($fg->id, $scanRes->json('data.id'));
        $this->assertEquals($fg->code, $scanRes->json('data.code'));
        $this->assertEquals('10.0000', $scanRes->json('data.available_qty'));
    }

    public function test_customer_sales_assignment_and_sales_loan_return_flow(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $fg = Product::query()->where('code', '1001')->firstOrFail();

        // 1. Create Customer with sales_name
        $custRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/customers', [
            'code' => 'CUST-TEST-01',
            'name' => 'SMA Test Jakarta',
            'sales_name' => 'Andi Wijaya',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMA Test Jakarta Pusat',
        ])->assertStatus(201);
        $this->assertEquals('Andi Wijaya', $custRes->json('data.sales_name'));
        $custId = $custRes->json('data.id');

        // 2. Fetch marketing team
        $teamRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/marketing-team')->assertOk();
        $this->assertNotEmpty($teamRes->json('data'));

        // 3. Create Sales Loan
        $loanRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/sales-loans', [
            'group_leader' => 'Budi Santoso',
            'sales_name' => 'Andi Wijaya',
            'customer_id' => $custId,
            'product_id' => $fg->id,
            'quantity' => 2,
            'loan_date' => '2026-11-09',
            'notes' => 'Unit demo untuk presentasi kepala sekolah',
        ])->assertStatus(201);
        $loanId = $loanRes->json('data.id');
        $this->assertEquals('DIPINJAM', $loanRes->json('data.status'));
        $this->assertNull($loanRes->json('data.return_date'));
        $this->assertNull($loanRes->json('data.receipt_no'));

        // 4. Return Sales Loan
        $returnRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/sales-loans/{$loanId}/return", [
            'return_date' => '2026-11-12',
            'return_notes' => 'Unit kembali lengkap dan berfungsi dengan baik',
        ])->assertOk();
        $this->assertEquals('DIKEMBALIKAN', $returnRes->json('data.status'));
        $this->assertEquals('2026-11-12', $returnRes->json('data.return_date'));
        $this->assertNotNull($returnRes->json('data.receipt_no'));
        $this->assertStringStartsWith('BTT-LOAN-', $returnRes->json('data.receipt_no'));

        // 5. Check listing includes customer and product names
        $listRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/sales-loans')->assertOk();
        $found = collect($listRes->json('data'))->firstWhere('id', $loanId);
        $this->assertNotNull($found);
        $this->assertEquals('SMA Test Jakarta', $found['customer_name']);
        $this->assertEquals('Budi Santoso', $found['group_leader']);
        $this->assertEquals('Andi Wijaya', $found['sales_name']);
        $this->assertEquals('DIKEMBALIKAN', $found['status']);
    }

    public function test_school_rental_sequential_barcode_generation_and_continuation(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $fg = Product::query()->where('code', '1001')->firstOrFail();
        $uom = \App\Domains\MasterData\Models\Uom::query()->where('code', 'PCS')->firstOrFail();

        $cust1 = \App\Domains\MasterData\Models\Customer::query()->create(['code' => 'SCH-01', 'name' => 'SMAN 1 Bandung', 'customer_type' => 'SCHOOL']);
        $cust2 = \App\Domains\MasterData\Models\Customer::query()->create(['code' => 'SCH-02', 'name' => 'SMPN 2 Bandung', 'customer_type' => 'SCHOOL']);

        // 1. Create completed production plan 1 with 50 MLK
        $plan1Id = \Illuminate\Support\Facades\DB::table('production_plans')->insertGetId([
            'plan_no' => 'PP-TEST-50',
            'title' => 'Batch 50 MLK Sekolah 1',
            'plan_date' => '2026-11-09',
            'due_date' => '2026-11-20',
            'status' => 'COMPLETED',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('production_plan_items')->insert([
            'production_plan_id' => $plan1Id,
            'product_id' => $fg->id,
            'target_qty' => 50,
            'uom_id' => $uom->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Order sewa sekolah 1 (50 units)
        $order1Res = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $cust1->id,
            'production_plan_id' => $plan1Id,
            'start_date' => '2026-11-10',
            'notes' => 'Sewa 50 MLK semester ganjil',
        ])->assertStatus(201);

        $this->assertEquals(1, $order1Res->json('data.start_unit_no'));
        $this->assertEquals(50, $order1Res->json('data.end_unit_no'));
        $this->assertEquals(50, $order1Res->json('data.total_units'));
        $this->assertEquals('1001_01', $order1Res->json('data.unit_codes.0'));
        $this->assertEquals('1001_50', $order1Res->json('data.unit_codes.49'));

        // 3. Create completed production plan 2 with 10 MLK
        $plan2Id = \Illuminate\Support\Facades\DB::table('production_plans')->insertGetId([
            'plan_no' => 'PP-TEST-10',
            'title' => 'Batch 10 MLK Sekolah 2',
            'plan_date' => '2026-11-09',
            'due_date' => '2026-11-20',
            'status' => 'COMPLETED',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('production_plan_items')->insert([
            'production_plan_id' => $plan2Id,
            'product_id' => $fg->id,
            'target_qty' => 10,
            'uom_id' => $uom->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Order sewa sekolah 2 (10 units) -> Must continue from 1001_51 to 1001_60
        $order2Res = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $cust2->id,
            'production_plan_id' => $plan2Id,
            'start_date' => '2026-11-12',
            'notes' => 'Sewa 10 MLK lanjutan',
        ])->assertStatus(201);

        $this->assertEquals(51, $order2Res->json('data.start_unit_no'));
        $this->assertEquals(60, $order2Res->json('data.end_unit_no'));
        $this->assertEquals(10, $order2Res->json('data.total_units'));
        $this->assertEquals('1001_51', $order2Res->json('data.unit_codes.0'));
        $this->assertEquals('1001_60', $order2Res->json('data.unit_codes.9'));
    }

    public function test_purchase_order_with_custom_text_supplier_phone_and_status_update(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $comp = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();

        // 1. Create Purchase Order with text supplier and whatsapp
        $poRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/purchase-orders', [
            'supplier_name' => 'PT Mitra Komponen Prima',
            'supplier_phone' => '081234567890',
            'status' => 'DIPESAN',
            'order_date' => '2026-11-10',
            'items' => [
                [
                    'product_id' => $comp->id,
                    'ordered_qty' => '25.0000',
                    'unit_price' => '20000.00',
                    'uom_id' => $comp->base_uom_id,
                ],
            ],
        ])->assertStatus(201);

        $poId = $poRes->json('data.id');
        $this->assertEquals('PT Mitra Komponen Prima', $poRes->json('data.supplier_name'));
        $this->assertEquals('081234567890', $poRes->json('data.supplier_phone'));
        $this->assertEquals('DIPESAN', $poRes->json('data.status'));

        // 2. Fetch list PO
        $listRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/purchase-orders')->assertOk();
        $found = collect($listRes->json('data'))->firstWhere('id', $poId);
        $this->assertNotNull($found);
        $this->assertEquals('PT Mitra Komponen Prima', $found['supplier_name']);
        $this->assertEquals('081234567890', $found['supplier_phone']);

        // 3. Update status to DIKIRIM and SELESAI DITERIMA
        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/purchase-orders/{$poId}/status", [
            'status' => 'DIKIRIM',
        ])->assertOk();

        $statusUpdated = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/purchase-orders/{$poId}/status", [
            'status' => 'SELESAI DITERIMA',
        ])->assertOk();

        $this->assertEquals('SELESAI DITERIMA', $statusUpdated->json('data.status'));
    }

    public function test_generate_rental_delivery_order_and_distribution_sync(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $fg = Product::query()->where('code', '1001')->firstOrFail();
        $uom = \App\Domains\MasterData\Models\Uom::query()->where('code', 'PCS')->firstOrFail();

        $cust = \App\Domains\MasterData\Models\Customer::query()->create([
            'code' => 'CUST-DO-01',
            'name' => 'SMP Bintang Bangsa',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMP Bintang Bangsa Official',
            'phone' => '021-88889999',
        ]);

        $planId = \Illuminate\Support\Facades\DB::table('production_plans')->insertGetId([
            'plan_no' => 'PP-DO-TEST',
            'title' => 'Batch DO 20 MLK',
            'plan_date' => '2026-11-09',
            'due_date' => '2026-11-20',
            'status' => 'COMPLETED',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('production_plan_items')->insert([
            'production_plan_id' => $planId,
            'product_id' => $fg->id,
            'target_qty' => 20,
            'uom_id' => $uom->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Create school rental order
        $orderRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $cust->id,
            'production_plan_id' => $planId,
            'start_date' => '2026-11-10',
        ])->assertStatus(201);
        $orderId = $orderRes->json('data.id');

        // 2. Generate Delivery Order (DO)
        $doRes = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/rental-orders/{$orderId}/generate-do")
            ->assertOk();

        $shipment = $doRes->json('data.shipment');
        $this->assertNotNull($shipment);
        $this->assertStringStartsWith('DO-', $shipment['shipment_no']);
        $this->assertEquals('RENTAL', $shipment['shipment_type']);
        $this->assertEquals('SMP Bintang Bangsa Official', $shipment['recipient_name']);
        $this->assertEquals('IN_TRANSIT', $shipment['status']);

        // 3. Check /shipments endpoint (Distribusi) -> Must include this shipment
        $shipmentsRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/shipments')->assertOk();
        $foundShipment = collect($shipmentsRes->json('data'))->firstWhere('id', $shipment['id']);
        $this->assertNotNull($foundShipment);
        $this->assertEquals('SMP Bintang Bangsa Official', $foundShipment['recipient_name']);
        $this->assertNotEmpty($foundShipment['items']);

        // 4. Calling generate-do again returns existing shipment (no duplicate DO)
        $doRes2 = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/rental-orders/{$orderId}/generate-do")
            ->assertOk();
        $this->assertEquals($shipment['id'], $doRes2->json('data.shipment.id'));

        // 5. Update shipment status to DELIVERED (Selesai)
        $updateRes = $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/shipments/{$shipment['id']}/status", [
            'status' => 'DELIVERED',
        ])->assertOk();

        $this->assertEquals('DELIVERED', $updateRes->json('data.status'));
        $this->assertNotNull($updateRes->json('data.delivered_at'));
    }

    public function test_school_rental_unit_reuse_and_tracking_and_completeness_history(): void
    {
        $admin = User::query()->where('email', 'admin@erlass.test')->firstOrFail();
        $fg = Product::query()->where('code', '1001')->firstOrFail();
        $uom = \App\Domains\MasterData\Models\Uom::query()->where('code', 'PCS')->firstOrFail();

        $custA = \App\Domains\MasterData\Models\Customer::query()->create([
            'code' => 'SCH-A',
            'name' => 'SMA Negeri 1 Mentari',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMA Negeri 1 Mentari',
        ]);
        $custB = \App\Domains\MasterData\Models\Customer::query()->create([
            'code' => 'SCH-B',
            'name' => 'SMA Negeri 2 Harapan',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMA Negeri 2 Harapan',
        ]);

        $planId = \Illuminate\Support\Facades\DB::table('production_plans')->insertGetId([
            'plan_no' => 'PP-REUSE-01',
            'title' => 'Batch 5 MLK Reusable',
            'plan_date' => '2026-11-10',
            'due_date' => '2026-11-20',
            'status' => 'COMPLETED',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\DB::table('production_plan_items')->insert([
            'production_plan_id' => $planId,
            'product_id' => $fg->id,
            'target_qty' => 5,
            'uom_id' => $uom->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Order sewa sekolah A (5 unit, mulai nomor 101 s/d 105)
        $orderA = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $custA->id,
            'production_plan_id' => $planId,
            'start_date' => '2026-11-10',
            'start_unit_no' => 101,
        ])->assertStatus(201)->json('data');

        $this->assertEquals(101, $orderA['start_unit_no']);
        $this->assertEquals(105, $orderA['end_unit_no']);
        $this->assertEquals('1001_101', $orderA['unit_codes'][0]);

        // 2. Periksa tracking: unit 1001_101 saat ini sedang dipakai di SMA Negeri 1 Mentari
        $track1 = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/rental-orders/units-tracking')->assertOk()->json('data.units');
        $u101 = collect($track1)->firstWhere('unit_code', '1001_101');
        $this->assertNotNull($u101);
        $this->assertEquals('IN_USE', $u101['current_status']);
        $this->assertEquals('SMA Negeri 1 Mentari', $u101['current_school']);

        // 3. Sekolah A selesai sewa -> dilakukan QC pengembalian (ada riwayat kelengkapan)
        $comp = Product::query()->where('code', 'SP.PART.ER.016')->firstOrFail();
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/quality-inspections', [
            'inspection_type' => 'RENTAL_RETURN_SCHOOL',
            'product_id' => $fg->id,
            'customer_name' => 'SMA Negeri 1 Mentari',
            'batch_no' => $orderA['rental_no'],
            'unit_count' => 5,
            'items' => [
                [
                    'product_id' => $comp->id,
                    'tier' => 'COMPONENT',
                    'expected_qty' => 5,
                    'actual_qty' => 4, // 1 hilang
                    'unit_price' => 20000,
                    'is_chargeable' => true,
                ],
            ],
        ])->assertCreated();

        // 4. Setelah QC selesai, rental order A otomatis menjadi RETURNED
        $orderAUpdated = \Illuminate\Support\Facades\DB::table('rental_orders')->where('id', $orderA['id'])->first();
        $this->assertEquals('RETURNED', $orderAUpdated->status);

        // 5. Sewa sekolah B menggunakan ulang nomor unit 101 s/d 105 yang sama
        $orderB = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/rental-orders', [
            'customer_id' => $custB->id,
            'production_plan_id' => $planId,
            'start_date' => '2026-11-15',
            'start_unit_no' => 101, // PAKAI ULANG NOMOR UNIT!
        ])->assertStatus(201)->json('data');

        $this->assertEquals(101, $orderB['start_unit_no']);
        $this->assertEquals('1001_101', $orderB['unit_codes'][0]);

        // 6. Cek tracking unit 1001_101 sekarang: dipakai di SMA Negeri 2 Harapan, dan punya riwayat kelengkapan dari Sekolah A!
        $track2 = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/rental-orders/units-tracking')->assertOk()->json('data');
        $u101After = collect($track2['units'])->firstWhere('unit_code', '1001_101');
        $this->assertEquals('IN_USE', $u101After['current_status']);
        $this->assertEquals('SMA Negeri 2 Harapan', $u101After['current_school']);
        $this->assertCount(2, $u101After['rental_history']);
        $this->assertNotEmpty($u101After['inspection_history']);
        $this->assertEquals('SMA Negeri 1 Mentari', $u101After['inspection_history'][0]['customer_name']);
        $this->assertEquals('CONDITIONAL', $u101After['inspection_history'][0]['status']);

        // 7. Test endpoint scan barcode unit 1001_101
        $scanUnitRes = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/rental-orders/units/scan/1001_101')
            ->assertOk()->json('data');
        $this->assertEquals('1001_101', $scanUnitRes['unit_code']);
        $this->assertEquals('SMA Negeri 2 Harapan', $scanUnitRes['current_school']);
        $this->assertNotEmpty($scanUnitRes['inspection_history']);

        // 8. Test update status dan sekolah pemakai langsung dari hasil scan
        $custC = \App\Domains\MasterData\Models\Customer::query()->create([
            'code' => 'SCH-C',
            'name' => 'SMK Bina Nusantara',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMK Bina Nusantara',
        ]);

        $updateAssignRes = $this->actingAs($admin, 'sanctum')->patchJson('/api/v1/rental-orders/units/update-assignment', [
            'rental_id' => $orderB['id'],
            'customer_id' => $custC->id,
            'status' => 'ACTIVE',
        ])->assertOk()->json('data');

        $this->assertEquals($custC->id, $updateAssignRes['customer_id']);

        // Selesai sewa
        $updateStatusDone = $this->actingAs($admin, 'sanctum')->patchJson('/api/v1/rental-orders/units/update-assignment', [
            'rental_id' => $orderB['id'],
            'status' => 'RETURNED',
        ])->assertOk()->json('data');
        $this->assertEquals('RETURNED', $updateStatusDone['status']);
    }
}
