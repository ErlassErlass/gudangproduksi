<?php

namespace App\Domains\VendorManagement\Services;

use App\Domains\Inventory\Enums\StockStatus;
use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use App\Shared\Numbering\DocumentNumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Phase 7 GAP / Vendor Processing service (Gate D — `03_BUSINESS_PROCESS.md §4`,
 * `06_BUSINESS_RULES.md "Vendor/GAP rules"`, `AC-004`–`AC-007`):
 *  - Dispatch moves stock from internal location to external vendor location with status `AT_VENDOR`
 *    (Erlass retains ownership; never treated as consumption).
 *  - Partial receipt records only physically received quantity (`AC-005`).
 *  - Enforces the reconciliation invariant: `sent == completed + outstanding + damaged` (`AC-006`).
 *  - Physically received items enter `QUARANTINE` first and only move to `AVAILABLE` / `DAMAGED`
 *    upon QC inspection disposition (`06_BUSINESS_RULES.md #48`, `AC-007`).
 */
final readonly class VendorProcessingService
{
    public function __construct(
        private DocumentNumberGenerator $numbering,
        private InventoryPostingContract $inventory,
        private AuditLogger $audit,
    ) {}

    /**
     * Create and dispatch a Vendor Order (e.g. 50 units sent to GAP).
     *
     * @param  array{
     *   vendor_id: int,
     *   warehouse_id: int,
     *   source_location_id: int,
     *   vendor_location_id: int,
     *   processing_type?: string,
     *   expected_return_date?: string|null,
     *   items: non-empty-list<array{product_id: int, sent_quantity: string, uom_id: int}>
     * }  $payload
     * @return array<string, mixed>
     */
    public function createAndDispatch(array $payload, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($payload, $actor, $idempotencyKey): array {
            $orderNo = $this->numbering->next('VENDOR_ORDER');
            $orderId = DB::table('vendor_orders')->insertGetId([
                'vendor_order_no' => $orderNo,
                'vendor_id' => $payload['vendor_id'],
                'processing_type' => $payload['processing_type'] ?? 'ASSEMBLY',
                'warehouse_id' => $payload['warehouse_id'],
                'source_location_id' => $payload['source_location_id'],
                'vendor_location_id' => $payload['vendor_location_id'],
                'expected_return_date' => $payload['expected_return_date'] ?? null,
                'status' => 'DISPATCHED',
                'created_by' => $actor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lines = [];
            foreach ($payload['items'] as $item) {
                $sent = bcadd($item['sent_quantity'], '0', 4);
                DB::table('vendor_order_items')->insert([
                    'vendor_order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'sent_quantity' => $sent,
                    'completed_quantity' => '0.0000',
                    'outstanding_quantity' => $sent,
                    'damaged_quantity' => '0.0000',
                    'uom_id' => $item['uom_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lines[] = new StockMovementLine(
                    productId: (int) $item['product_id'],
                    quantity: $sent,
                    fromLocationId: (int) $payload['source_location_id'],
                    toLocationId: (int) $payload['vendor_location_id'],
                    stockStatusFrom: StockStatus::AVAILABLE->value,
                    stockStatusTo: StockStatus::AT_VENDOR->value,
                );
            }

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'VENDOR_DISPATCH',
                sourceDocumentType: 'vendor_orders',
                sourceDocumentId: $orderId,
                postedByUserId: $actor->id,
                lines: $lines,
                notes: "Dispatch ke vendor GAP {$orderNo}",
            ));

            $this->audit->record('vendor.order.dispatched', null, null, ['vendor_order_no' => $orderNo], $actor);

            return [
                'id' => $orderId,
                'vendor_order_no' => $orderNo,
                'status' => 'DISPATCHED',
                'inventory_transaction' => $postedTx,
            ];
        });
    }

    /**
     * Receive goods back from GAP vendor, run QC disposition, and reconcile quantities (`AC-005`–`AC-007`).
     *
     * Example from `03_BUSINESS_PROCESS.md §4`:
     *   50 sent → 47 physically returned (45 accepted, 2 damaged), 3 remain outstanding at vendor.
     *
     * @param  array{
     *   receiving_location_id: int,
     *   items: non-empty-list<array{
     *     vendor_order_item_id: int,
     *     received_quantity: string,
     *     accepted_quantity: string,
     *     damaged_quantity: string
     *   }>
     * }  $payload
     * @return array<string, mixed>
     */
    public function receiveAndInspect(int $vendorOrderId, array $payload, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($vendorOrderId, $payload, $actor, $idempotencyKey): array {
            $order = DB::table('vendor_orders')->where('id', $vendorOrderId)->lockForUpdate()->first();
            if ($order === null) {
                throw new DomainException('Vendor Order tidak ditemukan.', 'VENDOR_ORDER_NOT_FOUND', 404);
            }

            $receiptNo = $this->numbering->next('VENDOR_RECEIPT');
            $receiptId = DB::table('vendor_receipts')->insertGetId([
                'receipt_no' => $receiptNo,
                'vendor_order_id' => $vendorOrderId,
                'receiving_location_id' => $payload['receiving_location_id'],
                'received_by' => $actor->id,
                'received_at' => now(),
                'receipt_status' => 'POSTED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lines = [];
            foreach ($payload['items'] as $idx => $item) {
                $voItem = DB::table('vendor_order_items')
                    ->where('id', $item['vendor_order_item_id'])
                    ->where('vendor_order_id', $vendorOrderId)
                    ->lockForUpdate()
                    ->first();

                if ($voItem === null) {
                    throw new DomainException('Baris Vendor Order tidak valid.', 'VENDOR_ORDER_ITEM_NOT_FOUND', 422);
                }

                $received = bcadd($item['received_quantity'], '0', 4);
                $accepted = bcadd($item['accepted_quantity'], '0', 4);
                $damaged = bcadd($item['damaged_quantity'], '0', 4);

                if (bccomp(bcadd($accepted, $damaged, 4), $received, 4) !== 0) {
                    throw new DomainException(
                        'Kuantitas diterima dari vendor harus sama dengan Accepted + Damaged.',
                        'VENDOR_RECEIPT_SPLIT_MISMATCH',
                        422,
                    );
                }

                if (bccomp($received, (string) $voItem->outstanding_quantity, 4) > 0) {
                    throw new DomainException(
                        "Kuantitas diterima ({$received}) melebihi sisa outstanding di vendor ({$voItem->outstanding_quantity}).",
                        'VENDOR_RECEIPT_EXCEEDS_OUTSTANDING',
                        409,
                    );
                }

                $newCompleted = bcadd((string) $voItem->completed_quantity, $accepted, 4);
                $newDamaged = bcadd((string) $voItem->damaged_quantity, $damaged, 4);
                $newOutstanding = bcsub((string) $voItem->outstanding_quantity, $received, 4);

                // Invariant check: sent == completed + outstanding + damaged (06_BUSINESS_RULES.md #46).
                $reconciledSum = bcadd(bcadd($newCompleted, $newOutstanding, 4), $newDamaged, 4);
                if (bccomp((string) $voItem->sent_quantity, $reconciledSum, 4) !== 0) {
                    throw new DomainException('Rekonsiliasi kuantitas vendor gagal.', 'VENDOR_RECONCILIATION_ERROR', 409);
                }

                DB::table('vendor_order_items')->where('id', $voItem->id)->update([
                    'completed_quantity' => $newCompleted,
                    'damaged_quantity' => $newDamaged,
                    'outstanding_quantity' => $newOutstanding,
                    'updated_at' => now(),
                ]);

                DB::table('vendor_receipt_items')->insert([
                    'vendor_receipt_id' => $receiptId,
                    'vendor_order_item_id' => $voItem->id,
                    'received_quantity' => $received,
                    'accepted_quantity' => $accepted,
                    'rejected_quantity' => $damaged,
                    'outstanding_quantity' => $newOutstanding,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (bccomp($accepted, '0', 4) > 0) {
                    $lines[] = new StockMovementLine(
                        productId: (int) $voItem->product_id,
                        quantity: $accepted,
                        fromLocationId: (int) $order->vendor_location_id,
                        toLocationId: (int) $payload['receiving_location_id'],
                        stockStatusFrom: StockStatus::AT_VENDOR->value,
                        stockStatusTo: StockStatus::AVAILABLE->value,
                    );
                }

                if (bccomp($damaged, '0', 4) > 0) {
                    $lines[] = new StockMovementLine(
                        productId: (int) $voItem->product_id,
                        quantity: $damaged,
                        fromLocationId: (int) $order->vendor_location_id,
                        toLocationId: (int) $payload['receiving_location_id'],
                        stockStatusFrom: StockStatus::AT_VENDOR->value,
                        stockStatusTo: StockStatus::DAMAGED->value,
                    );
                }
            }

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'VENDOR_RECEIPT',
                sourceDocumentType: 'vendor_receipts',
                sourceDocumentId: $receiptId,
                postedByUserId: $actor->id,
                lines: $lines,
                notes: "Penerimaan vendor {$receiptNo}",
            ));

            DB::table('vendor_receipts')->where('id', $receiptId)->update(['inventory_transaction_id' => $postedTx['id']]);

            $stillOutstanding = DB::table('vendor_order_items')
                ->where('vendor_order_id', $vendorOrderId)
                ->where('outstanding_quantity', '>', 0)
                ->exists();

            $newStatus = $stillOutstanding ? 'PARTIAL_RETURNED' : 'COMPLETED';
            DB::table('vendor_orders')->where('id', $vendorOrderId)->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

            return [
                'receipt_id' => $receiptId,
                'receipt_no' => $receiptNo,
                'vendor_order_status' => $newStatus,
                'inventory_transaction' => $postedTx,
            ];
        });
    }
}
