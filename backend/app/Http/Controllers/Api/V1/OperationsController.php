<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Procurement\Services\ProcurementService;
use App\Domains\Production\Services\BomService;
use App\Domains\Production\Services\ProductionWorkflowService;
use App\Domains\Rental\Models\SalesLoan;
use App\Domains\Rental\Services\RentalService;
use App\Domains\VendorManagement\Services\VendorProcessingService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Exceptions\DomainException;
use App\Shared\Idempotency\IdempotencyService;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use App\Shared\Numbering\DocumentNumberGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Phase 4–9 operational endpoints mapped to `08_API_SPECIFICATION.md`:
 * Procurement, Production Planning/MRP, Production Execution, Vendor/GAP, Rental/QC,
 * Distribution Shipments, and Material Cost & Operational Reports.
 */
final class OperationsController extends Controller
{
    public function __construct(
        private readonly ProcurementService $procurement,
        private readonly ProductionWorkflowService $production,
        private readonly BomService $bom,
        private readonly VendorProcessingService $vendor,
        private readonly RentalService $rental,
        private readonly InventoryPostingContract $inventory,
        private readonly DocumentNumberGenerator $numbering,
    ) {}

    // --- Procurement (Phase 4) ---

    public function purchaseOrders(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'procurement.view');

        $orders = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->select(
                'purchase_orders.*',
                DB::raw("COALESCE(purchase_orders.supplier_name, suppliers.name, 'Supplier') as supplier_name"),
                'suppliers.code as supplier_code'
            )
            ->orderByDesc('purchase_orders.id')
            ->get()
            ->map(function ($po) {
                $po->items = DB::table('purchase_order_items')
                    ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
                    ->where('purchase_order_id', $po->id)
                    ->select('purchase_order_items.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $po;
            });

        return response()->json(['data' => $orders]);
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'procurement.po.create');
        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:160'],
            'supplier_phone' => ['nullable', 'string', 'max:48'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'status' => ['nullable', 'string', Rule::in(['DIPESAN', 'DIKIRIM', 'SELESAI DITERIMA', 'APPROVED', 'DRAFT', 'PARTIAL', 'RECEIVED'])],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.ordered_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
        ]);

        // Aturan Bisnis: Purchase Order & GRN produknya HANYA berupa komponen/finished good dengan kategori fisik
        foreach ($data['items'] as $item) {
            $product = DB::table('products')
                ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
                ->where('products.id', $item['product_id'])
                ->select('products.*', 'product_categories.code as category_code', 'product_categories.name as category_name')
                ->first();

            if (! $product) {
                throw new DomainException('Produk tidak ditemukan.', 'PRODUCT_NOT_FOUND', 404);
            }

            $isPhysical = ($product->physical_type === 'PHYSICAL');
            $categoryCode = strtoupper($product->category_code ?? '');
            $categoryName = strtolower($product->category_name ?? '');
            $isEligibleCategory = in_array($categoryCode, ['COMP', 'FG', 'WIP-MOD', 'WIP-BOX'], true)
                || str_contains($categoryName, 'komponen')
                || str_contains($categoryName, 'finished good')
                || str_contains($categoryName, 'spare part');

            if (! $isPhysical || ! $isEligibleCategory) {
                throw new DomainException(
                    "Produk [{$product->code}] bukan komponen atau finished good dengan kategori fisik. Purchase Order & Penerimaan Barang (GRN) hanya mengizinkan produk fisik.",
                    'INVALID_PO_PRODUCT',
                    422
                );
            }
        }

        if (empty($data['supplier_name']) && !empty($data['supplier_id'])) {
            $data['supplier_name'] = DB::table('suppliers')->where('id', $data['supplier_id'])->value('name');
        } elseif (empty($data['supplier_name'])) {
            $data['supplier_name'] = 'Supplier Umum';
        }

        if (empty($data['status'])) {
            $data['status'] = 'DIPESAN';
        }

        /** @var User $user */
        $user = $request->user();
        $poId = $this->procurement->createPurchaseOrder($data, $user);

        return response()->json(['data' => DB::table('purchase_orders')->where('id', $poId)->first()], 201);
    }

    public function updatePurchaseOrderStatus(Request $request, int $purchaseOrder): JsonResponse
    {
        $this->requirePermission($request, 'procurement.po.create');
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['DIPESAN', 'DIKIRIM', 'SELESAI DITERIMA', 'APPROVED', 'CANCELLED'])],
        ]);

        DB::table('purchase_orders')->where('id', $purchaseOrder)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        $updated = DB::table('purchase_orders')->where('id', $purchaseOrder)->first();

        return response()->json(['data' => $updated]);
    }

    public function storeGoodsReceipt(Request $request, int $purchaseOrder): JsonResponse
    {
        $this->requirePermission($request, ['procurement.po.create', 'inventory.transaction.post']);
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'receiving_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'quarantine_location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', Rule::exists('purchase_order_items', 'id')],
            'items.*.received_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.accepted_qty' => ['required', 'numeric', 'min:0'],
            'items.*.rejected_qty' => ['required', 'numeric', 'min:0'],
            'items.*.actual_unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Verifikasi bahwa produk yang diterima berstatus fisik
        $poItems = DB::table('purchase_order_items')
            ->join('products', 'products.id', '=', 'purchase_order_items.product_id')
            ->where('purchase_order_items.purchase_order_id', $purchaseOrder)
            ->select('products.*')
            ->get();

        foreach ($poItems as $prod) {
            if ($prod->physical_type !== 'PHYSICAL') {
                throw new DomainException("Barang penerimaan [{$prod->code}] bukan produk dengan kategori fisik.", 'INVALID_GRN_PRODUCT', 422);
            }
        }

        /** @var User $user */
        $user = $request->user();
        $res = $this->procurement->receiveAndPostGrn($purchaseOrder, $data, $user, $idempotencyKey);

        return response()->json(['data' => $res], 201);
    }

    // --- Production Planning / MRP (Phase 5) & Execution (Phase 6) ---

    public function productionPlans(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.view');

        $plans = DB::table('production_plans')
            ->orderByDesc('id')
            ->get()
            ->map(function ($plan) {
                $plan->items = DB::table('production_plan_items')
                    ->join('products', 'products.id', '=', 'production_plan_items.product_id')
                    ->where('production_plan_id', $plan->id)
                    ->select('production_plan_items.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();
                $plan->latest_mrp = DB::table('production_mrp_runs')
                    ->where('production_plan_id', $plan->id)
                    ->orderByDesc('run_no')
                    ->first();
                $plan->stages = DB::table('production_plan_stages')
                    ->where('production_plan_id', $plan->id)
                    ->orderBy('sequence')
                    ->orderBy('id')
                    ->get();

                $targetQty = (float) DB::table('production_plan_items')
                    ->where('production_plan_id', $plan->id)
                    ->sum('target_qty');
                $plan->target_qty = $targetQty;

                $latestProduced = 0.0;
                foreach ($plan->stages as $stg) {
                    $stgProduced = (float) ($stg->produced_qty ?? 0);
                    $stg->produced_qty = $stgProduced;
                    $stgTarget = isset($stg->target_units) && (float) $stg->target_units > 0
                        ? (float) $stg->target_units
                        : $targetQty;

                    if ($stgProduced > 0 && $stgTarget > 0) {
                        if ($stgProduced < $stgTarget) {
                            $stg->output_category = 'KURANG';
                            $stg->output_variance = $stgProduced - $stgTarget;
                        } elseif ($stgProduced > $stgTarget) {
                            $stg->output_category = 'LEBIH';
                            $stg->output_variance = $stgProduced - $stgTarget;
                        } else {
                            $stg->output_category = 'SESUAI';
                            $stg->output_variance = 0.0;
                        }
                    } else {
                        $stg->output_category = $stg->output_category ?? null;
                        $stg->output_variance = null;
                    }

                    if ($stg->status === 'COMPLETED' && $stgProduced > 0) {
                        $latestProduced = $stgProduced;
                    } elseif ($latestProduced == 0 && $stgProduced > 0) {
                        $latestProduced = $stgProduced;
                    }
                }

                $plan->total_produced_qty = (float) $plan->stages->sum('produced_qty');
                $plan->produced_qty = $latestProduced > 0 ? $latestProduced : $plan->total_produced_qty;

                // Kategori output terhadap target produksi: KURANG, SESUAI, LEBIH
                if ($plan->produced_qty > 0 && $targetQty > 0) {
                    if ($plan->produced_qty < $targetQty) {
                        $plan->output_category = 'KURANG';
                        $plan->output_variance = $plan->produced_qty - $targetQty;
                    } elseif ($plan->produced_qty > $targetQty) {
                        $plan->output_category = 'LEBIH';
                        $plan->output_variance = $plan->produced_qty - $targetQty;
                    } else {
                        $plan->output_category = 'SESUAI';
                        $plan->output_variance = 0.0;
                    }
                } else {
                    $plan->output_category = null;
                    $plan->output_variance = null;
                }

                $completedWeight = $plan->stages->where('status', 'COMPLETED')->sum('weight_percentage');
                $plan->progress_percentage = min(100.0, (float) $completedWeight);
                $plan->total_stage_weight = (float) $plan->stages->sum('weight_percentage');

                // Rincian Kebutuhan Komponen & Estimasi Biaya Pengadaan
                $requiredComponents = [];
                $totalEstimatedBudget = 0.0;

                foreach ($plan->items as $item) {
                    $activeBom = $this->bom->findActiveVersionForProduct((int) $item->product_id);
                    if ($activeBom) {
                        try {
                            $exploded = $this->bom->explode($activeBom, (string) $item->target_qty);
                            foreach ($exploded as $comp) {
                                $compProdId = (int) $comp['product_id'];
                                $stockBalance = (float) DB::table('inventory_balances')
                                    ->where('product_id', $compProdId)
                                    ->where('stock_status', 'AVAILABLE')
                                    ->sum(DB::raw('on_hand_qty - reserved_qty'));

                                $grossQty = (float) $comp['gross_qty'];
                                $unitCost = (float) ($comp['estimated_unit_cost'] ?? 0);
                                $totalCost = (float) ($comp['estimated_total_cost'] ?? ($grossQty * $unitCost));

                                if (! isset($requiredComponents[$compProdId])) {
                                    $requiredComponents[$compProdId] = [
                                        'product_id' => $compProdId,
                                        'product_code' => $comp['product_code'],
                                        'product_name' => $comp['product_name'],
                                        'item_type' => $comp['item_type'],
                                        'uom_code' => $comp['uom_code'],
                                        'gross_qty' => $grossQty,
                                        'available_qty' => $stockBalance,
                                        'shortage_qty' => max(0, $grossQty - $stockBalance),
                                        'estimated_unit_cost' => $unitCost,
                                        'estimated_total_cost' => $totalCost,
                                    ];
                                } else {
                                    $requiredComponents[$compProdId]['gross_qty'] += $grossQty;
                                    $requiredComponents[$compProdId]['shortage_qty'] = max(0, $requiredComponents[$compProdId]['gross_qty'] - $requiredComponents[$compProdId]['available_qty']);
                                    $requiredComponents[$compProdId]['estimated_total_cost'] += $totalCost;
                                }
                                $totalEstimatedBudget += $totalCost;
                            }
                        } catch (\Throwable) {
                            // ignore cycle or unconfigured bom
                        }
                    }
                }

                $plan->required_components = array_values($requiredComponents);
                $plan->total_estimated_budget = $totalEstimatedBudget;

                return $plan;
            });

        return response()->json(['data' => $plans]);
    }

    public function storeProductionPlan(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.create');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'plan_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'priority' => ['sometimes', 'string', Rule::in(['LOW', 'NORMAL', 'HIGH', 'URGENT'])],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'project_name' => ['nullable', 'string', 'max:160'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.target_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();
        $planId = DB::transaction(function () use ($data, $user): int {
            $id = DB::table('production_plans')->insertGetId([
                'plan_no' => $this->numbering->next('PRODUCTION_PLAN'),
                'title' => $data['title'],
                'plan_date' => $data['plan_date'],
                'due_date' => $data['due_date'],
                'priority' => $data['priority'] ?? 'NORMAL',
                'status' => 'APPROVED',
                'customer_id' => $data['customer_id'] ?? null,
                'project_name' => $data['project_name'] ?? null,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                DB::table('production_plan_items')->insert([
                    'production_plan_id' => $id,
                    'product_id' => $item['product_id'],
                    'target_qty' => $item['target_qty'],
                    'uom_id' => $item['uom_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $id;
        });

        return response()->json(['data' => DB::table('production_plans')->where('id', $planId)->first()], 201);
    }

    public function runMrp(Request $request, int $productionPlan): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.create');

        return response()->json(['data' => $this->production->runMrp($productionPlan)], 201);
    }

    public function storeProductionPlanStage(Request $request, int $productionPlan): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.create');

        $plan = DB::table('production_plans')->where('id', $productionPlan)->first();
        if (! $plan) {
            throw new DomainException('Rencana produksi tidak ditemukan.', 'PLAN_NOT_FOUND', 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'weight_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'target_units' => ['nullable', 'numeric', 'min:0'],
            'planned_date' => ['nullable', 'date'],
            'produced_qty' => ['nullable', 'numeric', 'min:0'],
            'sequence' => ['sometimes', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $currentTotal = (float) DB::table('production_plan_stages')
            ->where('production_plan_id', $productionPlan)
            ->sum('weight_percentage');

        if (($currentTotal + (float) $data['weight_percentage']) > 100.01) {
            $remaining = max(0, 100.0 - $currentTotal);
            throw new DomainException(
                "Total bobot pengerjaan tidak boleh melebihi 100%. Sisa bobot yang tersedia: {$remaining}%.",
                'STAGE_WEIGHT_EXCEEDED',
                422
            );
        }

        $maxSeq = (int) DB::table('production_plan_stages')
            ->where('production_plan_id', $productionPlan)
            ->max('sequence');

        $targetQty = (float) DB::table('production_plan_items')
            ->where('production_plan_id', $productionPlan)
            ->sum('target_qty');

        $targetUnits = isset($data['target_units']) && $data['target_units'] !== '' ? (float) $data['target_units'] : null;
        $referenceTarget = ($targetUnits !== null && $targetUnits > 0) ? $targetUnits : $targetQty;

        $producedQty = isset($data['produced_qty']) ? (float) $data['produced_qty'] : 0.0;
        $outputCat = null;
        if ($producedQty > 0 && $referenceTarget > 0) {
            if ($producedQty < $referenceTarget) {
                $outputCat = 'KURANG';
            } elseif ($producedQty > $referenceTarget) {
                $outputCat = 'LEBIH';
            } else {
                $outputCat = 'SESUAI';
            }
        }

        $stageId = DB::table('production_plan_stages')->insertGetId([
            'production_plan_id' => $productionPlan,
            'sequence' => $data['sequence'] ?? ($maxSeq + 1),
            'name' => $data['name'],
            'weight_percentage' => $data['weight_percentage'],
            'target_units' => $targetUnits,
            'planned_date' => $data['planned_date'] ?? null,
            'produced_qty' => $producedQty,
            'output_category' => $outputCat,
            'status' => 'PENDING',
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stage = DB::table('production_plan_stages')->where('id', $stageId)->first();

        return response()->json(['data' => $stage], 201);
    }

    public function updateProductionPlanStage(Request $request, int $stageId): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.create');

        $stage = DB::table('production_plan_stages')->where('id', $stageId)->first();
        if (! $stage) {
            throw new DomainException('Tahap pengerjaan tidak ditemukan.', 'STAGE_NOT_FOUND', 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'weight_percentage' => ['sometimes', 'numeric', 'min:0.01', 'max:100'],
            'target_units' => ['nullable', 'numeric', 'min:0'],
            'planned_date' => ['nullable', 'date'],
            'produced_qty' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['PENDING', 'IN_PROGRESS', 'COMPLETED'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['weight_percentage'])) {
            $currentOtherTotal = (float) DB::table('production_plan_stages')
                ->where('production_plan_id', $stage->production_plan_id)
                ->where('id', '!=', $stageId)
                ->sum('weight_percentage');

            if (($currentOtherTotal + (float) $data['weight_percentage']) > 100.01) {
                $remaining = max(0, 100.0 - $currentOtherTotal);
                throw new DomainException(
                    "Total bobot pengerjaan tidak boleh melebihi 100%. Sisa bobot yang tersedia: {$remaining}%.",
                    'STAGE_WEIGHT_EXCEEDED',
                    422
                );
            }
        }

        $updatePayload = [
            'updated_at' => now(),
        ];

        if (isset($data['name'])) $updatePayload['name'] = $data['name'];
        if (isset($data['weight_percentage'])) $updatePayload['weight_percentage'] = $data['weight_percentage'];
        if (isset($data['notes'])) $updatePayload['notes'] = $data['notes'];
        if (array_key_exists('target_units', $data)) {
            $updatePayload['target_units'] = $data['target_units'] !== null ? (float) $data['target_units'] : null;
        }
        if (array_key_exists('planned_date', $data)) {
            $updatePayload['planned_date'] = $data['planned_date'];
        }

        if (isset($data['produced_qty']) || array_key_exists('target_units', $data)) {
            $pQty = isset($data['produced_qty']) ? (float) $data['produced_qty'] : (float) ($stage->produced_qty ?? 0);
            $updatePayload['produced_qty'] = $pQty;

            $stgTarget = array_key_exists('target_units', $data)
                ? ($data['target_units'] !== null ? (float) $data['target_units'] : null)
                : (isset($stage->target_units) ? (float) $stage->target_units : null);

            $targetQty = (float) DB::table('production_plan_items')
                ->where('production_plan_id', $stage->production_plan_id)
                ->sum('target_qty');

            $referenceTarget = ($stgTarget !== null && $stgTarget > 0) ? $stgTarget : $targetQty;

            if ($pQty > 0 && $referenceTarget > 0) {
                if ($pQty < $referenceTarget) {
                    $updatePayload['output_category'] = 'KURANG';
                } elseif ($pQty > $referenceTarget) {
                    $updatePayload['output_category'] = 'LEBIH';
                } else {
                    $updatePayload['output_category'] = 'SESUAI';
                }
            } else {
                $updatePayload['output_category'] = null;
            }
        }

        if (isset($data['status'])) {
            $updatePayload['status'] = $data['status'];
            if ($data['status'] === 'IN_PROGRESS' && ! $stage->started_at) {
                $updatePayload['started_at'] = now();
            } elseif ($data['status'] === 'COMPLETED') {
                if (! $stage->started_at) {
                    $updatePayload['started_at'] = now();
                }
                $updatePayload['completed_at'] = now();
            }
        }

        DB::table('production_plan_stages')->where('id', $stageId)->update($updatePayload);

        return response()->json(['data' => DB::table('production_plan_stages')->where('id', $stageId)->first()]);
    }

    public function destroyProductionPlanStage(Request $request, int $stageId): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.create');

        $stage = DB::table('production_plan_stages')->where('id', $stageId)->first();
        if (! $stage) {
            throw new DomainException('Tahap pengerjaan tidak ditemukan.', 'STAGE_NOT_FOUND', 404);
        }

        // Aturan bisnis: Jika sudah selesai (COMPLETED), tidak dapat lagi dihapus
        if ($stage->status === 'COMPLETED') {
            throw new DomainException(
                'Tahap pengerjaan yang sudah selesai (COMPLETED) tidak dapat lagi dihapus.',
                'COMPLETED_STAGE_CANNOT_BE_DELETED',
                422
            );
        }

        DB::table('production_plan_stages')->where('id', $stageId)->delete();

        return response()->json(['message' => 'Tahap pengerjaan berhasil dihapus.']);
    }

    public function productionOrders(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'production.work_order.view');

        $orders = DB::table('production_orders')
            ->join('products', 'products.id', '=', 'production_orders.product_id')
            ->leftJoin('production_cost_summaries', 'production_cost_summaries.production_order_id', '=', 'production_orders.id')
            ->select(
                'production_orders.*',
                'products.code as product_code',
                'products.name as product_name',
                'production_cost_summaries.total_material_cost',
                'production_cost_summaries.total_good_qty',
                'production_cost_summaries.total_reject_qty',
                'production_cost_summaries.material_cost_per_good_unit',
            )
            ->orderByDesc('production_orders.id')
            ->get()
            ->map(function ($order) {
                $order->work_orders = DB::table('work_orders')
                    ->where('production_order_id', $order->id)
                    ->orderBy('sequence')
                    ->get();

                return $order;
            });

        return response()->json(['data' => $orders]);
    }

    public function releaseProductionOrder(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'production.plan.approve');
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'target_qty' => ['required', 'numeric', 'gt:0'],
            'production_plan_item_id' => ['nullable', 'integer', Rule::exists('production_plan_items', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();
        $res = $this->production->releaseProductionOrder(
            (int) $data['product_id'],
            (string) $data['target_qty'],
            $user,
            isset($data['production_plan_item_id']) ? (int) $data['production_plan_item_id'] : null,
        );

        return response()->json(['data' => $res], 201);
    }

    public function issueWorkOrderMaterials(Request $request, int $workOrder): JsonResponse
    {
        $this->requirePermission($request, ['production.work_order.execute', 'inventory.transaction.post']);
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->production->issueMaterials($workOrder, $data['items'], $user, $idempotencyKey),
        ], 201);
    }

    public function returnUnusedMaterial(Request $request, int $materialIssueItem): JsonResponse
    {
        $this->requirePermission($request, ['production.work_order.execute', 'inventory.transaction.post']);
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->production->returnUnusedMaterial($materialIssueItem, (string) $data['quantity'], $user, $idempotencyKey),
        ]);
    }

    public function recordWorkOrderResult(Request $request, int $workOrder): JsonResponse
    {
        $this->requirePermission($request, 'production.work_order.execute');
        $data = $request->validate([
            'input_qty' => ['required', 'numeric', 'gt:0'],
            'good_qty' => ['required', 'numeric', 'min:0'],
            'reject_qty' => ['sometimes', 'numeric', 'min:0'],
            'scrap_qty' => ['sometimes', 'numeric', 'min:0'],
            'wip_remaining_qty' => ['sometimes', 'numeric', 'min:0'],
            'approved_process_loss_qty' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->production->recordStageResult($workOrder, $data, $user)], 201);
    }

    // --- Vendor / GAP (Phase 7) ---

    public function vendorOrders(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'vendor.view');

        $orders = DB::table('vendor_orders')
            ->join('vendors', 'vendors.id', '=', 'vendor_orders.vendor_id')
            ->select('vendor_orders.*', 'vendors.name as vendor_name', 'vendors.code as vendor_code')
            ->orderByDesc('vendor_orders.id')
            ->get()
            ->map(function ($vo) {
                $vo->items = DB::table('vendor_order_items')
                    ->join('products', 'products.id', '=', 'vendor_order_items.product_id')
                    ->where('vendor_order_id', $vo->id)
                    ->select('vendor_order_items.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $vo;
            });

        return response()->json(['data' => $orders]);
    }

    public function storeVendorOrder(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'vendor.manage');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'source_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'vendor_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'expected_return_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.sent_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->vendor->createAndDispatch($data, $user, $idempotencyKey)], 201);
    }

    public function receiveVendorOrder(Request $request, int $vendorOrder): JsonResponse
    {
        $this->requirePermission($request, 'vendor.manage');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'receiving_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vendor_order_item_id' => ['required', 'integer', Rule::exists('vendor_order_items', 'id')],
            'items.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.accepted_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.damaged_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->vendor->receiveAndInspect($vendorOrder, $data, $user, $idempotencyKey),
        ], 201);
    }

    // --- Rental & QC (Phase 7–8) ---

    public function rentalOrders(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.view');

        $fgStockMap = DB::table('inventory_balances')
            ->where('stock_status', 'AVAILABLE')
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(on_hand_qty - reserved_qty) as available_qty'))
            ->pluck('available_qty', 'product_id');

        $orders = DB::table('rental_orders')
            ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
            ->leftJoin('products', 'products.id', '=', 'rental_orders.product_id')
            ->leftJoin('production_plans', 'production_plans.id', '=', 'rental_orders.production_plan_id')
            ->select(
                'rental_orders.*',
                'customers.name as customer_name',
                'customers.code as customer_code',
                'customers.school_name',
                'products.name as product_name',
                'products.code as product_code',
                'production_plans.plan_no as production_plan_no',
                'production_plans.title as production_plan_title'
            )
            ->orderByDesc('rental_orders.id')
            ->get()
            ->map(function ($ro) use ($fgStockMap) {
                $ro->unit_codes = $ro->unit_codes_json ? json_decode($ro->unit_codes_json, true) : [];
                $avail = (float) ($fgStockMap[$ro->product_id] ?? 0);
                $ro->available_fg_stock = $avail;
                $totUnits = (float) ($ro->total_units ?? 1);
                $ro->is_stock_shortage = $avail < $totUnits;
                $ro->shortage_qty = max(0, $totUnits - $avail);
                $ro->items = DB::table('rental_order_items')
                    ->join('products', 'products.id', '=', 'rental_order_items.product_id')
                    ->leftJoin('product_serials', 'product_serials.id', '=', 'rental_order_items.product_serial_id')
                    ->where('rental_order_id', $ro->id)
                    ->select('rental_order_items.*', 'products.code as product_code', 'products.name as product_name', 'product_serials.serial_number')
                    ->get();

                return $ro;
            });

        return response()->json(['data' => $orders]);
    }

    public function storeRentalOrder(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');

        // Alur Sewa Sekolah / Finished Good (tidak lagi mengacu pada rencana produksi)
        $isSchoolRental = $request->input('order_type') === 'SCHOOL'
            || $request->has('production_plan_id')
            || (! $request->has('product_serial_id') && ! $request->has('expected_contents'));

        if ($isSchoolRental) {
            $data = $request->validate([
                'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
                'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
                'total_units' => ['nullable', 'numeric', 'min:1'],
                'production_plan_id' => ['nullable', 'integer', Rule::exists('production_plans', 'id')],
                'start_date' => ['required', 'date'],
                'notes' => ['nullable', 'string'],
                'start_unit_no' => ['nullable', 'integer', 'min:1'],
            ]);

            $plan = null;
            if (! empty($data['production_plan_id'])) {
                $plan = DB::table('production_plans')->where('id', $data['production_plan_id'])->first();
            }

            // Menentukan produk Finished Good yang dipesan
            $productId = null;
            if (! empty($data['product_id'])) {
                $productId = (int) $data['product_id'];
            } elseif ($plan) {
                $planItem = DB::table('production_plan_items')
                    ->where('production_plan_id', $plan->id)
                    ->first();
                $productId = $planItem ? (int) $planItem->product_id : null;
            }

            if (! $productId) {
                $productId = (int) (DB::table('products')->where('code', '1001')->value('id') ?? DB::table('products')->value('id'));
            }

            // Menentukan kuantitas / banyak produk yang ingin dipesan sekolah
            $targetQty = ! empty($data['total_units']) ? (int) $data['total_units'] : null;
            if (! $targetQty && $plan) {
                $planItem = DB::table('production_plan_items')
                    ->where('production_plan_id', $plan->id)
                    ->first();
                $targetQty = $planItem ? (int) $planItem->target_qty : 10;
            }
            if (! $targetQty || $targetQty < 1) {
                $targetQty = 10;
            }

            $product = DB::table('products')->where('id', $productId)->first();
            if (! $product) {
                throw new DomainException('Produk Finished Good tidak ditemukan.', 'PRODUCT_NOT_FOUND', 404);
            }
            if ($product->physical_type !== 'PHYSICAL') {
                throw new DomainException("Hanya produk Finished Good fisik yang dapat disewakan.", 'INVALID_RENTAL_PRODUCT', 422);
            }

            $productCode = $product->code ?: '1001';

            // Verifikasi stok Finished Good yang tersedia di gudang
            $fgAvailableStock = (float) DB::table('inventory_balances')
                ->where('product_id', $productId)
                ->where('stock_status', 'AVAILABLE')
                ->sum(DB::raw('on_hand_qty - reserved_qty'));

            $isShortage = $fgAvailableStock < $targetQty;
            $shortageQty = max(0, $targetQty - $fgAvailableStock);
            $stockNotice = $isShortage
                ? "Keterangan: Stok Finished Good [{$productCode}] di gudang kurang (Tersedia: {$fgAvailableStock} unit, Dipesan: {$targetQty} unit, Kurang: {$shortageQty} unit)."
                : "Stok Finished Good [{$productCode}] di gudang mencukupi (Tersedia: {$fgAvailableStock} unit).";

            $finalNotes = $data['notes'] ?? '';
            if ($isShortage) {
                $finalNotes = $finalNotes ? ($finalNotes . ' | ' . $stockNotice) : $stockNotice;
            }

            // Hitung nomor unit serial sekuensial per produk
            if (! empty($data['start_unit_no']) && (int) $data['start_unit_no'] > 0) {
                $startUnit = (int) $data['start_unit_no'];
                $endUnit = $startUnit + $targetQty - 1;
            } else {
                $lastEndUnit = (int) (DB::table('rental_orders')->where('product_id', $productId)->max('end_unit_no') ?? 0);
                $startUnit = $lastEndUnit + 1;
                $endUnit = $lastEndUnit + $targetQty;
            }

            $unitCodes = [];
            for ($i = $startUnit; $i <= $endUnit; $i++) {
                $unitCodes[] = sprintf('%s_%02d', $productCode, $i);
            }

            $rentalNo = sprintf('RNT-SCH-%s-%04d', date('Ym'), DB::table('rental_orders')->count() + 1);

            $id = DB::table('rental_orders')->insertGetId([
                'rental_no' => $rentalNo,
                'transaction_kind' => 'RENTAL',
                'order_type' => 'SCHOOL',
                'customer_id' => $data['customer_id'],
                'production_plan_id' => $data['production_plan_id'] ?? null,
                'product_id' => $productId,
                'start_date' => $data['start_date'],
                'expected_return_date' => date('Y-m-d', strtotime($data['start_date'] . ' + 90 days')),
                'status' => 'ACTIVE',
                'notes' => $finalNotes ?: null,
                'barcode_prefix' => $productCode,
                'start_unit_no' => $startUnit,
                'end_unit_no' => $endUnit,
                'total_units' => $targetQty,
                'unit_codes_json' => json_encode($unitCodes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created = DB::table('rental_orders')
                ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
                ->leftJoin('products', 'products.id', '=', 'rental_orders.product_id')
                ->leftJoin('production_plans', 'production_plans.id', '=', 'rental_orders.production_plan_id')
                ->where('rental_orders.id', $id)
                ->select(
                    'rental_orders.*',
                    'customers.name as customer_name',
                    'customers.code as customer_code',
                    'products.name as product_name',
                    'products.code as product_code',
                    'production_plans.plan_no as production_plan_no',
                    'production_plans.title as production_plan_title'
                )
                ->first();

            $created->unit_codes = $unitCodes;
            $created->available_fg_stock = $fgAvailableStock;
            $created->fg_stock_sufficient = ! $isShortage;
            $created->is_stock_shortage = $isShortage;
            $created->shortage_qty = $shortageQty;
            $created->stock_notice = $stockNotice;

            return response()->json(['data' => $created], 201);
        }

        // Alur generic rental legacy
        $data = $request->validate([
            'transaction_kind' => ['sometimes', 'string', Rule::in(['RENTAL', 'LOAN'])],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'project_name' => ['nullable', 'string', 'max:160'],
            'group_leader' => ['nullable', 'string', 'max:120'],
            'sales_pic' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:start_date'],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'product_serial_id' => ['required', 'integer', Rule::exists('product_serials', 'id')],
            'source_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'expected_contents' => ['required', 'array', 'min:1'],
            'expected_contents.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'expected_contents.*.expected_qty' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->rental->createAndReserve($data, $user)], 201);
    }

    public function dispatchRental(Request $request, int $rentalOrder): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');
        $idempotencyKey = $this->requireIdempotencyHeader($request);
        $data = $request->validate(['reservation_id' => ['required', 'integer', Rule::exists('inventory_reservations', 'id')]]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->rental->dispatch($rentalOrder, (int) $data['reservation_id'], $user, $idempotencyKey),
        ]);
    }

    public function returnRental(Request $request, int $rentalOrder): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');
        $idempotencyKey = $this->requireIdempotencyHeader($request);
        $data = $request->validate(['return_location_id' => ['required', 'integer', Rule::exists('locations', 'id')]]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->rental->receiveReturn($rentalOrder, (int) $data['return_location_id'], $user, $idempotencyKey),
        ], 201);
    }

    public function inspectRentalReturn(Request $request, int $rentalReturn): JsonResponse
    {
        $this->requirePermission($request, ['rental.return.inspect', 'qc.perform']);
        $idempotencyKey = $this->requireIdempotencyHeader($request);
        $data = $request->validate([
            'contents' => ['required', 'array', 'min:1'],
            'contents.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'contents.*.actual_qty' => ['required', 'numeric', 'min:0'],
            'contents.*.damaged_qty' => ['sometimes', 'numeric', 'min:0'],
        ]);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->rental->inspectReturn($rentalReturn, $data['contents'], $user, $idempotencyKey),
        ]);
    }

    public function generateRentalDeliveryOrder(Request $request, int $rentalOrder): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');

        $rental = DB::table('rental_orders')
            ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
            ->leftJoin('products', 'products.id', '=', 'rental_orders.product_id')
            ->leftJoin('production_plans', 'production_plans.id', '=', 'rental_orders.production_plan_id')
            ->where('rental_orders.id', $rentalOrder)
            ->select(
                'rental_orders.*',
                'customers.name as customer_name',
                'customers.code as customer_code',
                'customers.school_name',
                'customers.address_json as customer_address_json',
                'customers.phone as customer_phone',
                'products.name as product_name',
                'products.code as product_code',
                'production_plans.plan_no as production_plan_no',
                'production_plans.title as production_plan_title'
            )
            ->first();

        if (! $rental) {
            throw new DomainException('Data sewa rental tidak ditemukan.', 'RENTAL_NOT_FOUND', 404);
        }

        $addressStr = '';
        if (! empty($rental->customer_address_json)) {
            $addr = json_decode($rental->customer_address_json, true);
            if (is_array($addr)) {
                $addressStr = implode(', ', array_filter([$addr['street'] ?? null, $addr['city'] ?? null, $addr['province'] ?? null, $addr['postal_code'] ?? null]));
            } elseif (is_string($addr)) {
                $addressStr = $addr;
            }
        }
        $rental->customer_address = $addressStr;

        $recipientName = $rental->school_name ?: $rental->customer_name;

        // Cari apakah shipment untuk rental order ini sudah pernah dibuat sebelumnya
        $shipment = DB::table('shipments')
            ->where('source_document_type', 'rental_orders')
            ->where('source_document_id', $rental->id)
            ->first();

        if (! $shipment) {
            $sourceLocationId = DB::table('locations')->where('type', 'RACK')->value('id')
                ?? DB::table('locations')->value('id');

            $doNo = sprintf('DO-%s-%04d', date('Ym'), DB::table('shipments')->count() + 1);

            $rangeStr = '';
            if ($rental->barcode_prefix && $rental->start_unit_no && $rental->end_unit_no) {
                $rangeStr = sprintf('%s_%02d s/d %s_%02d (%d Unit)', 
                    $rental->barcode_prefix, 
                    $rental->start_unit_no, 
                    $rental->barcode_prefix, 
                    $rental->end_unit_no,
                    $rental->total_units ?? 1
                );
            }

            $shipmentId = DB::table('shipments')->insertGetId([
                'shipment_no' => $doNo,
                'shipment_type' => 'RENTAL',
                'source_location_id' => $sourceLocationId,
                'destination_location_id' => null,
                'source_document_type' => 'rental_orders',
                'source_document_id' => $rental->id,
                'status' => 'IN_TRANSIT',
                'dispatched_at' => now(),
                'tracking_reference' => $rental->rental_no,
                'recipient_name' => $recipientName,
                'notes' => 'Pengiriman Sewa MLK Sekolah' . ($rangeStr ? ' - Unit: ' . $rangeStr : ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productId = $rental->product_id ?: (int) (DB::table('products')->where('code', '1001')->value('id') ?? DB::table('products')->value('id'));

            DB::table('shipment_items')->insert([
                'shipment_id' => $shipmentId,
                'product_id' => $productId,
                'quantity' => $rental->total_units ?: 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $shipment = DB::table('shipments')->where('id', $shipmentId)->first();
        }

        $items = DB::table('shipment_items')
            ->join('products', 'products.id', '=', 'shipment_items.product_id')
            ->where('shipment_id', $shipment->id)
            ->select('shipment_items.*', 'products.code as product_code', 'products.name as product_name')
            ->get();

        $rental->unit_codes = ! empty($rental->unit_codes_json) ? json_decode($rental->unit_codes_json, true) : [];

        return response()->json([
            'data' => [
                'shipment' => $shipment,
                'rental' => $rental,
                'items' => $items,
            ],
            'message' => 'Surat Delivery Order (DO) berhasil diterbitkan dan otomatis ditambahkan ke halaman Distribusi.',
        ], 200);
    }

    public function updateRentalOrderStatus(Request $request, int $rentalOrder): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'DISPATCHED', 'RETURNED', 'CLOSED', 'CANCELLED'])],
        ]);

        DB::table('rental_orders')->where('id', $rentalOrder)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        $updated = DB::table('rental_orders')->where('id', $rentalOrder)->first();

        return response()->json([
            'data' => $updated,
            'message' => 'Status sewa sekolah berhasil diperbarui.',
        ]);
    }

    public function schoolRentalUnits(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.view');

        $orders = DB::table('rental_orders')
            ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
            ->leftJoin('products', 'products.id', '=', 'rental_orders.product_id')
            ->where('rental_orders.order_type', 'SCHOOL')
            ->select(
                'rental_orders.*',
                'customers.name as customer_name',
                'customers.code as customer_code',
                'customers.school_name',
                'products.name as product_name',
                'products.code as product_code'
            )
            ->orderBy('rental_orders.id')
            ->get();

        $inspections = DB::table('quality_inspections')
            ->join('users', 'users.id', '=', 'quality_inspections.inspected_by')
            ->leftJoin('products', 'products.id', '=', 'quality_inspections.product_id')
            ->where('quality_inspections.inspection_type', 'RENTAL_RETURN_SCHOOL')
            ->select(
                'quality_inspections.*',
                'users.name as inspector_name',
                'products.name as product_name',
                'products.code as product_code'
            )
            ->orderByDesc('quality_inspections.inspected_at')
            ->get()
            ->map(function ($qi) {
                $qi->discrepancies = DB::table('quality_discrepancies')
                    ->join('products', 'products.id', '=', 'quality_discrepancies.product_id')
                    ->where('quality_inspection_id', $qi->id)
                    ->select('quality_discrepancies.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $qi;
            });

        $unitMap = [];
        foreach ($orders as $order) {
            $prefix = $order->barcode_prefix ?: ($order->product_code ?: '1001');
            $start = $order->start_unit_no ?: 1;
            $end = $order->end_unit_no ?: ($start + ($order->total_units ?: 1) - 1);
            $schoolName = $order->school_name ?: $order->customer_name;

            for ($u = $start; $u <= $end; $u++) {
                $code = sprintf('%s_%02d', $prefix, $u);

                if (! isset($unitMap[$code])) {
                    $unitMap[$code] = [
                        'unit_code' => $code,
                        'unit_no' => $u,
                        'product_name' => $order->product_name ?: 'Mobile Learning Kit (MLK)',
                        'product_code' => $prefix,
                        'current_status' => 'AVAILABLE',
                        'current_school' => null,
                        'current_rental_no' => null,
                        'current_start_date' => null,
                        'rental_history' => [],
                        'inspection_history' => [],
                    ];
                }

                $historyEntry = [
                    'rental_id' => $order->id,
                    'rental_no' => $order->rental_no,
                    'school_name' => $schoolName,
                    'start_date' => $order->start_date,
                    'expected_return_date' => $order->expected_return_date,
                    'status' => $order->status,
                ];

                $unitMap[$code]['rental_history'][] = $historyEntry;

                // Jika status order ACTIVE atau DISPATCHED, unit sedang aktif dipakai di sekolah ini
                if (in_array($order->status, ['ACTIVE', 'DISPATCHED'], true)) {
                    $unitMap[$code]['current_status'] = 'IN_USE';
                    $unitMap[$code]['current_school'] = $schoolName;
                    $unitMap[$code]['current_rental_no'] = $order->rental_no;
                    $unitMap[$code]['current_start_date'] = $order->start_date;
                }
            }
        }

        // Tautkan riwayat inspeksi ke unit yang sesuai
        foreach ($unitMap as $code => &$uData) {
            foreach ($inspections as $ins) {
                $notes = $ins->notes ?? '';
                $matchesSchool = $ins->customer_name && collect($uData['rental_history'])->contains('school_name', $ins->customer_name);
                $matchesBatch = false;
                foreach ($uData['rental_history'] as $rh) {
                    if (str_contains($notes, $rh['rental_no'])) {
                        $matchesBatch = true;
                        break;
                    }
                }

                if ($matchesBatch || $matchesSchool) {
                    $uData['inspection_history'][] = [
                        'inspection_no' => $ins->inspection_no,
                        'inspected_at' => $ins->inspected_at,
                        'customer_name' => $ins->customer_name,
                        'status' => $ins->status,
                        'total_claim_amount' => $ins->total_claim_amount,
                        'discrepancies_count' => count($ins->discrepancies),
                        'notes' => $ins->notes,
                    ];
                }
            }
        }
        unset($uData);

        return response()->json([
            'data' => [
                'units' => array_values($unitMap),
                'inspections' => $inspections,
            ],
        ]);
    }

    public function scanSchoolRentalUnit(Request $request, string $unitCode): JsonResponse
    {
        $this->requirePermission($request, 'rental.view');

        $unitCode = trim($unitCode);

        $prefix = '1001';
        $unitNo = null;

        if (preg_match('/^([A-Za-z0-9\.\-]+)_(\d+)$/', $unitCode, $matches)) {
            $prefix = $matches[1];
            $unitNo = (int) $matches[2];
        } elseif (is_numeric($unitCode)) {
            $unitNo = (int) $unitCode;
            $unitCode = sprintf('1001_%02d', $unitNo);
        }

        $orders = DB::table('rental_orders')
            ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
            ->leftJoin('products', 'products.id', '=', 'rental_orders.product_id')
            ->where('rental_orders.order_type', 'SCHOOL')
            ->where(function ($q) use ($unitCode, $prefix, $unitNo) {
                $q->where('unit_codes_json', 'like', "%{$unitCode}%");
                if ($unitNo !== null) {
                    $q->orWhere(function ($sub) use ($prefix, $unitNo) {
                        $sub->where('barcode_prefix', $prefix)
                            ->where('start_unit_no', '<=', $unitNo)
                            ->where('end_unit_no', '>=', $unitNo);
                    });
                }
            })
            ->select(
                'rental_orders.*',
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.school_name',
                'customers.code as customer_code',
                'products.name as product_name',
                'products.code as product_code'
            )
            ->orderByDesc('rental_orders.id')
            ->get();

        if ($orders->isEmpty()) {
            $product = DB::table('products')->where('code', $prefix)->first();
            if (! $product) {
                $product = DB::table('products')->first();
            }

            return response()->json([
                'data' => [
                    'unit_code' => $unitCode,
                    'unit_no' => $unitNo ?: 1,
                    'product_name' => $product ? $product->name : 'Mobile Learning Kit (MLK) Standar',
                    'product_code' => $product ? $product->code : $prefix,
                    'current_status' => 'AVAILABLE',
                    'current_school' => null,
                    'current_rental' => null,
                    'rental_history' => [],
                    'inspection_history' => [],
                ],
            ]);
        }

        $latestOrder = $orders->first();
        $productName = $latestOrder->product_name ?: 'Mobile Learning Kit (MLK) Standar';
        $productCode = $latestOrder->product_code ?: $prefix;

        $activeOrder = $orders->first(fn ($o) => in_array($o->status, ['ACTIVE', 'DISPATCHED'], true)) ?? $latestOrder;
        $currentStatus = in_array($activeOrder->status, ['ACTIVE', 'DISPATCHED'], true) ? 'ACTIVE' : 'RETURNED';
        $currentSchool = $activeOrder->school_name ?: $activeOrder->customer_name;

        $rentalHistory = $orders->map(function ($o) {
            return [
                'rental_id' => $o->id,
                'rental_no' => $o->rental_no,
                'customer_id' => $o->customer_id,
                'school_name' => $o->school_name ?: $o->customer_name,
                'start_date' => $o->start_date,
                'expected_return_date' => $o->expected_return_date,
                'status' => $o->status,
            ];
        })->values();

        $schoolNames = $orders->pluck('school_name')->merge($orders->pluck('customer_name'))->filter()->unique();
        $rentalNos = $orders->pluck('rental_no')->filter()->unique();

        $inspections = DB::table('quality_inspections')
            ->join('users', 'users.id', '=', 'quality_inspections.inspected_by')
            ->where('quality_inspections.inspection_type', 'RENTAL_RETURN_SCHOOL')
            ->where(function ($q) use ($schoolNames, $rentalNos) {
                $q->whereIn('quality_inspections.customer_name', $schoolNames);
                foreach ($rentalNos as $rNo) {
                    $q->orWhere('quality_inspections.notes', 'like', "%{$rNo}%");
                }
            })
            ->select('quality_inspections.*', 'users.name as inspector_name')
            ->orderByDesc('quality_inspections.inspected_at')
            ->get()
            ->map(function ($qi) {
                $qi->discrepancies = DB::table('quality_discrepancies')
                    ->join('products', 'products.id', '=', 'quality_discrepancies.product_id')
                    ->where('quality_inspection_id', $qi->id)
                    ->select('quality_discrepancies.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();
                return $qi;
            });

        return response()->json([
            'data' => [
                'unit_code' => $unitCode,
                'unit_no' => $unitNo ?: ($activeOrder->start_unit_no ?: 1),
                'product_name' => $productName,
                'product_code' => $productCode,
                'current_status' => $currentStatus,
                'current_school' => $currentSchool,
                'current_rental' => [
                    'id' => $activeOrder->id,
                    'rental_no' => $activeOrder->rental_no,
                    'customer_id' => $activeOrder->customer_id,
                    'customer_name' => $activeOrder->customer_name,
                    'school_name' => $activeOrder->school_name ?: $activeOrder->customer_name,
                    'start_date' => $activeOrder->start_date,
                    'status' => $activeOrder->status,
                ],
                'rental_history' => $rentalHistory,
                'inspection_history' => $inspections,
            ],
        ]);
    }

    public function updateUnitAssignment(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');

        $data = $request->validate([
            'rental_id' => ['required', 'integer', Rule::exists('rental_orders', 'id')],
            'status' => ['required', 'string', Rule::in(['ACTIVE', 'DISPATCHED', 'RETURNED', 'CLOSED'])],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
        ]);

        $updateData = [
            'status' => $data['status'],
            'updated_at' => now(),
        ];

        if (! empty($data['customer_id'])) {
            $updateData['customer_id'] = $data['customer_id'];
        }

        DB::table('rental_orders')->where('id', $data['rental_id'])->update($updateData);

        $updatedRental = DB::table('rental_orders')
            ->join('customers', 'customers.id', '=', 'rental_orders.customer_id')
            ->where('rental_orders.id', $data['rental_id'])
            ->select('rental_orders.*', 'customers.name as customer_name', 'customers.school_name')
            ->first();

        return response()->json([
            'data' => $updatedRental,
            'message' => 'Data sekolah dan status sewa unit berhasil diperbarui.',
        ]);
    }

    public function qualityInspections(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'qc.view');

        $inspections = DB::table('quality_inspections')
            ->leftJoin('products', 'products.id', '=', 'quality_inspections.product_id')
            ->join('users', 'users.id', '=', 'quality_inspections.inspected_by')
            ->select('quality_inspections.*', 'products.code as product_code', 'products.name as product_name', 'users.name as inspector_name')
            ->orderByDesc('quality_inspections.id')
            ->get()
            ->map(function ($qi) {
                $qi->discrepancies = DB::table('quality_discrepancies')
                    ->join('products', 'products.id', '=', 'quality_discrepancies.product_id')
                    ->where('quality_inspection_id', $qi->id)
                    ->select('quality_discrepancies.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $qi;
            });

        return response()->json(['data' => $inspections]);
    }

    /**
     * Mengambil template checklist kelengkapan (Box, Modul, Komponen) dari resep BOM Finished Good.
     */
    public function qcChecklistTemplate(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'qc.view');

        $activeBom = $this->bom->findActiveVersionForProduct($product->id);
        $checklist = [];

        if ($activeBom) {
            $activeBom->loadMissing(['items.component.category', 'items.uom']);
            foreach ($activeBom->items as $item) {
                $comp = $item->component;
                $tier = 'COMPONENT';
                if ($comp->category?->code === 'WIP-BOX') {
                    $tier = 'BOX';
                } elseif ($comp->category?->code === 'WIP-MOD') {
                    $tier = 'MODULE';
                }

                $checklist[] = [
                    'product_id' => $comp->id,
                    'code' => $comp->code,
                    'name' => $comp->name,
                    'tier' => $tier,
                    'category_name' => $comp->category?->name ?? 'Komponen',
                    'std_qty_per_unit' => (float) $item->quantity,
                    'uom' => $item->uom?->code ?? 'Unit',
                    'reference_purchase_price' => (float) ($comp->reference_purchase_price ?? 50000),
                ];

                // Jika modul, ledakkan sub-komponennya jika ada resep
                if ($tier === 'MODULE') {
                    $modBom = $this->bom->findActiveVersionForProduct($comp->id);
                    if ($modBom) {
                        $modBom->loadMissing(['items.component.category', 'items.uom']);
                        foreach ($modBom->items as $sub) {
                            $subComp = $sub->component;
                            $checklist[] = [
                                'product_id' => $subComp->id,
                                'code' => $subComp->code,
                                'name' => "↳ [{$comp->name}] " . $subComp->name,
                                'tier' => 'COMPONENT',
                                'category_name' => $subComp->category?->name ?? 'Komponen',
                                'std_qty_per_unit' => (float) bcmul((string) $item->quantity, (string) $sub->quantity, 4),
                                'uom' => $sub->uom?->code ?? 'Pcs',
                                'reference_purchase_price' => (float) ($subComp->reference_purchase_price ?? 15000),
                            ];
                        }
                    }
                }
            }
        }

        // Fallback jika belum ada BOM bertingkat
        if (empty($checklist)) {
            $components = Product::query()
                ->whereIn('category_id', function ($q) {
                    $q->select('id')->from('product_categories')->whereIn('code', ['WIP-BOX', 'WIP-MOD', 'COMP']);
                })
                ->limit(10)
                ->get();

            foreach ($components as $c) {
                $tier = $c->category?->code === 'WIP-BOX' ? 'BOX' : ($c->category?->code === 'WIP-MOD' ? 'MODULE' : 'COMPONENT');
                $checklist[] = [
                    'product_id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'tier' => $tier,
                    'category_name' => $c->category?->name ?? 'Komponen',
                    'std_qty_per_unit' => 1.0,
                    'uom' => 'Unit',
                    'reference_purchase_price' => (float) ($c->reference_purchase_price ?? 25000),
                ];
            }
        }

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                ],
                'items' => $checklist,
            ],
        ]);
    }

    /**
     * Menyimpan hasil inspeksi Quality Control (Satuan / Batch Pengembalian Sewa Sekolah)
     * beserta kalkulasi otomatis kekurangan dan besar kerugian yang dibebankan ke sekolah penyewa.
     */
    public function storeQualityInspection(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'qc.perform');

        $validated = $request->validate([
            'inspection_type' => ['required', 'string', Rule::in(['UNIT_DEFECT', 'RENTAL_RETURN_SCHOOL'])],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'customer_name' => ['nullable', 'string', 'max:160'],
            'batch_no' => ['nullable', 'string', 'max:64'],
            'unit_count' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.tier' => ['nullable', 'string'],
            'items.*.expected_qty' => ['required', 'numeric', 'min:0'],
            'items.*.actual_qty' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_chargeable' => ['sometimes', 'boolean'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $inspectionNo = 'QC-' . now()->format('Ymd') . '-' . sprintf('%04d', DB::table('quality_inspections')->count() + 1);

        $totalClaim = '0.00';
        $totalLoss = '0.00';
        $hasMissing = false;

        $inspectionId = DB::table('quality_inspections')->insertGetId([
            'inspection_no' => $inspectionNo,
            'inspection_type' => $validated['inspection_type'],
            'source_document_type' => $validated['inspection_type'] === 'RENTAL_RETURN_SCHOOL' ? 'school_rental_return' : 'unit_incident_defect',
            'source_document_id' => 0,
            'product_id' => $validated['product_id'],
            'customer_name' => $validated['customer_name'] ?? null,
            'inspected_by' => $user->id,
            'inspected_at' => now(),
            'status' => 'PASSED',
            'total_claim_amount' => 0,
            'notes' => ($validated['batch_no'] ? "Batch/Ref: {$validated['batch_no']}. " : '') . ($validated['notes'] ?? ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($validated['items'] as $item) {
            $expected = bcadd((string) $item['expected_qty'], '0', 4);
            $actual = bcadd((string) $item['actual_qty'], '0', 4);

            if (bccomp($expected, $actual, 4) > 0) {
                $hasMissing = true;
                $missingQty = bcsub($expected, $actual, 4);
                $unitPrice = bcadd((string) ($item['unit_price'] ?? '0'), '0', 2);
                $itemLoss = bcmul($missingQty, $unitPrice, 2);
                $isChargeable = (bool) ($item['is_chargeable'] ?? true);

                $totalLoss = bcadd($totalLoss, $itemLoss, 2);
                if ($isChargeable) {
                    $totalClaim = bcadd($totalClaim, $itemLoss, 2);
                }

                DB::table('quality_discrepancies')->insert([
                    'quality_inspection_id' => $inspectionId,
                    'product_id' => $item['product_id'],
                    'discrepancy_type' => 'MISSING',
                    'component_tier' => $item['tier'] ?? 'COMPONENT',
                    'quantity' => $missingQty,
                    'reference_unit_price' => $unitPrice,
                    'estimated_claim_amount' => $itemLoss,
                    'is_chargeable' => $isChargeable,
                    'resolution_status' => 'OPEN',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $finalStatus = $hasMissing ? 'CONDITIONAL' : 'PASSED';
        DB::table('quality_inspections')->where('id', $inspectionId)->update([
            'status' => $finalStatus,
            'total_claim_amount' => $totalClaim,
        ]);

        // Jika inspeksi pengembalian sewa sekolah, otomatis tandai status rental order terkait sebagai RETURNED
        if ($validated['inspection_type'] === 'RENTAL_RETURN_SCHOOL' && ! empty($validated['batch_no'])) {
            DB::table('rental_orders')
                ->where('rental_no', $validated['batch_no'])
                ->update([
                    'status' => 'RETURNED',
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'data' => [
                'id' => $inspectionId,
                'inspection_no' => $inspectionNo,
                'status' => $finalStatus,
                'total_physical_loss' => $totalLoss,
                'total_claim_amount' => $totalClaim,
                'has_missing' => $hasMissing,
            ],
            'message' => $hasMissing
                ? "Inspeksi QC selesai. Ditemukan komponen hilang dengan klaim ganti rugi sekolah sebesar Rp " . number_format((float) $totalClaim, 0, ',', '.') . "."
                : "Inspeksi QC selesai. Seluruh kelengkapan produk Finished Good lengkap (PASSED).",
        ], 201);
    }

    // --- Distribution Shipments / DO (Phase 8) ---

    public function shipments(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'distribution.view');

        $data = DB::table('shipments')
            ->orderByDesc('id')
            ->get()
            ->map(function ($s) {
                $s->items = DB::table('shipment_items')
                    ->join('products', 'products.id', '=', 'shipment_items.product_id')
                    ->where('shipment_id', $s->id)
                    ->select('shipment_items.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $s;
            });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function storeShipment(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'distribution.dispatch');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'shipment_type' => ['required', 'string', Rule::in(['CUSTOMER', 'RENTAL', 'VENDOR', 'INTERNAL'])],
            'source_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'destination_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'tracking_reference' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $res = DB::transaction(function () use ($data, $user, $idempotencyKey): array {
            $doNo = $this->numbering->next('SHIPMENT');
            $shipmentId = DB::table('shipments')->insertGetId([
                'shipment_no' => $doNo,
                'shipment_type' => $data['shipment_type'],
                'source_location_id' => $data['source_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'source_document_type' => 'manual_do',
                'source_document_id' => 0,
                'status' => 'IN_TRANSIT',
                'dispatched_at' => now(),
                'tracking_reference' => $data['tracking_reference'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lines = [];
            foreach ($data['items'] as $item) {
                DB::table('shipment_items')->insert([
                    'shipment_id' => $shipmentId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $lines[] = new StockMovementLine(
                    productId: (int) $item['product_id'],
                    quantity: (string) $item['quantity'],
                    fromLocationId: (int) $data['source_location_id'],
                    toLocationId: (int) $data['destination_location_id'],
                    stockStatusFrom: StockStatus::AVAILABLE->value,
                    stockStatusTo: StockStatus::IN_TRANSIT->value,
                );
            }

            $postedTx = $this->inventory->post(new PostInventoryCommand(
                idempotencyKey: $idempotencyKey,
                transactionType: 'DISTRIBUTION_DISPATCH',
                sourceDocumentType: 'shipments',
                sourceDocumentId: $shipmentId,
                postedByUserId: $user->id,
                lines: $lines,
                notes: "Delivery Order {$doNo}",
            ));

            return ['id' => $shipmentId, 'shipment_no' => $doNo, 'status' => 'IN_TRANSIT', 'inventory_transaction' => $postedTx];
        });

        return response()->json(['data' => $res], 201);
    }

    public function updateShipmentStatus(Request $request, int $shipment): JsonResponse
    {
        $this->requirePermission($request, 'distribution.dispatch');
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['IN_TRANSIT', 'DELIVERED', 'SELESAI', 'CANCELLED', 'DRAFT'])],
        ]);

        $status = in_array($data['status'], ['DELIVERED', 'SELESAI'], true) ? 'DELIVERED' : $data['status'];

        $updateData = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'DELIVERED') {
            $updateData['delivered_at'] = now();
        }

        DB::table('shipments')->where('id', $shipment)->update($updateData);

        $updated = DB::table('shipments')->where('id', $shipment)->first();

        return response()->json([
            'data' => $updated,
            'message' => 'Status pengiriman berhasil diubah.',
        ]);
    }

    // --- Material Cost & Reports (Phase 9) ---

    public function materialCostReport(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['material_cost.view', 'reports.view']);

        $summaries = DB::table('production_cost_summaries')
            ->join('production_orders', 'production_orders.id', '=', 'production_cost_summaries.production_order_id')
            ->join('products', 'products.id', '=', 'production_orders.product_id')
            ->select(
                'production_cost_summaries.*',
                'production_orders.order_no',
                'production_orders.target_qty',
                'products.code as product_code',
                'products.name as product_name',
            )
            ->orderByDesc('production_orders.id')
            ->get()
            ->map(function ($row) {
                $row->items = DB::table('production_cost_items')
                    ->join('products', 'products.id', '=', 'production_cost_items.product_id')
                    ->where('production_order_id', $row->production_order_id)
                    ->select('production_cost_items.*', 'products.code as product_code', 'products.name as product_name')
                    ->get();

                return $row;
            });

        return response()->json([
            'data' => [
                'costing_scope' => ['MATERIAL_COMPONENT', 'PURCHASE_COST'],
                'excluded_categories' => ['LABOR', 'MACHINE', 'ELECTRICITY', 'OVERHEAD', 'DEPRECIATION', 'GAP_SERVICE_FEE'],
                'orders' => $summaries,
            ],
        ]);
    }

    public function operationalOverviewReport(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'reports.view');

        return response()->json([
            'data' => [
                'inventory_by_status' => InventoryBalance::query()
                    ->selectRaw('stock_status, COALESCE(SUM(on_hand_qty), 0) as on_hand, COALESCE(SUM(reserved_qty), 0) as reserved')
                    ->groupBy('stock_status')
                    ->get(),
                'vendor_outstanding' => DB::table('vendor_order_items')
                    ->join('vendor_orders', 'vendor_orders.id', '=', 'vendor_order_items.vendor_order_id')
                    ->join('vendors', 'vendors.id', '=', 'vendor_orders.vendor_id')
                    ->join('products', 'products.id', '=', 'vendor_order_items.product_id')
                    ->select(
                        'vendor_orders.vendor_order_no',
                        'vendors.name as vendor_name',
                        'products.code as product_code',
                        'products.name as product_name',
                        'vendor_order_items.sent_quantity',
                        'vendor_order_items.completed_quantity',
                        'vendor_order_items.outstanding_quantity',
                        'vendor_order_items.damaged_quantity',
                    )
                    ->get(),
                'purchase_price_history' => DB::table('purchase_cost_histories')
                    ->join('products', 'products.id', '=', 'purchase_cost_histories.product_id')
                    ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_cost_histories.supplier_id')
                    ->select('purchase_cost_histories.*', 'products.code as product_code', 'products.name as product_name', 'suppliers.name as supplier_name')
                    ->orderByDesc('effective_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }

    // --- Sales Loans (Peminjaman Unit Projek Sales) ---

    public function marketingTeam(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.view');

        $teams = [
            [
                'group_leader' => 'Budi Santoso',
                'sales' => ['Andi Wijaya', 'Rina Marlina', 'Dimas Pratama'],
            ],
            [
                'group_leader' => 'Dewi Sartika',
                'sales' => ['Siti Rahma', 'Fajar Hidayat', 'Bambang Pamungkas'],
            ],
            [
                'group_leader' => 'Hendro Kusumo',
                'sales' => ['Bayu Setiawan', 'Citra Lestari', 'Eko Prasetyo'],
            ],
        ];

        return response()->json(['data' => $teams]);
    }

    public function salesLoans(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.view');

        $loans = DB::table('sales_loans')
            ->join('customers', 'customers.id', '=', 'sales_loans.customer_id')
            ->join('products', 'products.id', '=', 'sales_loans.product_id')
            ->select(
                'sales_loans.*',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'customers.sales_name as customer_sales',
                'products.code as product_code',
                'products.name as product_name'
            )
            ->orderByDesc('sales_loans.id')
            ->get();

        return response()->json(['data' => $loans]);
    }

    public function storeSalesLoan(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');

        $data = $request->validate([
            'group_leader' => ['required', 'string', 'max:120'],
            'sales_name' => ['required', 'string', 'max:120'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'loan_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $nextNum = DB::table('sales_loans')->count() + 1;
        $loanNo = sprintf('LOAN-%s-%04d', date('Ym'), $nextNum);

        $loan = SalesLoan::query()->create([
            'loan_no' => $loanNo,
            'group_leader' => $data['group_leader'],
            'sales_name' => $data['sales_name'],
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'loan_date' => $data['loan_date'],
            'return_date' => null,
            'status' => 'DIPINJAM',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['data' => $loan], 201);
    }

    public function returnSalesLoan(Request $request, SalesLoan $salesLoan): JsonResponse
    {
        $this->requirePermission($request, 'rental.manage');

        $data = $request->validate([
            'return_date' => ['required', 'date'],
            'return_notes' => ['nullable', 'string'],
        ]);

        $receiptNo = sprintf('BTT-LOAN-%s-%04d', date('Ym'), $salesLoan->id);

        $salesLoan->update([
            'status' => 'DIKEMBALIKAN',
            'return_date' => $data['return_date'],
            'return_notes' => $data['return_notes'] ?? null,
            'receipt_no' => $receiptNo,
            'returned_at' => now(),
        ]);

        return response()->json(['data' => $salesLoan]);
    }

    private function requireIdempotencyHeader(Request $request): string
    {
        $key = trim((string) $request->header(IdempotencyService::HEADER));
        if ($key === '' || strlen($key) > 128) {
            throw new DomainException(
                'Header Idempotency-Key wajib disertakan untuk endpoint posting.',
                'IDEMPOTENCY_KEY_REQUIRED',
                422,
            );
        }

        return $key;
    }

    /** @param string|list<string> $codes */
    private function requirePermission(Request $request, string|array $codes): void
    {
        /** @var User $user */
        $user = $request->user();
        foreach ((array) $codes as $code) {
            if ($user->hasPermission($code)) {
                return;
            }
        }
        throw new AuthorizationException;
    }
}
