<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['item', 'user']);

        if ($request->has('tipe') && in_array($request->tipe, ['masuk', 'keluar'])) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // Limit or paginate
        $limit = $request->input('limit', 100);
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Store a newly created transaction or bulk sync from offline app.
     */
    public function store(Request $request)
    {
        // Support both single and bulk input
        $data = $request->all();

        if (isset($data['transactions']) && is_array($data['transactions'])) {
            return $this->bulkStore($data['transactions'], $request->user()->name ?? 'System');
        }

        return $this->singleStore($request, $request->user()->name ?? 'System');
    }

    /**
     * Handle single transaction submission.
     */
    private function singleStore(Request $request, string $petugasDefault)
    {
        $validated = $request->validate([
            'kode' => 'required|string|exists:items,kode',
            'tipe' => 'required|string|in:masuk,keluar',
            'qty' => 'required|integer|min:1',
            'transaction_date' => 'nullable|date',
            'no_dokumen' => 'nullable|string|max:50',
            'no_po' => 'nullable|string|max:50',
            'no_prn' => 'nullable|string|max:50',
            'job_number' => 'nullable|string|max:50',
            'transfer_order' => 'nullable|string|max:50',
            'sumber' => 'nullable|string|max:100',
            'penerima' => 'nullable|string|max:100',
            'keperluan' => 'nullable|string|max:100',
            'lokasi' => 'nullable|string|max:50',
            'petugas' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
            'client_id' => 'nullable|string|max:50|unique:transactions,client_id',
        ]);

        $item = Item::where('kode', $validated['kode'])->firstOrFail();

        // Check stock availability if it's "keluar"
        if ($validated['tipe'] === 'keluar') {
            $stok = $item->stok;
            if ($stok < $validated['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok tidak cukup untuk {$item->kode}. Tersedia: {$stok}",
                ], 422);
            }
        }

        $validated['item_id'] = $item->id;
        $validated['user_id'] = $request->user() ? $request->user()->id : null;
        $validated['transaction_date'] = $validated['transaction_date'] ?? now()->format('Y-m-d');
        $validated['petugas'] = $validated['petugas'] ?? ($request->user() ? $request->user()->name : $petugasDefault);

        // Clean unused properties
        unset($validated['kode']);

        $transaction = Transaction::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat',
            'data' => $transaction->load('item'),
        ], 201);
    }

    /**
     * Handle bulk offline sync.
     */
    private function bulkStore(array $transactions, string $petugasDefault)
    {
        $inserted = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($transactions as $tx) {
                // Check if already processed (de-duplication via client_id)
                if (isset($tx['client_id']) && Transaction::where('client_id', $tx['client_id'])->exists()) {
                    continue; // Skip silently if already sync'd
                }

                $item = Item::where('kode', $tx['kode'] ?? '')->first();
                if (!$item) {
                    $failed[] = [
                        'tx' => $tx,
                        'reason' => 'Kode barang tidak ditemukan di Master Data',
                    ];
                    continue;
                }

                // Check stock for "keluar" type
                if (($tx['tipe'] ?? '') === 'keluar') {
                    $stok = $item->stok;
                    $qty = (int) ($tx['qty'] ?? 0);
                    if ($stok < $qty) {
                        $failed[] = [
                            'tx' => $tx,
                            'reason' => "Stok tidak mencukupi untuk {$item->kode}. Tersedia: {$stok}",
                        ];
                        continue;
                    }
                }

                // Map fields
                $entry = Transaction::create([
                    'user_id' => auth()->id(),
                    'item_id' => $item->id,
                    'tipe' => $tx['tipe'] ?? 'masuk',
                    'qty' => (int) ($tx['qty'] ?? 1),
                    'transaction_date' => isset($tx['transaction_date']) ? date('Y-m-d', strtotime($tx['transaction_date'])) : now()->format('Y-m-d'),
                    'no_dokumen' => $tx['no_dokumen'] ?? null,
                    'no_po' => $tx['no_po'] ?? null,
                    'no_prn' => $tx['no_prn'] ?? null,
                    'job_number' => $tx['job_number'] ?? null,
                    'transfer_order' => $tx['transfer_order'] ?? null,
                    'sumber' => $tx['sumber'] ?? null,
                    'penerima' => $tx['penerima'] ?? null,
                    'keperluan' => $tx['keperluan'] ?? null,
                    'lokasi' => $tx['lokasi'] ?? null,
                    'petugas' => $tx['petugas'] ?? $petugasDefault,
                    'catatan' => $tx['catatan'] ?? null,
                    'client_id' => $tx['client_id'] ?? null,
                ]);

                $inserted[] = $entry->id;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proses sinkronisasi selesai',
                'synced_count' => count($inserted),
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses sinkronisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction statistics.
     */
    public function getStats()
    {
        $today = now()->format('Y-m-d');
        
        $totalMasuk = Transaction::where('tipe', 'masuk')->sum('qty');
        $totalKeluar = Transaction::where('tipe', 'keluar')->sum('qty');
        $transaksiHariIni = Transaction::whereDate('transaction_date', $today)->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_masuk' => (int) $totalMasuk,
                'total_keluar' => (int) $totalKeluar,
                'transaksi_hari_ini' => (int) $transaksiHariIni,
            ],
        ]);
    }
}
