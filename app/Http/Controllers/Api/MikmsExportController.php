<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\MikmsProduction;
use App\Models\MikmsQcLog;
use App\Models\MikmsShipment;
use App\Models\MikmsReturn;
use App\Models\MikmsRepair;
use App\Models\MikmsStockOpname;

class MikmsExportController extends Controller
{
    /**
     * Download Rekap Excel MIKMS berbasis template resmi MIKMS_Form_Lapangan.xlsx
     */
    public function exportExcel()
    {
        $templatePath = '/root/gudangscan/MIKMS_Form_Lapangan.xlsx';
        if (!file_exists($templatePath)) {
            $templatePath = base_path('MIKMS_Form_Lapangan.xlsx');
        }

        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
        } else {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        }

        // 1. Isi Form Produksi Modul (Sheet 5)
        $sheetProduksi = $spreadsheet->getSheetByName('Form Produksi Modul');
        if ($sheetProduksi) {
            $productions = MikmsProduction::with('module')->orderBy('production_date')->get();
            $row = 6;
            $no = 1;
            foreach ($productions as $p) {
                $sheetProduksi->setCellValue("A{$row}", $no++);
                $sheetProduksi->setCellValue("B{$row}", $p->production_date);
                $sheetProduksi->setCellValue("C{$row}", $p->module ? $p->module->code : '');
                $sheetProduksi->setCellValue("D{$row}", $p->module ? $p->module->name : '');
                $sheetProduksi->setCellValue("H{$row}", $p->quantity_produced);
                $sheetProduksi->setCellValue("I{$row}", $p->produced_by);
                $sheetProduksi->setCellValue("J{$row}", $p->notes);
                $row++;
            }
        }

        // 2. Isi Form QC Modul (Sheet 6)
        $sheetQc = $spreadsheet->getSheetByName('Form QC Modul');
        if ($sheetQc) {
            $qcs = MikmsQcLog::with('module')->orderBy('qc_date')->get();
            $row = 6;
            $no = 1;
            foreach ($qcs as $q) {
                $sheetQc->setCellValue("A{$row}", $no++);
                $sheetQc->setCellValue("B{$row}", $q->qc_date);
                $sheetQc->setCellValue("C{$row}", $q->module ? $q->module->code : '');
                $sheetQc->setCellValue("D{$row}", $q->module ? $q->module->name : '');
                $sheetQc->setCellValue("E{$row}", $q->target_box_code);
                $sheetQc->setCellValue("F{$row}", $q->status_qc);
                $sheetQc->setCellValue("G{$row}", $q->defect_notes);
                $sheetQc->setCellValue("H{$row}", $q->checked_by);
                $sheetQc->setCellValue("I{$row}", $q->notes);
                $row++;
            }
        }

        // 3. Isi Form Pengiriman Sekolah (Sheet 7)
        $sheetKirim = $spreadsheet->getSheetByName('Form Pengiriman Sekolah');
        if ($sheetKirim) {
            $shipments = MikmsShipment::orderBy('shipment_date')->get();
            $row = 6;
            $no = 1;
            foreach ($shipments as $s) {
                $sheetKirim->setCellValue("A{$row}", $no++);
                $sheetKirim->setCellValue("B{$row}", $s->shipment_date);
                $sheetKirim->setCellValue("C{$row}", $s->box_code);
                $sheetKirim->setCellValue("D{$row}", $s->program_code);
                $sheetKirim->setCellValue("E{$row}", $s->program_name);
                $sheetKirim->setCellValue("F{$row}", $s->school_name);
                $sheetKirim->setCellValue("G{$row}", $s->quantity_box);
                $sheetKirim->setCellValue("H{$row}", $s->shipped_by);
                $sheetKirim->setCellValue("I{$row}", $s->received_by_school);
                $sheetKirim->setCellValue("J{$row}", $s->notes);
                $row++;
            }
        }

        // 4. Isi Form Pengembalian (Sheet 8)
        $sheetKembali = $spreadsheet->getSheetByName('Form Pengembalian');
        if ($sheetKembali) {
            $returns = MikmsReturn::orderBy('return_date')->get();
            $row = 6;
            $no = 1;
            foreach ($returns as $r) {
                $sheetKembali->setCellValue("A{$row}", $no++);
                $sheetKembali->setCellValue("B{$row}", $r->return_date);
                $sheetKembali->setCellValue("C{$row}", $r->box_code);
                $sheetKembali->setCellValue("D{$row}", $r->school_name);
                $sheetKembali->setCellValue("E{$row}", $r->condition);
                $sheetKembali->setCellValue("F{$row}", $r->problematic_item_code);
                $sheetKembali->setCellValue("G{$row}", $r->problematic_item_name);
                $sheetKembali->setCellValue("H{$row}", $r->problematic_quantity);
                $sheetKembali->setCellValue("I{$row}", $r->received_by);
                $sheetKembali->setCellValue("J{$row}", $r->notes);
                $row++;
            }
        }

        // 5. Isi Form Repair (Sheet 9)
        $sheetRepair = $spreadsheet->getSheetByName('Form Repair');
        if ($sheetRepair) {
            $repairs = MikmsRepair::orderBy('repair_date')->get();
            $row = 6;
            $no = 1;
            foreach ($repairs as $rp) {
                $sheetRepair->setCellValue("A{$row}", $no++);
                $sheetRepair->setCellValue("B{$row}", $rp->repair_date);
                $sheetRepair->setCellValue("C{$row}", $rp->item_code);
                $sheetRepair->setCellValue("D{$row}", $rp->item_name);
                $sheetRepair->setCellValue("E{$row}", $rp->asset_id);
                $sheetRepair->setCellValue("F{$row}", $rp->damage_type);
                $sheetRepair->setCellValue("G{$row}", $rp->repair_action);
                $sheetRepair->setCellValue("H{$row}", $rp->quantity);
                $sheetRepair->setCellValue("I{$row}", $rp->repair_result);
                $sheetRepair->setCellValue("J{$row}", $rp->repaired_by);
                $sheetRepair->setCellValue("K{$row}", $rp->notes);
                $row++;
            }
        }

        // 6. Isi Form Stock Opname (Sheet 10)
        $sheetOpname = $spreadsheet->getSheetByName('Form Stock Opname');
        if ($sheetOpname) {
            $opnames = MikmsStockOpname::orderBy('opname_date')->get();
            $row = 6;
            $no = 1;
            foreach ($opnames as $o) {
                $sheetOpname->setCellValue("A{$row}", $no++);
                $sheetOpname->setCellValue("B{$row}", $o->opname_date);
                $sheetOpname->setCellValue("C{$row}", $o->item_code);
                $sheetOpname->setCellValue("D{$row}", $o->item_name);
                $sheetOpname->setCellValue("E{$row}", $o->system_quantity);
                $sheetOpname->setCellValue("F{$row}", $o->physical_quantity);
                $sheetOpname->setCellValue("G{$row}", $o->difference);
                $sheetOpname->setCellValue("H{$row}", $o->difference_reason);
                $sheetOpname->setCellValue("I{$row}", $o->counted_by);
                $row++;
            }
        }

        $filename = 'MIKMS_Form_Lapangan_Export_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
