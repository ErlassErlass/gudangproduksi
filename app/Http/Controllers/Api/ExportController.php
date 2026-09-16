<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Exports\GudangScanExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Export all transaction logs and current stock levels to Excel.
     */
    public function exportExcel(Request $request)
    {
        $gudang = $request->input('gudang');
        
        $suffix = $gudang ? '_' . str_replace(' ', '_', $gudang) : '';
        $filename = 'ErlassGudangApp' . $suffix . '_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new GudangScanExport($gudang), $filename);
    }

    /**
     * Export Stock Card (Kartu Stok) of an item to PDF.
     */
    public function exportPdf(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);
        $year = $request->input('tahun', now()->year);
        $gudang = $request->input('gudang');

        $kartuStokData = $item->getKartuStok((int) $year, $gudang);

        $pdf = Pdf::loadView('pdf.kartu-stok', [
            'item' => $kartuStokData['item'],
            'year' => $kartuStokData['year'],
            'gudang' => $kartuStokData['gudang'],
            'opening_balance' => $kartuStokData['opening_balance'],
            'rows' => $kartuStokData['rows'],
        ]);

        // Set paper size to A4
        $pdf->setPaper('a4', 'portrait');

        $gudangSuffix = $gudang ? '_' . str_replace(' ', '_', $gudang) : '';
        $filename = 'KartuStok_' . $item->kode . $gudangSuffix . '_' . $year . '.pdf';
        
        return $pdf->download($filename);
    }
}
