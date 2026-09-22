<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\MikmsModule;
use App\Models\MikmsBom;
use App\Models\MikmsBox;
use App\Models\MikmsProduction;
use App\Models\MikmsQcLog;
use App\Models\MikmsShipment;
use App\Models\MikmsReturn;
use App\Models\MikmsRepair;
use App\Models\MikmsStockOpname;
use App\Models\MikmsModuleStock;
use App\Models\MikmsModuleStockLog;
use App\Models\MikmsPackageOrder;
use App\Models\MikmsProgram;
use App\Models\MikmsFinishGood;
use App\Models\MikmsUnit;
use App\Models\MikmsDirectInput;
use App\Models\MikmsQcChecklistTemplate;
use App\Models\MikmsDispatch;

class MikmsController extends Controller
{
    /**
     * Dashboard statistik MIKMS
     */
    public function getDashboard()
    {
        $totalModules = MikmsModule::count();
        $totalBoxes = MikmsBox::count();
        $boxesReady = MikmsBox::where('status', 'READY')->count();
        $boxesOnLoan = MikmsBox::where('status', 'ON_LOAN')->count();
        $boxesRepair = MikmsBox::where('status', 'REPAIR')->count();

        // Finish Goods Unit Stats
        $totalUnits = MikmsUnit::count();
        $unitsInStock = MikmsUnit::where('status', 'in_stock')->count();
        $unitsQcPending = MikmsUnit::where('status', 'qc_pending')->count();
        $unitsDispatched = MikmsUnit::where('status', 'dispatched')->count();
        $unitsRepair = MikmsUnit::where('status', 'repair')->count();

        $totalProduced = MikmsProduction::sum('quantity_produced');
        $totalShipments = MikmsShipment::count();
        $totalReturns = MikmsReturn::count();
        $totalRepairs = MikmsRepair::count();

        $recentProductions = MikmsProduction::with('module')->latest()->take(5)->get();
        $recentShipments = MikmsShipment::latest()->take(5)->get();
        $recentReturns = MikmsReturn::latest()->take(5)->get();
        $recentRepairs = MikmsRepair::latest()->take(5)->get();

        $stats = [
            'total_modules' => $totalModules,
            'total_boxes' => $totalBoxes,
            'boxes_ready' => $boxesReady,
            'boxes_on_loan' => $boxesOnLoan,
            'boxes_repair' => $boxesRepair,
            'total_units' => $totalUnits,
            'units_in_stock' => $unitsInStock,
            'units_qc_pending' => $unitsQcPending,
            'units_dispatched' => $unitsDispatched,
            'units_repair' => $unitsRepair,
            'total_produced' => (int)$totalProduced,
            'total_shipments' => $totalShipments,
            'total_returns' => $totalReturns,
            'total_repairs' => $totalRepairs,
        ];

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => array_merge($stats, [
                'stats' => $stats,
                'recent_productions' => $recentProductions,
                'recent_shipments' => $recentShipments,
                'recent_returns' => $recentReturns,
                'recent_repairs' => $recentRepairs,
            ])
        ]);
    }

    /**
     * List master modul & BOM
     */
    public function getModules()
    {
        $modules = MikmsModule::with(['boms.item'])->get()->map(function ($mod) {
            $bomList = $mod->boms->map(function ($b) {
                $stok = $b->item ? $b->item->stok : 0;
                return [
                    'id' => $b->id,
                    'item_id' => $b->item_id,
                    'item_code' => $b->item ? $b->item->kode : '',
                    'item_name' => $b->item ? $b->item->nama : '',
                    'item_unit' => $b->item ? $b->item->satuan : 'pcs',
                    'quantity_per_module' => $b->quantity,
                    'box_category' => $b->box_category,
                    'current_stock' => $stok,
                ];
            });

            return [
                'id' => $mod->id,
                'code' => $mod->code,
                'name' => $mod->name,
                'description' => $mod->description,
                'components' => $bomList,
            ];
        });

        return response()->json(['status' => 'success', 'success' => true, 'data' => $modules]);
    }

    /**
     * List Master Box Kit
     */
    public function getBoxes(Request $request)
    {
        $query = MikmsBox::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('box_code', 'like', "%$q%")
                  ->orWhere('category', 'like', "%$q%")
                  ->orWhere('program_code', 'like', "%$q%");
            });
        }

        $boxes = $query->orderBy('box_code')->get();
        return response()->json(['status' => 'success', 'success' => true, 'data' => $boxes]);
    }

    /**
     * Simpan / Tambah Box Kit
     */
    public function storeBox(Request $request)
    {
        $validated = $request->validate([
            'box_code' => 'required|string|max:50|unique:mikms_boxes,box_code',
            'category' => 'required|string|max:100',
            'program_code' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $box = MikmsBox::create([
            'box_code' => strtoupper($validated['box_code']),
            'category' => $validated['category'],
            'program_code' => $validated['program_code'] ?? null,
            'status' => $validated['status'] ?? 'READY',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Box Kit berhasil ditambahkan', 'data' => $box]);
    }

    /**
     * Form Produksi Modul (Potong stok otomatis via BOM)
     */
    public function storeProduction(Request $request)
    {
        $validated = $request->validate([
            'production_date' => 'required|date',
            'module_id' => 'required|exists:mikms_modules,id',
            'quantity_produced' => 'required|integer|min:1',
            'produced_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $module = MikmsModule::with('boms.item')->findOrFail($validated['module_id']);
        $qty = $validated['quantity_produced'];

        // Cek stok bahan baku
        $shortage = [];
        foreach ($module->boms as $bom) {
            $needed = $bom->quantity * $qty;
            $currentStock = $bom->item ? $bom->item->stok : 0;
            if ($currentStock < $needed) {
                $shortage[] = "{$bom->item->nama} (kurang " . ($needed - $currentStock) . " {$bom->item->satuan})";
            }
        }

        if (!empty($shortage)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok bahan baku tidak mencukupi untuk perakitan: ' . implode(', ', $shortage),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Simpan data produksi
            $production = MikmsProduction::create([
                'production_date' => $validated['production_date'],
                'module_id' => $module->id,
                'quantity_produced' => $qty,
                'produced_by' => $validated['produced_by'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Potong stok komponen otomatis di gudang utama
            $userId = auth()->id() ?? 1;
            foreach ($module->boms as $bom) {
                $needed = $bom->quantity * $qty;
                Transaction::create([
                    'item_id' => $bom->item_id,
                    'user_id' => $userId,
                    'tipe' => 'keluar',
                    'qty' => $needed,
                    'transaction_date' => $validated['production_date'],
                    'catatan' => "Produksi MIKMS: {$module->code} - {$module->name} x {$qty}",
                    'lokasi' => 'Gudang Utama',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil memproduksi {$qty} unit Modul {$module->code} ({$module->name}). Stok komponen otomatis terpotong.",
                'data' => $production
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Form QC Unit & Modul
     */
    public function storeQcLog(Request $request)
    {
        $validated = $request->validate([
            'qc_date'           => 'nullable|date',
            'serial_number'     => 'nullable|string|exists:mikms_units,serial_number',
            'box_id'            => 'nullable|exists:mikms_boxes,id',
            'module_id'         => 'nullable|exists:mikms_modules,id',
            'qc_type'           => 'nullable|in:production,return,rental_return,sewa_kembali',
            'checklist_results' => 'nullable|array',
            'checklist_results.*.item' => 'required_with:checklist_results|string',
            'checklist_results.*.pass' => 'required_with:checklist_results|boolean',
            'defect_notes'      => 'nullable|string',
            'checked_by'        => 'nullable|string|max:100',
            'technician_name'   => 'nullable|string|max:100',
            'notes'             => 'nullable|string',
            'status'            => 'nullable|in:PASS,FAIL',
        ]);

        $qcDate = $validated['qc_date'] ?? now()->toDateString();
        $checker = $validated['checked_by'] ?? $validated['technician_name'] ?? 'QC Officer';

        // 1. Jika QC untuk Unit Finish Good (dengan serial number)
        if (!empty($validated['serial_number'])) {
            $unit = MikmsUnit::where('serial_number', $validated['serial_number'])->firstOrFail();

            $checklist = $validated['checklist_results'] ?? [];
            $allPass = !empty($checklist) && collect($checklist)->every(fn($r) => $r['pass'] === true);
            $statusQc = $allPass ? 'LOLOS' : 'TIDAK LOLOS';

            DB::beginTransaction();
            try {
                $qc = MikmsQcLog::create([
                    'qc_date'           => $qcDate,
                    'unit_id'           => $unit->id,
                    'qc_type'           => $validated['qc_type'] ?? 'production',
                    'status_qc'         => $statusQc,
                    'checklist_results' => $checklist,
                    'defect_notes'      => $validated['defect_notes'] ?? null,
                    'checked_by'        => $checker,
                    'notes'             => $validated['notes'] ?? null,
                ]);

                // Update status unit berdasarkan hasil QC
                if ($allPass) {
                    $unit->status = 'in_stock';
                } else {
                    $unit->status = in_array($unit->status, ['returned', 'qc_pending']) ? 'repair' : 'qc_pending';
                }
                $unit->save();

                // Sinkronkan ke tabel Asset jika unit ini terdaftar sebagai aset barang sewa
                $asset = \App\Models\Asset::where('serial_number', $unit->serial_number)->first();
                if ($asset) {
                    $asset->status = $allPass ? 'good' : 'not_good';
                    $asset->save();
                }

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'success' => true,
                    'message' => "QC Unit {$unit->serial_number}: {$statusQc}. Status unit diperbarui menjadi '{$unit->status}'.",
                    'data'    => [
                        'qc'          => $qc,
                        'unit_status' => $unit->status,
                        'status_qc'   => $statusQc,
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }

        // 2. Legacy QC untuk Box / Modul
        DB::beginTransaction();
        try {
            $statusQc = $validated['status'] ?? 'PASS';
            $qc = MikmsQcLog::create([
                'qc_date'           => $qcDate,
                'unit_id'           => null,
                'qc_type'           => 'production',
                'status_qc'         => $statusQc === 'PASS' ? 'LOLOS' : 'TIDAK LOLOS',
                'checklist_results' => $validated['checklist_results'] ?? null,
                'defect_notes'      => $validated['defect_notes'] ?? null,
                'checked_by'        => $checker,
                'notes'             => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['box_id'])) {
                $box = MikmsBox::find($validated['box_id']);
                if ($box) {
                    $box->status = $statusQc === 'PASS' ? 'READY' : 'REPAIR';
                    $box->save();
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'success' => true,
                'message' => "QC berhasil dicatat. Status: {$statusQc}.",
                'data'    => $qc
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Form Pengiriman ke Sekolah
     */
    public function storeShipment(Request $request)
    {
        $validated = $request->validate([
            'shipment_date' => 'required|date',
            'box_code' => 'required|string|max:50',
            'program_code' => 'nullable|string|max:50',
            'program_name' => 'nullable|string|max:100',
            'school_name' => 'required|string|max:150',
            'quantity_box' => 'nullable|integer|min:1',
            'shipped_by' => 'required|string|max:100',
            'received_by_school' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $boxCode = strtoupper(trim($validated['box_code']));

        // Update status box jika terdaftar
        $box = MikmsBox::where('box_code', $boxCode)->first();
        if ($box) {
            $box->update(['status' => 'ON_LOAN']);
        }

        $shipment = MikmsShipment::create([
            'shipment_date' => $validated['shipment_date'],
            'box_code' => $boxCode,
            'program_code' => $validated['program_code'] ?? null,
            'program_name' => $validated['program_name'] ?? null,
            'school_name' => $validated['school_name'],
            'quantity_box' => $validated['quantity_box'] ?? 1,
            'shipped_by' => $validated['shipped_by'],
            'received_by_school' => $validated['received_by_school'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pengiriman {$boxCode} ke {$validated['school_name']} berhasil dicatat.",
            'data' => $shipment
        ]);
    }

    /**
     * Form Pengembalian dari Sekolah
     */
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'box_code' => 'required|string|max:50',
            'school_name' => 'required|string|max:150',
            'condition' => 'required|in:LENGKAP,RUSAK,HILANG',
            'problematic_item_code' => 'nullable|string|max:50',
            'problematic_item_name' => 'nullable|string|max:150',
            'problematic_quantity' => 'nullable|integer|min:0',
            'received_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $boxCode = strtoupper(trim($validated['box_code']));

        // Update status box
        $box = MikmsBox::where('box_code', $boxCode)->first();
        if ($box) {
            if ($validated['condition'] === 'LENGKAP') {
                $box->update(['status' => 'READY']);
            } elseif ($validated['condition'] === 'RUSAK') {
                $box->update(['status' => 'REPAIR']);
            } else {
                $box->update(['status' => 'DAMAGED']);
            }
        }

        $return = MikmsReturn::create([
            'return_date' => $validated['return_date'],
            'box_code' => $boxCode,
            'school_name' => $validated['school_name'],
            'condition' => $validated['condition'],
            'problematic_item_code' => $validated['problematic_item_code'] ?? null,
            'problematic_item_name' => $validated['problematic_item_name'] ?? null,
            'problematic_quantity' => $validated['problematic_quantity'] ?? 0,
            'received_by' => $validated['received_by'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pengembalian {$boxCode} dicatat (Kondisi: {$validated['condition']}).",
            'data' => $return
        ]);
    }

    /**
     * Form Repair
     */
    public function storeRepair(Request $request)
    {
        $validated = $request->validate([
            'repair_date' => 'required|date',
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:150',
            'asset_id' => 'nullable|string|max:50',
            'damage_type' => 'required|string|max:150',
            'repair_action' => 'required|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'repair_result' => 'required|in:BERHASIL,GAGAL',
            'repaired_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $repair = MikmsRepair::create([
            'repair_date' => $validated['repair_date'],
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'asset_id' => $validated['asset_id'] ?? null,
            'damage_type' => $validated['damage_type'],
            'repair_action' => $validated['repair_action'],
            'quantity' => $validated['quantity'] ?? 1,
            'repair_result' => $validated['repair_result'],
            'repaired_by' => $validated['repaired_by'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Jika ada Box terkait yang dalam status REPAIR, jika hasil BERHASIL bisa dikembalikan READY
        if (!empty($validated['asset_id'])) {
            $box = MikmsBox::where('box_code', strtoupper($validated['asset_id']))->first();
            if ($box) {
                $box->update(['status' => ($validated['repair_result'] === 'BERHASIL' ? 'READY' : 'DISPOSED')]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Hasil repair dicatat: {$validated['repair_result']}.",
            'data' => $repair
        ]);
    }

    /**
     * Form Stock Opname
     */
    public function storeStockOpname(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'item_code' => 'required|string|max:50',
            'physical_quantity' => 'required|integer|min:0',
            'difference_reason' => 'nullable|string|max:255',
            'counted_by' => 'required|string|max:100',
        ]);

        $item = Item::where('kode', $validated['item_code'])->first();
        $itemName = $item ? $item->nama : $validated['item_code'];
        $sysQty = $item ? $item->stok : 0;
        $physQty = $validated['physical_quantity'];
        $diff = $physQty - $sysQty;

        $opname = MikmsStockOpname::create([
            'opname_date' => $validated['opname_date'],
            'item_code' => $validated['item_code'],
            'item_name' => $itemName,
            'system_quantity' => $sysQty,
            'physical_quantity' => $physQty,
            'difference' => $diff,
            'difference_reason' => $validated['difference_reason'] ?? null,
            'counted_by' => $validated['counted_by'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Stock Opname {$validated['item_code']} tersimpan (Fisik: {$physQty}, Selisih: {$diff}).",
            'data' => $opname
        ]);
    }

    /**
     * Ambil seluruh riwayat transaksi MIKMS
     */
    public function getLogs(Request $request)
    {
        $type = $request->query('type', 'productions');

        switch ($type) {
            case 'productions':
                $data = MikmsProduction::with('module')->latest()->paginate(20);
                break;
            case 'qc':
                $data = MikmsQcLog::with('module')->latest()->paginate(20);
                break;
            case 'shipments':
                $data = MikmsShipment::latest()->paginate(20);
                break;
            case 'returns':
                $data = MikmsReturn::latest()->paginate(20);
                break;
            case 'repairs':
                $data = MikmsRepair::latest()->paginate(20);
                break;
            case 'opname':
                $data = MikmsStockOpname::latest()->paginate(20);
                break;
            case 'package_orders':
                $data = MikmsPackageOrder::latest()->paginate(20);
                break;
            default:
                $data = [];
        }

        return response()->json(['status' => 'success', 'success' => true, 'data' => $data]);
    }

    // =============================================
    // PROGRAM-TO-MODULE MAPPING (from Excel BOM)
    // =============================================

    /**
     * Map program codes to their required module codes.
     * Reads dynamically from database `mikms_programs`, with fallback to default mapping.
     */
    private function getProgramModules(): array
    {
        try {
            $programs = MikmsProgram::where('is_active', true)->get();
            if ($programs->isNotEmpty()) {
                $result = [];
                foreach ($programs as $p) {
                    $modules = is_array($p->modules) ? $p->modules : json_decode($p->modules, true);
                    $result[strtoupper($p->code)] = [
                        'name' => $p->name,
                        'modules' => $modules ?: [],
                        'total_pcs' => $p->total_pcs ?: $p->calculateTotalPcs(),
                        'description' => $p->description,
                    ];
                }
                return $result;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return [
            'MLK' => [
                'name' => 'Microbit Learning Kit',
                'modules' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07'],
                'total_pcs' => 95,
            ],
            'ROBOTIC' => [
                'name' => 'Robotic Explorer',
                'modules' => ['M01', 'M03', 'M04', 'M05', 'M07', 'M08'],
                'total_pcs' => 81,
            ],
        ];
    }

    // =============================================
    // PACKAGE SIMULATE (Dry Run / Preview)
    // =============================================

    /**
     * GET /api/mikms/package-simulate?program_code=MLK&package_qty=5
     * Preview kebutuhan tanpa mengubah stok.
     */
    public function packageSimulate(Request $request)
    {
        $request->validate([
            'program_code' => 'required|string|max:50',
            'package_qty' => 'required|integer|min:1|max:100',
        ]);

        $programCode = strtoupper(trim($request->program_code));
        $packageQty = (int) $request->package_qty;
        $programs = $this->getProgramModules();

        if (!isset($programs[$programCode])) {
            return response()->json([
                'status' => 'error',
                'message' => "Program Kit {$programCode} tidak ditemukan atau belum aktif di Master Program.",
            ], 422);
        }

        $program = $programs[$programCode];

        // 1. Get required modules and their current ready stock
        $modulesBreakdown = [];
        $rawMaterialsNeeded = [];
        $canFulfill = true;
        $shortages = [];
        $totalModulesFromStock = 0;
        $totalModulesToAssemble = 0;

        foreach ($program['modules'] as $moduleCode) {
            $module = MikmsModule::where('code', $moduleCode)->first();
            if (!$module) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Modul {$moduleCode} tidak ditemukan di database.",
                ], 404);
            }

            // Check ready module stock
            $moduleStock = MikmsModuleStock::where('module_id', $module->id)->first();
            $readyStock = $moduleStock ? $moduleStock->stock_ready : 0;

            // Calculate: how many from stock vs how many need assembly
            $fromStock = min($readyStock, $packageQty);
            $toAssemble = $packageQty - $fromStock;

            $totalModulesFromStock += $fromStock;
            $totalModulesToAssemble += $toAssemble;

            $modulesBreakdown[] = [
                'module_id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'needed' => $packageQty,
                'ready_stock' => $readyStock,
                'from_stock' => $fromStock,
                'to_assemble' => $toAssemble,
            ];

            // If we need to assemble, calculate raw materials needed
            if ($toAssemble > 0) {
                $boms = MikmsBom::where('module_id', $module->id)->with('item')->get();
                foreach ($boms as $bom) {
                    $rawNeeded = $bom->quantity * $toAssemble;
                    $itemId = $bom->item_id;

                    if (isset($rawMaterialsNeeded[$itemId])) {
                        $rawMaterialsNeeded[$itemId]['needed'] += $rawNeeded;
                    } else {
                        $item = $bom->item;
                        $rawMaterialsNeeded[$itemId] = [
                            'item_id' => $itemId,
                            'code' => $item ? $item->kode : '?',
                            'name' => $item ? $item->nama : '?',
                            'unit' => $item ? $item->satuan : 'pcs',
                            'needed' => $rawNeeded,
                            'available' => $item ? $item->stok : 0,
                            'sufficient' => true,
                        ];
                    }
                }
            }
        }

        // 2. Check raw material sufficiency
        foreach ($rawMaterialsNeeded as &$raw) {
            if ($raw['available'] < $raw['needed']) {
                $raw['sufficient'] = false;
                $canFulfill = false;
                $shortages[] = [
                    'code' => $raw['code'],
                    'name' => $raw['name'],
                    'needed' => $raw['needed'],
                    'available' => $raw['available'],
                    'shortage' => $raw['needed'] - $raw['available'],
                    'unit' => $raw['unit'],
                ];
            }
        }
        unset($raw);

        // 3. Build summary
        $summaryParts = [];
        $summaryParts[] = "{$packageQty} paket {$program['name']}";
        if ($totalModulesFromStock > 0) {
            $summaryParts[] = "{$totalModulesFromStock} modul diambil dari rak";
        }
        if ($totalModulesToAssemble > 0) {
            $summaryParts[] = "{$totalModulesToAssemble} modul perlu dirakit dari bahan baku";
        }
        if ($canFulfill) {
            $summary = implode('. ', $summaryParts) . '. ✅ Dapat dipenuhi.';
        } else {
            $summary = implode('. ', $summaryParts) . '. ❌ Bahan baku tidak mencukupi!';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'program_code' => $programCode,
                'program_name' => $program['name'],
                'package_qty' => $packageQty,
                'total_components_per_package' => $program['total_pcs'],
                'modules_breakdown' => $modulesBreakdown,
                'raw_materials_needed' => array_values($rawMaterialsNeeded),
                'can_fulfill' => $canFulfill,
                'shortages' => $shortages,
                'summary' => $summary,
                'total_modules_from_stock' => $totalModulesFromStock,
                'total_modules_to_assemble' => $totalModulesToAssemble,
            ],
        ]);
    }

    // =============================================
    // PACKAGE ORDER (Atomic Execute)
    // =============================================

    /**
     * POST /api/mikms/package-orders
     * Execute a package order with cascading BOM deduction.
     */
    public function storePackageOrder(Request $request)
    {
        $validated = $request->validate([
            'order_date' => 'required|date',
            'program_code' => 'required|string|max:50',
            'package_qty' => 'required|integer|min:1|max:100',
            'customer_name' => 'nullable|string|max:150',
            'customer_id' => 'nullable|exists:customers,id',
            'ordered_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $programCode = strtoupper(trim($validated['program_code']));
        $packageQty = (int) $validated['package_qty'];
        $programs = $this->getProgramModules();

        if (!isset($programs[$programCode])) {
            return response()->json([
                'status' => 'error',
                'message' => "Program Kit {$programCode} tidak ditemukan atau belum aktif di Master Program.",
            ], 422);
        }

        $program = $programs[$programCode];

        DB::beginTransaction();
        try {
            $userId = auth()->id() ?? 1;
            $deductionLog = [
                'program' => $programCode,
                'program_name' => $program['name'],
                'package_qty' => $packageQty,
                'timestamp' => now()->toISOString(),
                'modules' => [],
                'raw_materials_deducted' => [],
            ];

            foreach ($program['modules'] as $moduleCode) {
                $module = MikmsModule::where('code', $moduleCode)->firstOrFail();
                $moduleStock = MikmsModuleStock::where('module_id', $module->id)->lockForUpdate()->first();
                $readyStock = $moduleStock ? $moduleStock->stock_ready : 0;

                $fromStock = min($readyStock, $packageQty);
                $toAssemble = $packageQty - $fromStock;

                $moduleLog = [
                    'code' => $moduleCode,
                    'name' => $module->name,
                    'needed' => $packageQty,
                    'from_ready_stock' => $fromStock,
                    'assembled_from_raw' => $toAssemble,
                ];

                // TIER 1: Deduct from ready module stock
                if ($fromStock > 0 && $moduleStock) {
                    $stockBefore = $moduleStock->stock_ready;
                    $moduleStock->stock_ready -= $fromStock;
                    $moduleStock->save();

                    MikmsModuleStockLog::create([
                        'module_id' => $module->id,
                        'type' => 'package_out',
                        'quantity' => -$fromStock,
                        'stock_before' => $stockBefore,
                        'stock_after' => $moduleStock->stock_ready,
                        'reference_type' => 'mikms_package_orders',
                        'performed_by' => $validated['ordered_by'],
                        'notes' => "Paket {$programCode} x{$packageQty} — modul dari rak",
                    ]);
                }

                // TIER 2: Assemble from raw materials
                if ($toAssemble > 0) {
                    $boms = MikmsBom::where('module_id', $module->id)->with('item')->get();

                    // First pass: validate all raw materials have enough stock
                    $shortageItems = [];
                    foreach ($boms as $bom) {
                        $needed = $bom->quantity * $toAssemble;
                        $item = $bom->item;
                        $currentStock = $item ? $item->stok : 0;
                        if ($currentStock < $needed) {
                            $shortageItems[] = "{$item->nama} (kurang " . ($needed - $currentStock) . " {$item->satuan})";
                        }
                    }

                    if (!empty($shortageItems)) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Stok bahan baku tidak cukup untuk merakit {$toAssemble}x {$moduleCode} ({$module->name}): " . implode(', ', $shortageItems),
                            'shortages' => $shortageItems,
                        ], 422);
                    }

                    // Second pass: deduct raw materials
                    foreach ($boms as $bom) {
                        $needed = $bom->quantity * $toAssemble;
                        Transaction::create([
                            'item_id' => $bom->item_id,
                            'user_id' => $userId,
                            'tipe' => 'keluar',
                            'qty' => $needed,
                            'transaction_date' => $validated['order_date'],
                            'catatan' => "Paket {$programCode} x{$packageQty}: Rakit {$moduleCode} x{$toAssemble}",
                            'lokasi' => 'Gudang Utama',
                        ]);

                        $item = $bom->item;
                        $deductionLog['raw_materials_deducted'][] = [
                            'item_code' => $item ? $item->kode : '?',
                            'item_name' => $item ? $item->nama : '?',
                            'qty_deducted' => $needed,
                            'for_module' => $moduleCode,
                        ];
                    }

                    // Record auto-production for assembled modules
                    MikmsProduction::create([
                        'production_date' => $validated['order_date'],
                        'module_id' => $module->id,
                        'quantity_produced' => $toAssemble,
                        'produced_by' => $validated['ordered_by'],
                        'notes' => "Auto-assembly dari Package Order {$programCode} x{$packageQty}",
                    ]);
                }

                $deductionLog['modules'][] = $moduleLog;
            }

            $customerName = !empty($validated['customer_name']) ? $validated['customer_name'] : 'Gudang Internal';

            // Save the package order
            $order = MikmsPackageOrder::create([
                'order_date' => $validated['order_date'],
                'program_code' => $programCode,
                'program_name' => $program['name'],
                'package_qty' => $packageQty,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $customerName,
                'status' => 'COMPLETED',
                'deduction_log' => $deductionLog,
                'ordered_by' => $validated['ordered_by'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Otomatis registrasikan unit serial number ke MikmsUnit jika program cocok dengan Finish Good (misal MLK)
            $createdSerials = [];
            $finishGood = MikmsFinishGood::where('code', $programCode)->first();
            if ($finishGood) {
                for ($i = 0; $i < $packageQty; $i++) {
                    $serial = $finishGood->nextSerial();
                    MikmsUnit::create([
                        'serial_number' => $serial,
                        'product_id'    => $finishGood->id,
                        'status'        => 'qc_pending',
                        'source_type'   => 'assembled',
                        'source_id'     => $order->id,
                        'notes'         => "Dirakit dari Pesan Paket #{$order->id} ({$programCode}). Petugas: {$validated['ordered_by']}",
                    ]);
                    $createdSerials[] = $serial;
                }
                $order->update([
                    'notes' => ($order->notes ? $order->notes . ' | ' : '') . 'Serial Unit: ' . implode(', ', $createdSerials)
                ]);
            }

            DB::commit();

            $msg = "Pesanan {$packageQty} paket {$program['name']} berhasil diproses. Stok komponen telah dipotong.";
            if (!empty($createdSerials)) {
                $msg .= " Unit telah diregistrasi ke Registry: " . implode(', ', $createdSerials) . " (Status: Menunggu QC).";
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => $msg,
                'data' => [
                    'order' => $order,
                    'serial_numbers' => $createdSerials,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =============================================
    // MODULE STOCK MANAGEMENT
    // =============================================

    /**
     * GET /api/mikms/module-stocks
     * List all module stocks.
     */
    public function getModuleStocks()
    {
        $stocks = MikmsModuleStock::with('module')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'module_id' => $s->module_id,
                'module_code' => $s->module ? $s->module->code : '?',
                'module_name' => $s->module ? $s->module->name : '?',
                'stock_ready' => $s->stock_ready,
                'updated_at' => $s->updated_at?->toDateTimeString(),
            ];
        });

        return response()->json(['status' => 'success', 'success' => true, 'data' => $stocks]);
    }

    /**
     * POST /api/mikms/module-stocks/adjust
     * Manual adjust stok modul jadi.
     */
    public function adjustModuleStock(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:mikms_modules,id',
            'adjustment' => 'required|integer',
            'reason' => 'required|string|max:255',
            'adjusted_by' => 'required|string|max:100',
        ]);

        $moduleStock = MikmsModuleStock::firstOrCreate(
            ['module_id' => $validated['module_id']],
            ['stock_ready' => 0]
        );

        $stockBefore = $moduleStock->stock_ready;
        $newStock = $stockBefore + $validated['adjustment'];

        if ($newStock < 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Stok modul tidak boleh negatif. Saldo saat ini: {$stockBefore}, penyesuaian: {$validated['adjustment']}",
            ], 422);
        }

        $moduleStock->stock_ready = $newStock;
        $moduleStock->save();

        MikmsModuleStockLog::create([
            'module_id' => $validated['module_id'],
            'type' => 'manual_adjust',
            'quantity' => $validated['adjustment'],
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'reference_type' => 'manual',
            'performed_by' => $validated['adjusted_by'],
            'notes' => $validated['reason'],
        ]);

        $module = MikmsModule::find($validated['module_id']);
        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => "Stok modul {$module->code} ({$module->name}) disesuaikan: {$stockBefore} → {$newStock}",
            'data' => $moduleStock,
        ]);
    }

    /**
     * GET /api/mikms/package-orders
     * List all package orders.
     */
    public function getPackageOrders(Request $request)
    {
        $query = MikmsPackageOrder::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20);
        return response()->json(['status' => 'success', 'success' => true, 'data' => $orders]);
    }

    // =============================================
    // MASTER PROGRAM KIT CRUD
    // =============================================

    /**
     * GET /api/mikms/programs
     */
    public function getPrograms(Request $request)
    {
        $query = MikmsProgram::query();
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        $programs = $query->orderBy('name')->get();
        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $programs,
        ]);
    }

    /**
     * POST /api/mikms/programs
     */
    public function storeProgram(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:mikms_programs,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'modules' => 'required|array|min:1',
            'modules.*' => 'string|exists:mikms_modules,code',
            'is_active' => 'boolean',
        ]);

        $program = new MikmsProgram();
        $program->code = strtoupper(trim($validated['code']));
        $program->name = trim($validated['name']);
        $program->description = $validated['description'] ?? null;
        $program->modules = array_values(array_unique($validated['modules']));
        $program->is_active = $validated['is_active'] ?? true;
        $program->total_pcs = $program->calculateTotalPcs();
        $program->save();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => "Program Kit {$program->name} ({$program->code}) berhasil ditambahkan.",
            'data' => $program,
        ], 201);
    }

    /**
     * PUT /api/mikms/programs/{id}
     */
    public function updateProgram(Request $request, $id)
    {
        $program = MikmsProgram::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'modules' => 'sometimes|required|array|min:1',
            'modules.*' => 'string|exists:mikms_modules,code',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) $program->name = trim($validated['name']);
        if (array_key_exists('description', $validated)) $program->description = $validated['description'];
        if (isset($validated['modules'])) {
            $program->modules = array_values(array_unique($validated['modules']));
            $program->total_pcs = $program->calculateTotalPcs();
        }
        if (isset($validated['is_active'])) $program->is_active = $validated['is_active'];
        $program->save();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => "Program Kit {$program->code} berhasil diperbarui.",
            'data' => $program,
        ]);
    }

    /**
     * DELETE /api/mikms/programs/{id}
     */
    public function deleteProgram($id)
    {
        $program = MikmsProgram::findOrFail($id);
        $code = $program->code;

        // Check if referenced in package orders or shipments
        $hasOrders = MikmsPackageOrder::where('program_code', $code)->exists();
        $hasShipments = MikmsShipment::where('program_code', $code)->exists();

        if ($hasOrders || $hasShipments) {
            $program->is_active = false;
            $program->save();
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "Program {$code} dinonaktifkan karena telah memiliki riwayat transaksi.",
                'data' => $program,
            ]);
        }

        $program->delete();
        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => "Program {$code} berhasil dihapus permanen.",
        ]);
    }

    // =============================================
    // FINISH GOODS — 5 PRODUK UNIT
    // =============================================

    /**
     * GET /api/mikms/products
     * List semua produk finish good beserta stok in_stock.
     */
    public function getProducts()
    {
        $products = MikmsFinishGood::where('active', true)->get()->map(function ($p) {
            return [
                'id'          => $p->id,
                'code'        => $p->code,
                'name'        => $p->name,
                'type'        => $p->type,
                'can_rent'    => $p->can_rent,
                'description' => $p->description,
                'stock'       => [
                    'in_stock'   => $p->units()->where('status', 'in_stock')->count(),
                    'qc_pending' => $p->units()->where('status', 'qc_pending')->count(),
                    'dispatched' => $p->units()->where('status', 'dispatched')->count(),
                    'repair'     => $p->units()->where('status', 'repair')->count(),
                    'total'      => $p->units()->count(),
                ],
            ];
        });

        return response()->json(['status' => 'success', 'success' => true, 'data' => $products]);
    }

    /**
     * GET /api/mikms/qc-templates/{productId}
     * Ambil checklist template untuk produk tertentu.
     */
    public function getQcTemplates($productId)
    {
        $product = MikmsFinishGood::findOrFail($productId);
        $templates = MikmsQcChecklistTemplate::where('product_id', $productId)
                        ->where('active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'check_item', 'sort_order']);

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => [
                'product'   => ['id' => $product->id, 'code' => $product->code, 'name' => $product->name],
                'checklist' => $templates,
            ],
        ]);
    }

    // =============================================
    // DIRECT INPUT — Jimu, Erboblox, Arduino
    // =============================================

    /**
     * POST /api/mikms/direct-input
     * Input langsung unit dari vendor, generate serial numbers, status qc_pending.
     */
    public function storeDirectInput(Request $request)
    {
        $validated = $request->validate([
            'input_date'  => 'required|date',
            'product_id'  => 'required|exists:mikms_finish_goods,id',
            'quantity'    => 'required|integer|min:1|max:500',
            'supplier'    => 'nullable|string|max:150',
            'received_by' => 'required|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $product = MikmsFinishGood::findOrFail($validated['product_id']);

        if ($product->type === 'bundle') {
            return response()->json([
                'status'  => 'error',
                'message' => "Produk {$product->name} adalah paket kemasan (bundle). Gunakan menu 'Packaging ROBO' untuk memasangkan unit MLK dan JIMU.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $directInput = MikmsDirectInput::create($validated);

            $serialNumbers = [];
            for ($i = 0; $i < $validated['quantity']; $i++) {
                $serial = $product->nextSerial();
                MikmsUnit::create([
                    'serial_number' => $serial,
                    'product_id'    => $product->id,
                    'status'        => 'qc_pending',
                    'source_type'   => $product->type === 'assembled' ? 'assembled' : 'direct_input',
                    'source_id'     => $directInput->id,
                ]);
                $serialNumbers[] = $serial;
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "{$validated['quantity']} unit {$product->name} berhasil diinput. Serial: {$serialNumbers[0]} s/d {$serialNumbers[count($serialNumbers)-1]}. Status: Menunggu QC.",
                'data'    => [
                    'direct_input'   => $directInput,
                    'serial_numbers' => $serialNumbers,
                    'count'          => count($serialNumbers),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =============================================
    // UNIT REGISTRY
    // =============================================

    /**
     * GET /api/mikms/units
     * List unit registry dengan filter.
     */
    public function getUnits(Request $request)
    {
        $query = MikmsUnit::with(['product', 'latestQc']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where('serial_number', 'like', "%{$q}%");
        }

        $units = $query->latest()->paginate(50);

        $units->getCollection()->transform(function ($u) {
            return [
                'id'            => $u->id,
                'serial_number' => $u->serial_number,
                'product_code'  => $u->product?->code,
                'product_name'  => $u->product?->name,
                'status'        => $u->status,
                'status_label'  => $u->statusLabel(),
                'status_badge'  => $u->statusBadge(),
                'source_type'   => $u->source_type,
                'qc_status'     => $u->latestQc?->status_qc ?? '-',
                'created_at'    => $u->created_at?->toDateString(),
            ];
        });

        return response()->json(['status' => 'success', 'success' => true, 'data' => $units]);
    }

    /**
     * GET /api/mikms/units-for-sewa
     * Mengambil unit Finish Goods yang sudah tergenerate, berstatus in_stock,
     * can_rent = true, dan belum terdaftar di tabel assets (untuk opsi input sewa asset).
     */
    public function getUnitsAvailableForSewa(Request $request)
    {
        $registeredSerials = DB::table('assets')->pluck('serial_number')->toArray();

        $query = MikmsUnit::with(['product', 'latestQc'])
            ->where('status', 'in_stock')
            ->whereHas('product', function ($q) {
                $q->where('can_rent', true)->where('active', true);
            })
            ->whereNotIn('serial_number', $registeredSerials);

        if ($request->filled('category') && $request->category !== 'ALL') {
            $cat = strtoupper($request->category);
            $query->whereHas('product', function ($q) use ($cat) {
                $q->where('code', $cat);
            });
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('serial_number', 'like', "%{$s}%")
                  ->orWhereHas('product', function ($pq) use ($s) {
                      $pq->where('name', 'like', "%{$s}%")
                         ->orWhere('code', 'like', "%{$s}%");
                  });
            });
        }

        // Map matching items in database
        $itemMap = [
            'MLK'  => Item::where('kode', 'MLK01')->first() ?? Item::where('nama', 'like', '%Microbit Learning Kit%')->first(),
            'ROBO' => Item::where('kode', 'RE01')->first() ?? Item::where('nama', 'like', '%Robotic Explorer%')->first(),
            'JIMU' => Item::where('kode', 'RJ01')->first() ?? Item::where('nama', 'like', '%Jimu%')->first(),
            'ERBO' => Item::where('kode', 'ERBO01')->first() ?? Item::where('nama', 'like', '%Erboblox%')->first(),
            'ALK'  => Item::where('kode', 'ALK01')->first() ?? Item::where('nama', 'like', '%Arduino Learning Kit%')->first(),
        ];

        $units = $query->orderBy('serial_number', 'asc')->get()->map(function ($u) use ($itemMap) {
            $code = $u->product?->code;
            $matchedItem = $itemMap[$code] ?? null;

            return [
                'id'            => $u->id,
                'serial_number' => $u->serial_number,
                'product_id'    => $u->product_id,
                'product_code'  => $code,
                'product_name'  => $u->product?->name,
                'item_id'       => $matchedItem?->id,
                'item_kode'     => $matchedItem?->kode,
                'item_nama'     => $matchedItem?->nama,
                'status'        => $u->status,
                'qc_status'     => $u->latestQc?->status_qc ?? 'PASS',
                'created_at'    => $u->created_at?->format('Y-m-d H:i'),
            ];
        });

        $categories = MikmsFinishGood::where('can_rent', true)
            ->where('active', true)
            ->select('id', 'code', 'name')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status'     => 'success',
            'success'    => true,
            'categories' => $categories,
            'data'       => $units,
            'total'      => $units->count(),
        ]);
    }

    /**
     * GET /api/mikms/units/{serial}
     * Detail satu unit berdasarkan serial number.
     */
    public function getUnit($serial)
    {
        $unit = MikmsUnit::with(['product', 'qcLogs', 'dispatches', 'parent.product', 'children.product', 'children.latestQc'])
                         ->where('serial_number', strtoupper($serial))
                         ->firstOrFail();

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => [
                'id'            => $unit->id,
                'serial_number' => $unit->serial_number,
                'product'       => $unit->product,
                'status'        => $unit->status,
                'status_label'  => $unit->statusLabel(),
                'source_type'   => $unit->source_type,
                'source_id'     => $unit->source_id,
                'parent'        => $unit->parent ? [
                    'serial_number' => $unit->parent->serial_number,
                    'product_name'  => $unit->parent->product?->name,
                ] : null,
                'children'      => $unit->children->map(fn($c) => [
                    'serial_number' => $c->serial_number,
                    'product_code'  => $c->product?->code,
                    'product_name'  => $c->product?->name,
                    'status'        => $c->status,
                    'status_label'  => $c->statusLabel(),
                    'qc_status'     => $c->latestQc?->status_qc ?? '-',
                ]),
                'qc_logs'       => $unit->qcLogs,
                'dispatches'    => $unit->dispatches,
                'created_at'    => $unit->created_at,
            ],
        ]);
    }

    // =============================================
    // DISPATCH — Pengeluaran Unit dari Gudang
    // =============================================

    /**
     * POST /api/mikms/dispatch
     * Keluarkan unit dari gudang (sewa/beli).
     */
    public function storeDispatch(Request $request)
    {
        $validated = $request->validate([
            'dispatch_date'  => 'required|date',
            'serial_numbers' => 'required|array|min:1',
            'serial_numbers.*' => 'string|exists:mikms_units,serial_number',
            'type'           => 'required|in:sewa,beli',
            'destination'    => 'nullable|string|max:255',
            'dispatched_by'  => 'required|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        $units = MikmsUnit::with(['children', 'product'])->whereIn('serial_number', $validated['serial_numbers'])->get();

        // Validasi: semua unit harus in_stock
        $notReady = $units->filter(fn($u) => $u->status !== 'in_stock');
        if ($notReady->isNotEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unit berikut tidak dalam status in_stock: ' . $notReady->pluck('serial_number')->join(', '),
            ], 422);
        }

        // Validasi: Sewa hanya terjadi di Finish Goods dengan izin sewa (can_rent = true)
        if ($validated['type'] === 'sewa') {
            $cannotRent = $units->filter(fn($u) => $u->product && !$u->product->can_rent);
            if ($cannotRent->isNotEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Sewa hanya terjadi di Finish Goods yang diizinkan untuk disewa. Unit berikut tidak dapat disewakan: ' . $cannotRent->pluck('serial_number')->join(', '),
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $dispatched = [];
            foreach ($units as $unit) {
                $dispatch = MikmsDispatch::create([
                    'dispatch_date' => $validated['dispatch_date'],
                    'unit_id'       => $unit->id,
                    'type'          => $validated['type'],
                    'destination'   => $validated['destination'] ?? null,
                    'dispatched_by' => $validated['dispatched_by'],
                    'notes'         => $validated['notes'] ?? null,
                ]);
                $unit->update(['status' => 'dispatched']);

                // Jika unit bundle, update juga anak unitnya
                foreach ($unit->children as $child) {
                    $child->update(['status' => 'dispatched']);
                }

                $dispatched[] = $unit->serial_number;
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => count($dispatched) . ' unit berhasil di-dispatch (' . $validated['type'] . '): ' . implode(', ', $dispatched),
                'data'    => ['dispatched_serials' => $dispatched],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/mikms/dispatch/return
     * Tandai unit sewa sebagai dikembalikan, set status returned → wajib QC ulang.
     */
    public function returnDispatch(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|exists:mikms_units,serial_number',
            'received_by'   => 'required|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        $unit = MikmsUnit::with('children')->where('serial_number', $validated['serial_number'])->firstOrFail();

        if ($unit->status !== 'dispatched') {
            return response()->json([
                'status'  => 'error',
                'message' => "Unit {$unit->serial_number} tidak dalam status dispatched.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tandai dispatch sebagai returned
            $activeDispatch = MikmsDispatch::where('unit_id', $unit->id)
                                           ->whereNull('returned_at')
                                           ->latest()
                                           ->first();

            if ($activeDispatch && $activeDispatch->type !== 'sewa') {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => "Unit ini berstatus 'beli', tidak bisa diretur.",
                ], 422);
            }

            if ($activeDispatch) {
                $activeDispatch->update([
                    'returned_at' => now(),
                    'notes'       => ($activeDispatch->notes ? $activeDispatch->notes . ' | ' : '') . 'Retur: ' . ($validated['notes'] ?? '-'),
                ]);
            }

            // Unit kembali → wajib QC ulang sebelum bisa in_stock
            $unit->update(['status' => 'returned']);

            // Jika unit bundle, tandai juga children untuk di-QC ulang
            foreach ($unit->children as $child) {
                $child->update(['status' => 'returned']);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "Unit {$unit->serial_number} berhasil diretur. Status: Returned. Wajib QC ulang sebelum masuk stok.",
                'data'    => ['serial_number' => $unit->serial_number, 'status' => 'returned'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =============================================
    // BUNDLING & PACKAGING — Robotic Explorer
    // (1 Unit MLK + 1 Unit JIMU Trackbot)
    // =============================================

    /**
     * GET /api/mikms/bundle/available-units
     * List unit MLK dan JIMU yang berstatus in_stock dan belum terikat bundle.
     */
    public function getAvailableBundleUnits()
    {
        $mlkProduct = MikmsFinishGood::where('code', 'MLK')->first();
        $jimuProduct = MikmsFinishGood::where('code', 'JIMU')->first();

        $mlkUnits = $mlkProduct ? MikmsUnit::where('product_id', $mlkProduct->id)
                                    ->where('status', 'in_stock')
                                    ->whereNull('parent_unit_id')
                                    ->latest()
                                    ->get(['id', 'serial_number']) : [];

        $jimuUnits = $jimuProduct ? MikmsUnit::where('product_id', $jimuProduct->id)
                                      ->where('status', 'in_stock')
                                      ->whereNull('parent_unit_id')
                                      ->latest()
                                      ->get(['id', 'serial_number']) : [];

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'data'    => [
                'mlk'  => $mlkUnits,
                'jimu' => $jimuUnits,
            ],
        ]);
    }

    /**
     * POST /api/mikms/bundle-unit
     * Kemas / pairing 1 unit MLK + 1 unit JIMU menjadi 1 unit Robotic Explorer (ROBO).
     */
    public function storeBundleUnit(Request $request)
    {
        $validated = $request->validate([
            'mlk_serial'   => 'required|string|exists:mikms_units,serial_number',
            'jimu_serial'  => 'required|string|exists:mikms_units,serial_number',
            'packaged_by'  => 'required|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        $mlkUnit = MikmsUnit::where('serial_number', strtoupper($validated['mlk_serial']))->firstOrFail();
        $jimuUnit = MikmsUnit::where('serial_number', strtoupper($validated['jimu_serial']))->firstOrFail();

        // Validasi produk
        if ($mlkUnit->product?->code !== 'MLK') {
            return response()->json(['status' => 'error', 'message' => "Unit {$mlkUnit->serial_number} bukan Microbit Learning Kit (MLK)"], 422);
        }
        if ($jimuUnit->product?->code !== 'JIMU') {
            return response()->json(['status' => 'error', 'message' => "Unit {$jimuUnit->serial_number} bukan Robotik Jimu (JIMU)"], 422);
        }

        // Validasi status in_stock
        if ($mlkUnit->status !== 'in_stock') {
            return response()->json(['status' => 'error', 'message' => "Unit MLK {$mlkUnit->serial_number} belum lolos QC atau tidak in_stock (status: {$mlkUnit->status})"], 422);
        }
        if ($jimuUnit->status !== 'in_stock') {
            return response()->json(['status' => 'error', 'message' => "Unit Jimu {$jimuUnit->serial_number} belum lolos QC atau tidak in_stock (status: {$jimuUnit->status})"], 422);
        }

        // Validasi belum dibundling
        if ($mlkUnit->parent_unit_id) {
            return response()->json(['status' => 'error', 'message' => "Unit MLK {$mlkUnit->serial_number} sudah terikat dalam bundle lain"], 422);
        }
        if ($jimuUnit->parent_unit_id) {
            return response()->json(['status' => 'error', 'message' => "Unit Jimu {$jimuUnit->serial_number} sudah terikat dalam bundle lain"], 422);
        }

        $roboProduct = MikmsFinishGood::where('code', 'ROBO')->firstOrFail();

        DB::beginTransaction();
        try {
            $roboSerial = $roboProduct->nextSerial();

            $roboUnit = MikmsUnit::create([
                'serial_number' => $roboSerial,
                'product_id'    => $roboProduct->id,
                'status'        => 'qc_pending', // Boks packaging luar wajib QC sebelum siap kirim
                'source_type'   => 'bundle',
                'notes'         => "Bundling Kemasan: MLK={$mlkUnit->serial_number} + JIMU={$jimuUnit->serial_number}. Dikemas oleh: {$validated['packaged_by']}." . ($validated['notes'] ? " ({$validated['notes']})" : ''),
            ]);

            $mlkUnit->update([
                'parent_unit_id' => $roboUnit->id,
                'status'         => 'bundled',
            ]);

            $jimuUnit->update([
                'parent_unit_id' => $roboUnit->id,
                'status'         => 'bundled',
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "Boks Packaging Robotic Explorer berhasil dibuat: {$roboSerial} (berisi {$mlkUnit->serial_number} + {$jimuUnit->serial_number}). Status: Menunggu QC.",
                'data'    => [
                    'robo_unit'     => $roboUnit->fresh(['product', 'children.product']),
                    'serial_number' => $roboSerial,
                    'mlk_serial'    => $mlkUnit->serial_number,
                    'jimu_serial'   => $jimuUnit->serial_number,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
