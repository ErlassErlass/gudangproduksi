<?php

namespace App\Exports;

use App\Models\Transaction;
use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GudangScanExport implements WithMultipleSheets
{
    protected ?string $gudang;

    public function __construct(string $gudang = null)
    {
        $this->gudang = $gudang;
    }

    public function sheets(): array
    {
        return [
            new LogMasukExport($this->gudang),
            new LogKeluarExport($this->gudang),
            new RekapStokExport($this->gudang),
        ];
    }
}

class LogMasukExport implements FromCollection, WithTitle, WithHeadings, WithMapping
{
    protected ?string $gudang;

    public function __construct(string $gudang = null)
    {
        $this->gudang = $gudang;
    }

    public function collection()
    {
        $query = Transaction::with('item')->where('tipe', 'masuk');
        if ($this->gudang && $this->gudang !== 'Semua') {
            $query->where('lokasi', $this->gudang);
        }
        return $query->orderBy('transaction_date', 'desc')->get();
    }

    public function title(): string
    {
        return 'Log Masuk';
    }

    public function headings(): array
    {
        return [
            'ID', 'Waktu', 'Kode', 'Nama Barang', 'Produk Kit', 'Qty', 'Satuan', 'Sumber', 'No. Dokumen', 'No. PO', 'No. PRN', 'Job', 'Transfer Order', 'Lokasi Gudang', 'Catatan', 'Petugas'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->transaction_date->format('Y-m-d') . ' ' . $row->created_at->format('H:i:s'),
            $row->item->kode,
            $row->item->nama,
            $row->item->produk,
            $row->qty,
            $row->item->satuan,
            $row->sumber,
            $row->no_dokumen,
            $row->no_po,
            $row->no_prn,
            $row->job_number,
            $row->transfer_order,
            $row->lokasi,
            $row->catatan,
            $row->petugas
        ];
    }
}

class LogKeluarExport implements FromCollection, WithTitle, WithHeadings, WithMapping
{
    protected ?string $gudang;

    public function __construct(string $gudang = null)
    {
        $this->gudang = $gudang;
    }

    public function collection()
    {
        $query = Transaction::with('item')->where('tipe', 'keluar');
        if ($this->gudang && $this->gudang !== 'Semua') {
            $query->where('lokasi', $this->gudang);
        }
        return $query->orderBy('transaction_date', 'desc')->get();
    }

    public function title(): string
    {
        return 'Log Keluar';
    }

    public function headings(): array
    {
        return [
            'ID', 'Waktu', 'Kode', 'Nama Barang', 'Produk Kit', 'Qty', 'Satuan', 'Penerima', 'Keperluan', 'No. Dokumen', 'No. PO', 'No. PRN', 'Job', 'Transfer Order', 'Lokasi Gudang', 'Catatan', 'Petugas'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->transaction_date->format('Y-m-d') . ' ' . $row->created_at->format('H:i:s'),
            $row->item->kode,
            $row->item->nama,
            $row->item->produk,
            $row->qty,
            $row->item->satuan,
            $row->penerima,
            $row->keperluan,
            $row->no_dokumen,
            $row->no_po,
            $row->no_prn,
            $row->job_number,
            $row->transfer_order,
            $row->lokasi,
            $row->catatan,
            $row->petugas
        ];
    }
}

class RekapStokExport implements FromCollection, WithTitle, WithHeadings, WithMapping
{
    protected ?string $gudang;

    public function __construct(string $gudang = null)
    {
        $this->gudang = $gudang;
    }

    public function collection()
    {
        return Item::all();
    }

    public function title(): string
    {
        return 'Rekap Stok';
    }

    public function headings(): array
    {
        return [
            'Kode', 'Nama Barang', 'Produk Kit', 'Satuan', 'Total Masuk', 'Total Keluar', 'Stok Akhir'
        ];
    }

    public function map($row): array
    {
        $masuk = $row->getTotalMasuk($this->gudang);
        $keluar = $row->getTotalKeluar($this->gudang);
        return [
            $row->kode,
            $row->nama,
            $row->produk,
            $row->satuan,
            $row->getTotalMasuk($this->gudang),
            $row->getTotalKeluar($this->gudang),
            $masuk - $keluar
        ];
    }
}
