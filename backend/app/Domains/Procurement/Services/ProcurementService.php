<?php

namespace App\Domains\Procurement\Services;

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
 * Phase 4 Procurement domain service (Gate B):
 *  - Partial GRN validation (`received_qty <= ordered_qty - already_received_qty`).
 *  - Incoming QC split (`accepted_qty + rejected_qty == received_qty`).
 *  - Accepted quantity posts to `AVAILABLE` at the receiving location; rejected quantity posts to
 *    `QUARANTINE` (never available for picking — AC-007).
 *  - Preserves PO unit price and actual receipt unit price in `purchase_cost_histories` (AC-008).
 */
final readonly class ProcurementService
{
    public function __construct(
        private DocumentNumberGenerator $numbering,
        private InventoryPostingContract $inventory,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *   supplier_id?: int|null,
     *   supplier_name?: string|null,
     *   supplier_phone?: string|null,
     *   status?: string|null,
     *   order_date: string,
     *   expected_date?: string|null,
     *   notes?: string|null,
     *   items: non-empty-list<array{product_id: int, ordered_qty: string, unit_price: string, uom_id: int}>
     * }  $payload
     */
    public function createPurchaseOrder(array $payload, User $creator): int
    {
        return DB::transaction(function () use ($payload, $creator): int {
            $subtotal = '0.00';
            foreach ($payload['items'] as $item) {
                $subtotal = bcadd($subtotal, bcmul($item['ordered_qty'], $item['unit_price'], 2), 2);
            }

            $supplierId = $payload['supplier_id'] ?? null;
            $supplierName = $payload['supplier_name'] ?? null;
            if (! $supplierName && $supplierId) {
                $supplierName = DB::table('suppliers')->where('id', $supplierId)->value('name');
            }
            $supplierPhone = $payload['supplier_phone'] ?? null;
            $status = $payload['status'] ?? 'DIPESAN';

            $poNo = $this->numbering->next('PURCHASE_ORDER');
            $poId = DB::table('purchase_orders')->insertGetId([
                'po_no' => $poNo,
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'supplier_phone' => $supplierPhone,
                'order_date' => $payload['order_date'],
                'expected_date' => $payload['expected_date'] ?? null,
                'status' => $status,
                'currency_code' => config('pwms.default_currency', 'IDR'),
                'subtotal' => $subtotal,
                'notes' => $payload['notes'] ?? null,
                'created_by' => $creator->id,
                'approved_by' => $creator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($payload['items'] as $item) {
                DB::table('purchase_order_items')->insert([
                    'purchase_order_id' => $poId,
                    'product_id' => $item['product_id'],
                    'ordered_qty' => $item['ordered_qty'],
                    'received_qty' => '0.0000',
                    'unit_price' => $item['unit_price'],
                    'uom_id' => $item['uom_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit->record('procurement.po.created', null, null, ['po_no' => $poNo, 'subtotal' => $subtotal], $creator);

            return $poId;
        });
    }

    /**
     * Receive and post a Goods Receipt (GRN) with incoming QC split.
     *
     * @param  array{
     *   receiving_location_id: int,
     *   quarantine_location_id?: int|null,
     *   items: non-empty-list<array{
     *     purchase_order_item_id: int,
     *     received_qty: string,
     *     accepted_qty: string,
     *     rejected_qty: string,
     *     actual_unit_price?: string|null,
     *     batch_id?: int|null,
     *     notes?: string|null
     *   }>
     * }  $payload
     * @return array<string, mixed>
     */
    public function receiveAndPostGrn(int $purchaseOrderId, array $payload, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($purchaseOrderId, $payload, $actor, $idempotencyKey): array {
            $po = DB::table('purchase_orders')->where('id', $purchaseOrderId)->lockForUpdate()->first();
            if ($po === null) {
                throw new DomainException('Purchase Order tidak ditemukan.', 'PO_NOT_FOUND', 404);
            }
            if (! in_array($po->status, ['APPROVED', 'PARTIAL', 'DIPESAN', 'DIKIRIM', 'SELESAI DITERIMA'], true)) {
                throw new DomainException("PO berstatus [{$po->status}] tidak dapat menerima barang.", 'PO_INVALID_STATE', 409);
            }

            $grnNo = $this->numbering->next('GOODS_RECEIPT');
            $grnId = DB::table('goods_receipts')->insertGetId([
                'grn_no' => $grnNo,
                'purchase_order_id' => $purchaseOrderId,
                'receiving_location_id' => $payload['receiving_location_id'],
                'quarantine_location_id' => $payload['quarantine_location_id'] ?? $payload['receiving_location_id'],
                'received_at' => now(),
                'received_by' => $actor->id,
                'status' => 'POSTED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $movementLines = [];
            foreach ($payload['items'] as $idx => $item) {
                $poItem = DB::table('purchase_order_items')
                    ->where('id', $item['purchase_order_item_id'])
                    ->where('purchase_order_id', $purchaseOrderId)
                    ->lockForUpdate()
                    ->first();

                if ($poItem === null) {
                    throw new DomainException('Baris PO tidak valid.', 'PO_ITEM_NOT_FOUND', 422);
                }

                $received = bcadd($item['received_qty'], '0', 4);
                $accepted = bcadd($item['accepted_qty'], '0', 4);
                $rejected = bcadd($item['rejected_qty'], '0', 4);

                if (bccomp(bcadd($accepted, $rejected, 4), $received, 4) !== 0) {
                    throw new DomainException(
                        'Jumlah accepted + rejected harus sama dengan jumlah yang diterima secara fisik.',
                        'GRN_QC_SPLIT_MISMATCH',
                        422,
                        ["items.{$idx}.accepted_qty" => ['Accepted + Rejected harus sama dengan Received.']],
                    );
                }

                $outstanding = bcsub((string) $poItem->ordered_qty, (string) $poItem->received_qty, 4);
                if (bccomp($received, $outstanding, 4) > 0) {
                    throw new DomainException(
                        "Kuantitas penerimaan ({$received}) melebihi sisa PO ({$outstanding}).",
                        'GRN_EXCEEDS_PO_OUTSTANDING',
                        409,
                    );
                }

                $actualPrice = isset($item['actual_unit_price'])
                    ? bcadd((string) $item['actual_unit_price'], '0', 6)
                    : bcadd((string) $poItem->unit_price, '0', 6);

                $grnItemId = DB::table('goods_receipt_items')->insertGetId([
                    'goods_receipt_id' => $grnId,
                    'purchase_order_item_id' => $poItem->id,
                    'received_qty' => $received,
                    'accepted_qty' => $accepted,
                    'rejected_qty' => $rejected,
                    'actual_unit_price' => $actualPrice,
                    'batch_id' => $item['batch_id'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update PO line received_qty; original PO unit_price is NEVER overwritten (AC-008).
                DB::table('purchase_order_items')
                    ->where('id', $poItem->id)
                    ->update([
                        'received_qty' => bcadd((string) $poItem->received_qty, $accepted, 4),
                        'updated_at' => now(),
                    ]);

                DB::table('purchase_cost_histories')->insert([
                    'product_id' => $poItem->product_id,
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_item_id' => $poItem->id,
                    'goods_receipt_item_id' => $grnItemId,
                    'unit_price' => $actualPrice,
                    'currency_code' => $po->currency_code,
                    'effective_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (bccomp($accepted, '0', 4) > 0) {
                    $movementLines[] = new StockMovementLine(
                        productId: (int) $poItem->product_id,
                        quantity: $accepted,
                        toLocationId: (int) $payload['receiving_location_id'],
                        batchId: isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                        stockStatusTo: StockStatus::AVAILABLE->value,
                        unitCost: $actualPrice,
                    );
                }

                if (bccomp($rejected, '0', 4) > 0) {
                    $movementLines[] = new StockMovementLine(
                        productId: (int) $poItem->product_id,
                        quantity: $rejected,
                        toLocationId: (int) ($payload['quarantine_location_id'] ?? $payload['receiving_location_id']),
                        batchId: isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                        stockStatusTo: StockStatus::QUARANTINE->value,
                        unitCost: $actualPrice,
                    );
                }
            }

            $postedTx = null;
            if ($movementLines !== []) {
                $postedTx = $this->inventory->post(new PostInventoryCommand(
                    idempotencyKey: $idempotencyKey,
                    transactionType: 'PURCHASE_RECEIPT',
                    sourceDocumentType: 'goods_receipts',
                    sourceDocumentId: $grnId,
                    postedByUserId: $actor->id,
                    lines: $movementLines,
                    notes: "GRN {$grnNo} untuk PO {$po->po_no}",
                ));

                DB::table('goods_receipts')->where('id', $grnId)->update([
                    'inventory_transaction_id' => $postedTx['id'],
                ]);
            }

            // Recompute PO fulfillment status.
            $remainingLines = DB::table('purchase_order_items')
                ->where('purchase_order_id', $purchaseOrderId)
                ->whereRaw('received_qty < ordered_qty')
                ->count();

            DB::table('purchase_orders')->where('id', $purchaseOrderId)->update([
                'status' => $remainingLines === 0 ? 'RECEIVED' : 'PARTIAL',
                'updated_at' => now(),
            ]);

            return [
                'id' => $grnId,
                'grn_no' => $grnNo,
                'po_status' => $remainingLines === 0 ? 'RECEIVED' : 'PARTIAL',
                'inventory_transaction' => $postedTx,
            ];
        });
    }
}
