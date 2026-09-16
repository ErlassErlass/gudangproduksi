<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Get stock levels and overview.
     */
    public function index(Request $request)
    {
        $gudang = $request->input('gudang');

        // Fetch sum of masuk quantities grouped by item_id
        $masukQuery = DB::table('transactions')
            ->select('item_id', DB::raw('SUM(qty) as total_masuk'))
            ->where('tipe', 'masuk');
        if ($gudang && $gudang !== 'Semua') {
            $masukQuery->where('lokasi', $gudang);
        }
        $masukSums = $masukQuery->groupBy('item_id')->pluck('total_masuk', 'item_id');

        // Fetch sum of keluar quantities grouped by item_id
        $keluarQuery = DB::table('transactions')
            ->select('item_id', DB::raw('SUM(qty) as total_keluar'))
            ->where('tipe', 'keluar');
        if ($gudang && $gudang !== 'Semua') {
            $keluarQuery->where('lokasi', $gudang);
        }
        $keluarSums = $keluarQuery->groupBy('item_id')->pluck('total_keluar', 'item_id');

        $items = Item::with('category')->get()->map(function ($item) use ($masukSums, $keluarSums) {
            $masuk  = (int) ($masukSums[$item->id] ?? 0);
            $keluar = (int) ($keluarSums[$item->id] ?? 0);
            $stok   = $masuk - $keluar;

            return [
                'id'          => $item->id,
                'kode'        => $item->kode,
                'nama'        => $item->nama,
                'satuan'      => $item->satuan,
                'jenis_barang'=> $item->jenis_barang,
                'produk'      => $item->produk,
                'komponen'    => $item->komponen,
                'category'    => $item->category,
                'min_stok'    => $item->min_stok,
                'masuk'       => $masuk,
                'keluar'      => $keluar,
                'stok'        => $stok,
                'is_warning'  => ($stok > 0 && $stok <= $item->min_stok),
                'is_empty'    => ($stok <= 0),
            ];
        });

        $totalItems   = $items->count();
        $stokKosong   = $items->filter(fn($i) => $i['is_empty'])->count();
        $stokMenipis  = $items->filter(fn($i) => !$i['is_empty'] && $i['is_warning'])->count();

        return response()->json([
            'success' => true,

            'data'    => [
                'items'   => $items,
                'summary' => [
                    'total_barang'  => $totalItems,
                    'stok_kosong'   => $stokKosong,
                    'stok_menipis'  => $stokMenipis,
                ]
            ]
        ]);
    }

    /**
     * Get stock card (Kartu Stok) data.
     */
    public function getCard(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);
        $year = $request->input('tahun', now()->year);
        $gudang = $request->input('gudang');

        $kartuStok = $item->getKartuStok((int) $year, $gudang);

        return response()->json([
            'success' => true,
            'data' => $kartuStok,
        ]);
    }
}
