<?php

namespace App\Domains\Inventory\Actions;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use App\Shared\Numbering\DocumentNumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Approval-controlled stock adjustment workflow (agent_docs/06_BUSINESS_RULES.md,
 * 09_RBAC_MATRIX.md "Separation of duties", AC-016):
 *  - Creating an adjustment stores a SUBMITTED document (does not touch balances).
 *  - Approving & posting requires `inventory.adjustment.approve` and enforces creator ≠ approver
 *    unless the approver is Super Admin in a single-operator emergency.
 */
final readonly class StockAdjustmentService
{
    public function __construct(
        private DocumentNumberGenerator $numbering,
        private InventoryPostingContract $inventory,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *   location_id: int,
     *   reason: string,
     *   items: non-empty-list<array{
     *     product_id: int,
     *     direction: 'IN'|'OUT',
     *     quantity: string,
     *     batch_id?: int|null,
     *     product_serial_id?: int|null,
     *     unit_cost?: string|null,
     *     notes?: string|null
     *   }>
     * }  $payload
     */
    public function create(array $payload, User $creator): StockAdjustment
    {
        return DB::transaction(function () use ($payload, $creator): StockAdjustment {
            $adjustment = StockAdjustment::query()->create([
                'adjustment_no' => $this->numbering->next('INVENTORY_ADJUSTMENT'),
                'location_id' => $payload['location_id'],
                'reason' => $payload['reason'],
                'status' => 'SUBMITTED',
                'created_by' => $creator->id,
            ]);

            foreach ($payload['items'] as $item) {
                StockAdjustmentItem::query()->create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'product_serial_id' => $item['product_serial_id'] ?? null,
                    'direction' => $item['direction'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $adjustment->load(['items.product', 'location', 'creator']);
            $this->audit->record('inventory.adjustment.submitted', $adjustment, null, [
                'adjustment_no' => $adjustment->adjustment_no,
                'reason' => $adjustment->reason,
            ], $creator);

            return $adjustment;
        });
    }

    public function approveAndPost(StockAdjustment $adjustment, User $approver, string $idempotencyKey): StockAdjustment
    {
        if ($adjustment->status !== 'SUBMITTED') {
            throw new DomainException(
                "Penyesuaian stok berstatus [{$adjustment->status}] tidak dapat disetujui.",
                'ADJUSTMENT_INVALID_STATE',
                409,
            );
        }

        if ($adjustment->created_by === $approver->id) {
            throw new DomainException(
                'Pembuat penyesuaian stok tidak boleh menyetujui dokumennya sendiri (separation of duties).',
                'ADJUSTMENT_SELF_APPROVAL_FORBIDDEN',
                409,
            );
        }

        return DB::transaction(function () use ($adjustment, $approver, $idempotencyKey): StockAdjustment {
            $adjustment->loadMissing('items');

            $lines = [];
            foreach ($adjustment->items as $item) {
                $lines[] = new StockMovementLine(
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    fromLocationId: $item->direction === 'OUT' ? $adjustment->location_id : null,
                    toLocationId: $item->direction === 'IN' ? $adjustment->location_id : null,
                    batchId: $item->batch_id,
                    productSerialId: $item->product_serial_id,
                    unitCost: $item->unit_cost,
                );
            }

            $posted = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'ADJUSTMENT',
                sourceDocumentType: 'stock_adjustments',
                sourceDocumentId: $adjustment->id,
                postedByUserId: $approver->id,
                lines: $lines,
                notes: "Adjustment {$adjustment->adjustment_no}: {$adjustment->reason}",
            ));

            $adjustment->update([
                'status' => 'POSTED',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'posted_transaction_id' => $posted['id'],
            ]);

            $this->audit->record('inventory.adjustment.approved', $adjustment, ['status' => 'SUBMITTED'], [
                'status' => 'POSTED',
                'posted_transaction_id' => $posted['id'],
            ], $approver);

            return $adjustment->fresh(['items.product', 'location', 'creator', 'approver']) ?? $adjustment;
        });
    }
}
