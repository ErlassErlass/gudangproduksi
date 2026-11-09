<?php

namespace App\Domains\Production\Services;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryTransactionItem;
use App\Domains\MasterData\Models\Product;
use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use App\Shared\Numbering\DocumentNumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 (MRP), Phase 6 (Production Execution — Gate C), and Phase 9 (Material Cost — Gate E):
 *
 *  - MRP rule (`03_BUSINESS_PROCESS.md §2`):
 *      `Net Requirement = max(0, Gross Requirement + Safety Stock - Available Stock - Confirmed Incoming)`.
 *    Does NOT automatically create POs (`AGENT_INSTRUCTIONS.md §8`).
 *  - Releasing a Production Order copies an immutable BOM & stage snapshot (`bom_snapshot_json`) and
 *    generates Work Orders per configured stage (`1001-YYMMDD-seq`).
 *  - Material Issue posts through `InventoryPostingContract`, snapshots unit cost at issue time, and
 *    records a `production_cost_items` row linked to the ledger line (`AC-009`).
 *  - Returning unused material posts back to inventory and inserts a negative cost adjustment (`AC-009`).
 *  - Stage result recording enforces exact quantity reconciliation (`03_BUSINESS_PROCESS.md §2`):
 *      `Input Qty = Good Output + Reject Qty + Scrap Qty + WIP Remaining + Approved Process Loss`.
 *  - Production cost summary includes ONLY material/component cost; reject/scrap material remains in
 *    total material cost while `material_cost_per_good_unit` divides by accepted good output (or is
 *    `null` when good output is zero — `03_BUSINESS_PROCESS.md §8`, `AC-010`, `AC-011`, `AC-012`).
 */
final readonly class ProductionWorkflowService
{
    public function __construct(
        private BomService $bom,
        private DocumentNumberGenerator $numbering,
        private InventoryPostingContract $inventory,
        private AuditLogger $audit,
    ) {}

    /**
     * Run MRP explosion for a Production Plan and persist `production_mrp_runs` + `production_mrp_items`.
     *
     * @return array<string, mixed>
     */
    public function runMrp(int $productionPlanId): array
    {
        return DB::transaction(function () use ($productionPlanId): array {
            $plan = DB::table('production_plans')->where('id', $productionPlanId)->first();
            if ($plan === null) {
                throw new DomainException('Rencana produksi tidak ditemukan.', 'PLAN_NOT_FOUND', 404);
            }

            $planItems = DB::table('production_plan_items')->where('production_plan_id', $productionPlanId)->get();
            if ($planItems->isEmpty()) {
                throw new DomainException('Rencana produksi belum memiliki item target.', 'PLAN_EMPTY_ITEMS', 422);
            }

            $grossByProduct = [];
            $estimatedBudget = '0.00';

            foreach ($planItems as $planItem) {
                $activeBom = $this->bom->findActiveVersionForProduct((int) $planItem->product_id);
                if ($activeBom === null) {
                    throw new DomainException(
                        "Produk #{$planItem->product_id} belum memiliki versi BOM berstatus ACTIVE.",
                        'MRP_MISSING_ACTIVE_BOM',
                        422,
                    );
                }

                foreach ($this->bom->explode($activeBom, (string) $planItem->target_qty) as $req) {
                    $pid = (int) $req['product_id'];
                    $grossByProduct[$pid] = bcadd($grossByProduct[$pid] ?? '0.0000', $req['gross_qty'], 4);
                    if ($req['estimated_total_cost'] !== null) {
                        $estimatedBudget = bcadd($estimatedBudget, $req['estimated_total_cost'], 2);
                    }
                }
            }

            $nextRunNo = ((int) DB::table('production_mrp_runs')->where('production_plan_id', $productionPlanId)->max('run_no')) + 1;
            $runId = DB::table('production_mrp_runs')->insertGetId([
                'production_plan_id' => $productionPlanId,
                'run_no' => $nextRunNo,
                'status' => 'COMPLETED',
                'calculated_at' => now(),
                'parameters_json' => json_encode(['include_safety_stock' => true]),
                'summary_json' => json_encode(['estimated_material_budget' => $estimatedBudget]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mrpLines = [];
            foreach ($grossByProduct as $productId => $grossQty) {
                $product = Product::query()->findOrFail($productId);

                $balRow = InventoryBalance::query()
                    ->selectRaw('COALESCE(SUM(on_hand_qty), 0) as on_hand, COALESCE(SUM(reserved_qty), 0) as reserved')
                    ->where('product_id', $productId)
                    ->where('stock_status', StockStatus::AVAILABLE->value)
                    ->first();

                $onHand = bcadd((string) ($balRow->on_hand ?? '0'), '0', 4);
                $reserved = bcadd((string) ($balRow->reserved ?? '0'), '0', 4);
                $available = bcsub($onHand, $reserved, 4);

                // Confirmed incoming from approved/partial POs.
                $incoming = (string) DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->where('purchase_order_items.product_id', $productId)
                    ->whereIn('purchase_orders.status', ['APPROVED', 'PARTIAL'])
                    ->selectRaw('COALESCE(SUM(ordered_qty - received_qty), 0) as incoming')
                    ->value('incoming');
                $incoming = bcadd($incoming, '0', 4);

                $safety = bcadd($product->safety_stock_qty ?? '0', '0', 4);

                // Net = max(0, Gross + Safety − Available − Confirmed Incoming)
                $rawNet = bcsub(bcsub(bcadd($grossQty, $safety, 4), $available, 4), $incoming, 4);
                $net = bccomp($rawNet, '0', 4) > 0 ? $rawNet : '0.0000';

                DB::table('production_mrp_items')->insert([
                    'mrp_run_id' => $runId,
                    'product_id' => $productId,
                    'gross_requirement' => $grossQty,
                    'available_qty' => $available,
                    'reserved_qty' => $reserved,
                    'confirmed_incoming_qty' => $incoming,
                    'safety_stock_qty' => $safety,
                    'net_requirement' => $net,
                    'suggested_purchase_qty' => $net,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $mrpLines[] = [
                    'product_id' => $productId,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'gross_requirement' => $grossQty,
                    'available_qty' => $available,
                    'reserved_qty' => $reserved,
                    'confirmed_incoming_qty' => $incoming,
                    'safety_stock_qty' => $safety,
                    'net_requirement' => $net,
                    'suggested_purchase_qty' => $net,
                ];
            }

            return [
                'id' => $runId,
                'run_no' => $nextRunNo,
                'estimated_material_budget' => $estimatedBudget,
                'items' => $mrpLines,
            ];
        });
    }

    /**
     * Create and release a Production Order from an approved plan item, freezing the BOM & stage
     * snapshot and generating stage Work Orders (`AC-001`, `AC-003`).
     *
     * @return array<string, mixed>
     */
    public function releaseProductionOrder(int $productId, string $targetQty, User $actor, ?int $planItemId = null): array
    {
        return DB::transaction(function () use ($productId, $targetQty, $actor, $planItemId): array {
            $product = Product::query()->findOrFail($productId);
            $activeBom = $this->bom->findActiveVersionForProduct($productId);
            if ($activeBom === null) {
                throw new DomainException("Produk [{$product->code}] belum memiliki BOM aktif.", 'PRODUCTION_NO_ACTIVE_BOM', 422);
            }

            $snapshot = $this->bom->buildOrderSnapshot($activeBom, $targetQty);
            $orderNo = $this->numbering->next('PRODUCTION_ORDER', now(), ['product_code' => $product->code]);

            $orderId = DB::table('production_orders')->insertGetId([
                'order_no' => $orderNo,
                'production_plan_item_id' => $planItemId,
                'product_id' => $productId,
                'target_qty' => bcadd($targetQty, '0', 4),
                'bom_version_id' => $activeBom->id,
                'bom_snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'stage_snapshot_json' => json_encode($snapshot['stages'], JSON_THROW_ON_ERROR),
                'status' => 'RELEASED',
                'released_by' => $actor->id,
                'released_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stages = $snapshot['stages'] !== []
                ? $snapshot['stages']
                : [['sequence' => 1, 'stage_code' => 'ASSEMBLY', 'name' => 'Perakitan Utama', 'output_product_id' => $productId, 'qc_required' => true]];

            $workOrders = [];
            foreach ($stages as $stage) {
                $woNo = $this->numbering->next('WORK_ORDER');
                $woId = DB::table('work_orders')->insertGetId([
                    'work_order_no' => $woNo,
                    'production_order_id' => $orderId,
                    'stage_code' => $stage['stage_code'],
                    'stage_name' => $stage['name'],
                    'sequence' => $stage['sequence'],
                    'output_product_id' => $stage['output_product_id'] ?? $productId,
                    'target_qty' => bcadd($targetQty, '0', 4),
                    'status' => 'PENDING',
                    'qc_required' => (bool) ($stage['qc_required'] ?? true),
                    'qc_passed' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $workOrders[] = ['id' => $woId, 'work_order_no' => $woNo, 'stage_code' => $stage['stage_code']];
            }

            DB::table('production_cost_summaries')->insert([
                'production_order_id' => $orderId,
                'total_material_cost' => '0.00',
                'total_good_qty' => '0.0000',
                'total_reject_qty' => '0.0000',
                'material_cost_per_good_unit' => null,
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record('production.order.released', null, null, [
                'order_no' => $orderNo,
                'bom_version_id' => $activeBom->id,
            ], $actor);

            return [
                'id' => $orderId,
                'order_no' => $orderNo,
                'status' => 'RELEASED',
                'bom_snapshot' => $snapshot,
                'work_orders' => $workOrders,
            ];
        });
    }

    /**
     * Issue materials to a Work Order and record actual unit-cost snapshots in `production_cost_items` (AC-009).
     *
     * @param  non-empty-list<array{product_id: int, location_id: int, quantity: string, uom_id: int}>  $items
     * @return array<string, mixed>
     */
    public function issueMaterials(int $workOrderId, array $items, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($workOrderId, $items, $actor, $idempotencyKey): array {
            $wo = DB::table('work_orders')->where('id', $workOrderId)->lockForUpdate()->first();
            if ($wo === null) {
                throw new DomainException('Work Order tidak ditemukan.', 'WO_NOT_FOUND', 404);
            }

            $reqId = DB::table('material_requests')->insertGetId([
                'request_no' => $this->numbering->next('MATERIAL_REQUEST'),
                'work_order_id' => $workOrderId,
                'requested_by' => $actor->id,
                'approved_by' => $actor->id,
                'status' => 'FULFILLED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lines = [];
            foreach ($items as $item) {
                DB::table('material_request_items')->insert([
                    'material_request_id' => $reqId,
                    'product_id' => $item['product_id'],
                    'requested_qty' => $item['quantity'],
                    'approved_qty' => $item['quantity'],
                    'issued_qty' => $item['quantity'],
                    'uom_id' => $item['uom_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lines[] = new StockMovementLine(
                    productId: (int) $item['product_id'],
                    quantity: (string) $item['quantity'],
                    fromLocationId: (int) $item['location_id'],
                );
            }

            $issueNo = $this->numbering->next('MATERIAL_ISSUE');
            $issueId = DB::table('material_issues')->insertGetId([
                'issue_no' => $issueNo,
                'material_request_id' => $reqId,
                'issued_by' => $actor->id,
                'status' => 'POSTED',
                'issued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'MATERIAL_ISSUE',
                sourceDocumentType: 'material_issues',
                sourceDocumentId: $issueId,
                postedByUserId: $actor->id,
                lines: $lines,
                notes: "Issue {$issueNo} untuk WO {$wo->work_order_no}",
            ));

            DB::table('material_issues')->where('id', $issueId)->update(['inventory_transaction_id' => $postedTx['id']]);
            DB::table('work_orders')->where('id', $workOrderId)->update(['status' => 'IN_PROGRESS', 'started_at' => $wo->started_at ?? now()]);
            DB::table('production_orders')->where('id', $wo->production_order_id)->update(['status' => 'IN_PROGRESS']);

            // Copy ledger cost snapshots into material_issue_items and production_cost_items.
            $txItems = InventoryTransactionItem::query()->where('inventory_transaction_id', $postedTx['id'])->get();
            foreach ($txItems as $txItem) {
                DB::table('material_issue_items')->insert([
                    'material_issue_id' => $issueId,
                    'product_id' => $txItem->product_id,
                    'location_id' => $txItem->from_location_id,
                    'issued_qty' => $txItem->quantity,
                    'returned_unused_qty' => '0.0000',
                    'unit_cost' => $txItem->unit_cost ?? '0.000000',
                    'total_cost' => $txItem->total_cost ?? '0.00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('production_cost_items')->insertOrIgnore([
                    'production_order_id' => $wo->production_order_id,
                    'work_order_id' => $workOrderId,
                    'product_id' => $txItem->product_id,
                    'actual_quantity' => $txItem->quantity,
                    'unit_cost' => $txItem->unit_cost ?? '0.000000',
                    'total_cost' => $txItem->total_cost ?? '0.00',
                    'inventory_transaction_item_id' => $txItem->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->recalculateOrderCostSummary((int) $wo->production_order_id);

            return [
                'issue_id' => $issueId,
                'issue_no' => $issueNo,
                'inventory_transaction' => $postedTx,
            ];
        });
    }

    /**
     * Return unused material from a previous Material Issue back to inventory and reduce net
     * production consumption cost (`01_PRD.md FR-14`, `06_BUSINESS_RULES.md`, `11_TESTING_STRATEGY.md`).
     */
    public function returnUnusedMaterial(int $materialIssueItemId, string $returnQty, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($materialIssueItemId, $returnQty, $actor, $idempotencyKey): array {
            $issueItem = DB::table('material_issue_items')->where('id', $materialIssueItemId)->lockForUpdate()->first();
            if ($issueItem === null) {
                throw new DomainException('Baris pengeluaran material tidak ditemukan.', 'ISSUE_ITEM_NOT_FOUND', 404);
            }

            $returnQty = bcadd($returnQty, '0', 4);
            $maxReturnable = bcsub((string) $issueItem->issued_qty, (string) $issueItem->returned_unused_qty, 4);
            if (bccomp($returnQty, '0', 4) <= 0 || bccomp($returnQty, $maxReturnable, 4) > 0) {
                throw new DomainException(
                    "Kuantitas retur material tidak valid (maks. dapat diretur: {$maxReturnable}).",
                    'MATERIAL_RETURN_EXCEEDS_ISSUED',
                    409,
                );
            }

            $issue = DB::table('material_issues')->where('id', $issueItem->material_issue_id)->firstOrFail();
            $req = DB::table('material_requests')->where('id', $issue->material_request_id)->firstOrFail();
            $wo = DB::table('work_orders')->where('id', $req->work_order_id)->firstOrFail();

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'MATERIAL_RETURN',
                sourceDocumentType: 'material_issues',
                sourceDocumentId: $issue->id,
                postedByUserId: $actor->id,
                lines: [
                    new StockMovementLine(
                        productId: (int) $issueItem->product_id,
                        quantity: $returnQty,
                        toLocationId: (int) $issueItem->location_id,
                        unitCost: (string) $issueItem->unit_cost,
                    ),
                ],
                notes: "Retur sisa material tidak terpakai dari {$issue->issue_no}",
            ));

            DB::table('material_issue_items')->where('id', $materialIssueItemId)->update([
                'returned_unused_qty' => bcadd((string) $issueItem->returned_unused_qty, $returnQty, 4),
                'updated_at' => now(),
            ]);

            $txItem = InventoryTransactionItem::query()->where('inventory_transaction_id', $postedTx['id'])->firstOrFail();
            $negQty = bcmul($returnQty, '-1', 4);
            $negCost = bcmul((string) $txItem->total_cost, '-1', 2);

            DB::table('production_cost_items')->insertOrIgnore([
                'production_order_id' => $wo->production_order_id,
                'work_order_id' => $wo->id,
                'product_id' => $issueItem->product_id,
                'actual_quantity' => $negQty,
                'unit_cost' => $issueItem->unit_cost,
                'total_cost' => $negCost,
                'inventory_transaction_item_id' => $txItem->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->recalculateOrderCostSummary((int) $wo->production_order_id);
        });
    }

    /**
     * Record Work Order production result and enforce the stage reconciliation formula:
     *   `Input Qty == Good + Reject + Scrap + WIP Remaining + Approved Process Loss`
     * (`03_BUSINESS_PROCESS.md §2`, `AGENT_INSTRUCTIONS.md §8 "No silently ignoring quantity variance"`).
     *
     * @param  array{
     *   input_qty: string,
     *   good_qty: string,
     *   reject_qty?: string,
     *   scrap_qty?: string,
     *   wip_remaining_qty?: string,
     *   approved_process_loss_qty?: string,
     *   notes?: string|null
     * }  $payload
     * @return array<string, mixed>
     */
    public function recordStageResult(int $workOrderId, array $payload, User $actor): array
    {
        return DB::transaction(function () use ($workOrderId, $payload, $actor): array {
            $wo = DB::table('work_orders')->where('id', $workOrderId)->lockForUpdate()->first();
            if ($wo === null) {
                throw new DomainException('Work Order tidak ditemukan.', 'WO_NOT_FOUND', 404);
            }

            $input = bcadd($payload['input_qty'], '0', 4);
            $good = bcadd($payload['good_qty'], '0', 4);
            $reject = bcadd($payload['reject_qty'] ?? '0', '0', 4);
            $scrap = bcadd($payload['scrap_qty'] ?? '0', '0', 4);
            $wip = bcadd($payload['wip_remaining_qty'] ?? '0', '0', 4);
            $loss = bcadd($payload['approved_process_loss_qty'] ?? '0', '0', 4);

            $sumOutputs = bcadd(bcadd(bcadd(bcadd($good, $reject, 4), $scrap, 4), $wip, 4), $loss, 4);
            if (bccomp($input, $sumOutputs, 4) !== 0) {
                throw new DomainException(
                    "Selisih kuantitas tahap belum direkonsiliasi: Input ({$input}) ≠ Good + Reject + Scrap + WIP + Process Loss ({$sumOutputs}).",
                    'PRODUCTION_STAGE_QUANTITY_UNRECONCILED',
                    422,
                );
            }

            DB::table('production_results')->insert([
                'work_order_id' => $workOrderId,
                'result_date' => now(),
                'input_qty' => $input,
                'good_qty' => $good,
                'reject_qty' => $reject,
                'scrap_qty' => $scrap,
                'wip_remaining_qty' => $wip,
                'approved_process_loss_qty' => $loss,
                'recorded_by' => $actor->id,
                'notes' => $payload['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (bccomp($wip, '0', 4) === 0) {
                DB::table('work_orders')->where('id', $workOrderId)->update([
                    'status' => 'COMPLETED',
                    'qc_passed' => true,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $this->recalculateOrderCostSummary((int) $wo->production_order_id);
        });
    }

    /**
     * Recompute `production_cost_summaries` strictly from posted material issues/returns
     * (`01_PRD.md FR-14`, `03_BUSINESS_PROCESS.md §8`, `AC-009`–`AC-012`).
     *
     * @return array<string, mixed>
     */
    public function recalculateOrderCostSummary(int $productionOrderId): array
    {
        $totalMaterialCost = (string) DB::table('production_cost_items')
            ->where('production_order_id', $productionOrderId)
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total')
            ->value('total');
        $totalMaterialCost = bcadd($totalMaterialCost, '0', 2);

        $productionOrder = DB::table('production_orders')->where('id', $productionOrderId)->firstOrFail();

        // Final stage (highest sequence) good & reject/scrap output determines yield.
        $finalWo = DB::table('work_orders')
            ->where('production_order_id', $productionOrderId)
            ->orderByDesc('sequence')
            ->first();

        $goodRow = $finalWo
            ? DB::table('production_results')
                ->where('work_order_id', $finalWo->id)
                ->selectRaw('COALESCE(SUM(good_qty), 0) as good, COALESCE(SUM(reject_qty + scrap_qty), 0) as bad')
                ->first()
            : null;

        $totalGood = bcadd((string) ($goodRow->good ?? '0'), '0', 4);
        $totalReject = bcadd((string) ($goodRow->bad ?? '0'), '0', 4);

        // If accepted good output is zero, cost per good unit is null (03_BUSINESS_PROCESS.md §8, 11_TESTING_STRATEGY.md).
        $costPerGoodUnit = bccomp($totalGood, '0', 4) > 0
            ? bcdiv($totalMaterialCost, $totalGood, 6)
            : null;

        DB::table('production_cost_summaries')->updateOrInsert(
            ['production_order_id' => $productionOrderId],
            [
                'total_material_cost' => $totalMaterialCost,
                'total_good_qty' => $totalGood,
                'total_reject_qty' => $totalReject,
                'material_cost_per_good_unit' => $costPerGoodUnit,
                'calculated_at' => now(),
                'updated_at' => now(),
            ],
        );

        return [
            'production_order_id' => $productionOrderId,
            'order_no' => $productionOrder->order_no,
            'total_material_cost' => $totalMaterialCost,
            'total_good_qty' => $totalGood,
            'total_reject_qty' => $totalReject,
            'material_cost_per_good_unit' => $costPerGoodUnit,
        ];
    }
}
