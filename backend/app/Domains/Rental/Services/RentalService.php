<?php

namespace App\Domains\Rental\Services;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Services\ReservationService;
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
 * Phase 8 Rental & Loan + Return QC service (Gate D — `01_PRD.md FR-10/FR-12`,
 * `06_BUSINESS_RULES.md "Rental rules"`, `AC-013`, `AC-014`):
 *  - Serialized rental unit cannot be double-booked across overlapping rental date ranges (`AC-013`).
 *  - Captures expected kit composition (`expected_contents_json`) at reservation/dispatch time.
 *  - Returned unit is placed in `QUARANTINE` (not `AVAILABLE`) until return QC inspection completes (`06_BUSINESS_RULES.md #56`).
 *  - Return QC compares expected vs actual kit contents, creates `quality_discrepancies` with
 *    estimated claim amounts from component reference prices (`01_PRD.md FR-10`), and only moves
 *    the unit back to `AVAILABLE` if QC passes.
 */
final readonly class RentalService
{
    public function __construct(
        private DocumentNumberGenerator $numbering,
        private ReservationService $reservations,
        private InventoryPostingContract $inventory,
        private AuditLogger $audit,
    ) {}

    /**
     * Create and reserve a serialized unit for a rental/loan order.
     *
     * @param  array{
     *   transaction_kind?: 'RENTAL'|'LOAN',
     *   customer_id: int,
     *   project_name?: string|null,
     *   group_leader?: string|null,
     *   sales_pic?: string|null,
     *   start_date: string,
     *   expected_return_date: string,
     *   product_id: int,
     *   product_serial_id: int,
     *   source_location_id: int,
     *   expected_contents: list<array{product_id: int, expected_qty: string}>
     * }  $payload
     * @return array<string, mixed>
     */
    public function createAndReserve(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor): array {
            // Overlap check (AC-013): a serial cannot be booked on an active rental whose window overlaps [start_date, expected_return_date].
            $overlapExists = DB::table('rental_order_items')
                ->join('rental_orders', 'rental_orders.id', '=', 'rental_order_items.rental_order_id')
                ->where('rental_order_items.product_serial_id', $payload['product_serial_id'])
                ->whereIn('rental_orders.status', ['RESERVED', 'DISPATCHED', 'RETURNED_PENDING_QC'])
                ->where('rental_orders.start_date', '<=', $payload['expected_return_date'])
                ->where('rental_orders.expected_return_date', '>=', $payload['start_date'])
                ->lockForUpdate()
                ->exists();

            if ($overlapExists) {
                throw new DomainException(
                    'Unit serial ini sudah dipesan pada rentang tanggal yang bertumpukan.',
                    'RENTAL_SERIAL_OVERLAPPING_BOOKING',
                    409,
                );
            }

            $rentalNo = $this->numbering->next('RENTAL_ORDER');
            $orderId = DB::table('rental_orders')->insertGetId([
                'rental_no' => $rentalNo,
                'transaction_kind' => $payload['transaction_kind'] ?? 'RENTAL',
                'customer_id' => $payload['customer_id'],
                'project_name' => $payload['project_name'] ?? null,
                'group_leader' => $payload['group_leader'] ?? null,
                'sales_pic' => $payload['sales_pic'] ?? null,
                'start_date' => $payload['start_date'],
                'expected_return_date' => $payload['expected_return_date'],
                'status' => 'RESERVED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderItemId = DB::table('rental_order_items')->insertGetId([
                'rental_order_id' => $orderId,
                'product_id' => $payload['product_id'],
                'product_serial_id' => $payload['product_serial_id'],
                'source_location_id' => $payload['source_location_id'],
                'quantity' => '1.0000',
                'expected_contents_json' => json_encode($payload['expected_contents'], JSON_THROW_ON_ERROR),
                'dispatched_qty' => '0.0000',
                'returned_qty' => '0.0000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $reservation = $this->reservations->reserve(
                sourceType: 'rental_orders',
                sourceId: $orderId,
                productId: $payload['product_id'],
                locationId: $payload['source_location_id'],
                quantity: '1.0000',
                productSerialId: $payload['product_serial_id'],
                userId: $actor->id,
            );

            $this->audit->record('rental.order.reserved', null, null, ['rental_no' => $rentalNo], $actor);

            return [
                'id' => $orderId,
                'rental_no' => $rentalNo,
                'rental_order_item_id' => $orderItemId,
                'reservation_id' => $reservation->id,
                'status' => 'RESERVED',
            ];
        });
    }

    public function dispatch(int $rentalOrderId, int $reservationId, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($rentalOrderId, $reservationId, $actor, $idempotencyKey): array {
            $order = DB::table('rental_orders')->where('id', $rentalOrderId)->lockForUpdate()->firstOrFail();
            $item = DB::table('rental_order_items')->where('rental_order_id', $rentalOrderId)->firstOrFail();

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'RENTAL_DISPATCH',
                sourceDocumentType: 'rental_orders',
                sourceDocumentId: $rentalOrderId,
                postedByUserId: $actor->id,
                lines: [
                    new StockMovementLine(
                        productId: (int) $item->product_id,
                        quantity: '1.0000',
                        fromLocationId: (int) $item->source_location_id,
                        productSerialId: (int) $item->product_serial_id,
                        consumeReservationId: $reservationId,
                    ),
                ],
                notes: "Dispatch rental {$order->rental_no}",
            ));

            DB::table('rental_orders')->where('id', $rentalOrderId)->update(['status' => 'DISPATCHED', 'updated_at' => now()]);
            DB::table('rental_order_items')->where('id', $item->id)->update(['dispatched_qty' => '1.0000', 'updated_at' => now()]);

            return ['status' => 'DISPATCHED', 'inventory_transaction' => $postedTx];
        });
    }

    /**
     * Receive returned rental unit into `QUARANTINE` (never `AVAILABLE` before QC — `06_BUSINESS_RULES.md #56`,
     * `AGENT_INSTRUCTIONS.md §8 "No treating returned rental goods as available before QC"`).
     */
    public function receiveReturn(int $rentalOrderId, int $returnLocationId, User $actor, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($rentalOrderId, $returnLocationId, $actor, $idempotencyKey): array {
            $order = DB::table('rental_orders')->where('id', $rentalOrderId)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'DISPATCHED') {
                throw new DomainException('Hanya pesanan rental berstatus DISPATCHED yang dapat dikembalikan.', 'RENTAL_INVALID_STATE', 409);
            }

            $item = DB::table('rental_order_items')->where('rental_order_id', $rentalOrderId)->firstOrFail();
            $returnNo = $this->numbering->next('RENTAL_RETURN');

            $returnId = DB::table('rental_returns')->insertGetId([
                'return_no' => $returnNo,
                'rental_order_id' => $rentalOrderId,
                'return_location_id' => $returnLocationId,
                'received_by' => $actor->id,
                'returned_at' => now(),
                'status' => 'QUARANTINE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'RENTAL_RETURN',
                sourceDocumentType: 'rental_returns',
                sourceDocumentId: $returnId,
                postedByUserId: $actor->id,
                lines: [
                    new StockMovementLine(
                        productId: (int) $item->product_id,
                        quantity: '1.0000',
                        toLocationId: $returnLocationId,
                        productSerialId: (int) $item->product_serial_id,
                        stockStatusTo: StockStatus::QUARANTINE->value,
                    ),
                ],
                notes: "Pengembalian rental {$returnNo} (menunggu QC)",
            ));

            DB::table('rental_orders')->where('id', $rentalOrderId)->update(['status' => 'RETURNED_PENDING_QC', 'updated_at' => now()]);
            DB::table('rental_order_items')->where('id', $item->id)->update(['returned_qty' => '1.0000', 'updated_at' => now()]);

            return [
                'return_id' => $returnId,
                'return_no' => $returnNo,
                'status' => 'QUARANTINE',
                'inventory_transaction' => $postedTx,
            ];
        });
    }

    /**
     * Inspect returned rental kit against expected contents (`AC-014`).
     *
     * @param  list<array{product_id: int, actual_qty: string, damaged_qty?: string}>  $actualContents
     * @return array<string, mixed>
     */
    public function inspectReturn(int $rentalReturnId, array $actualContents, User $inspector, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($rentalReturnId, $actualContents, $inspector, $idempotencyKey): array {
            $ret = DB::table('rental_returns')->where('id', $rentalReturnId)->lockForUpdate()->firstOrFail();
            $orderItem = DB::table('rental_order_items')->where('rental_order_id', $ret->rental_order_id)->firstOrFail();
            $expectedList = json_decode((string) $orderItem->expected_contents_json, true) ?: [];

            $inspectionNo = $this->numbering->next('QUALITY_INSPECTION');
            $inspectionId = DB::table('quality_inspections')->insertGetId([
                'inspection_no' => $inspectionNo,
                'inspection_type' => 'RENTAL_RETURN',
                'source_document_type' => 'rental_returns',
                'source_document_id' => $rentalReturnId,
                'product_id' => $orderItem->product_id,
                'inspected_by' => $inspector->id,
                'inspected_at' => now(),
                'status' => 'PASSED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $actualByProduct = collect($actualContents)->keyBy('product_id');
            $discrepancies = [];
            $totalEstimatedClaim = '0.00';

            foreach ($expectedList as $exp) {
                $compId = (int) $exp['product_id'];
                $expectedQty = bcadd((string) $exp['expected_qty'], '0', 4);
                $actualRow = $actualByProduct->get($compId);
                $actualQty = bcadd((string) ($actualRow['actual_qty'] ?? '0'), '0', 4);
                $damagedQty = bcadd((string) ($actualRow['damaged_qty'] ?? '0'), '0', 4);
                $missingQty = bccomp($expectedQty, $actualQty, 4) > 0 ? bcsub($expectedQty, $actualQty, 4) : '0.0000';

                DB::table('rental_return_items')->insert([
                    'rental_return_id' => $rentalReturnId,
                    'rental_order_item_id' => $orderItem->id,
                    'product_id' => $compId,
                    'expected_qty' => $expectedQty,
                    'actual_qty' => $actualQty,
                    'missing_qty' => $missingQty,
                    'damaged_qty' => $damagedQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $component = Product::query()->findOrFail($compId);
                $refPrice = $component->reference_purchase_price ?? '0.00';

                foreach (['MISSING' => $missingQty, 'DAMAGED' => $damagedQty] as $type => $qty) {
                    if (bccomp($qty, '0', 4) > 0) {
                        $claim = bcmul($qty, $refPrice, 2);
                        $totalEstimatedClaim = bcadd($totalEstimatedClaim, $claim, 2);

                        DB::table('quality_discrepancies')->insert([
                            'quality_inspection_id' => $inspectionId,
                            'product_id' => $compId,
                            'discrepancy_type' => $type,
                            'quantity' => $qty,
                            'reference_unit_price' => $refPrice,
                            'estimated_claim_amount' => $claim,
                            'resolution_status' => 'OPEN',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $discrepancies[] = [
                            'product_code' => $component->code,
                            'discrepancy_type' => $type,
                            'quantity' => $qty,
                            'estimated_claim_amount' => $claim,
                        ];
                    }
                }
            }

            $passed = $discrepancies === [];
            DB::table('quality_inspections')->where('id', $inspectionId)->update([
                'status' => $passed ? 'PASSED' : 'CONDITIONAL',
            ]);

            if ($passed) {
                // Release serialized unit from QUARANTINE back to AVAILABLE.
                $this->inventory->post(new PostInventoryCommand(
                    idempotencyKey: $idempotencyKey,
                    transactionType: 'TRANSFER',
                    sourceDocumentType: 'quality_inspections',
                    sourceDocumentId: $inspectionId,
                    postedByUserId: $inspector->id,
                    lines: [
                        new StockMovementLine(
                            productId: (int) $orderItem->product_id,
                            quantity: '1.0000',
                            fromLocationId: (int) $ret->return_location_id,
                            toLocationId: (int) $ret->return_location_id,
                            productSerialId: (int) $orderItem->product_serial_id,
                            stockStatusFrom: StockStatus::QUARANTINE->value,
                            stockStatusTo: StockStatus::AVAILABLE->value,
                        ),
                    ],
                    notes: "Pelepasan QC pengembalian rental {$inspectionNo}",
                ));

                DB::table('rental_returns')->where('id', $rentalReturnId)->update(['status' => 'INSPECTED_PASSED']);
                DB::table('rental_orders')->where('id', $ret->rental_order_id)->update(['status' => 'COMPLETED']);
            } else {
                DB::table('rental_returns')->where('id', $rentalReturnId)->update(['status' => 'INSPECTED_ISSUE']);
            }

            return [
                'inspection_id' => $inspectionId,
                'inspection_no' => $inspectionNo,
                'passed' => $passed,
                'discrepancies' => $discrepancies,
                'total_estimated_claim_amount' => $totalEstimatedClaim,
            ];
        });
    }
}
