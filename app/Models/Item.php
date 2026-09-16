<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'kode', 'nama', 'satuan', 'jenis_barang', 'upc_barcode',
        'produk', 'komponen', 'lokasi_default', 'min_stok', 'deskripsi', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_stok' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Calculate current stock for this item, optionally filtered by warehouse.
     */
    public function getStok(string $gudang = null): int
    {
        return $this->getTotalMasuk($gudang) - $this->getTotalKeluar($gudang);
    }

    /**
     * Helper for default attribute.
     */
    public function getStokAttribute(): int
    {
        return $this->getStok();
    }

    public function getTotalMasuk(string $gudang = null): int
    {
        $query = $this->transactions()->where('tipe', 'masuk');
        if ($gudang && $gudang !== 'Semua') {
            $query->where('lokasi', $gudang);
        }
        return (int) $query->sum('qty');
    }

    public function getTotalKeluar(string $gudang = null): int
    {
        $query = $this->transactions()->where('tipe', 'keluar');
        if ($gudang && $gudang !== 'Semua') {
            $query->where('lokasi', $gudang);
        }
        return (int) $query->sum('qty');
    }

    /**
     * Get stock card data (Kartu Stok) for a specific year and optional warehouse.
     */
    public function getKartuStok(int $year = null, string $gudang = null): array
    {
        $year = $year ?? now()->year;

        $txQuery = $this->transactions()
            ->whereYear('transaction_date', $year);

        if ($gudang && $gudang !== 'Semua') {
            $txQuery->where('lokasi', $gudang);
        }

        $transactions = $txQuery->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        // Calculate opening balance (all transactions before this year)
        $openingMasukQuery = $this->transactions()
            ->where('tipe', 'masuk')
            ->where('transaction_date', '<', "$year-01-01");
        
        $openingKeluarQuery = $this->transactions()
            ->where('tipe', 'keluar')
            ->where('transaction_date', '<', "$year-01-01");

        if ($gudang && $gudang !== 'Semua') {
            $openingMasukQuery->where('lokasi', $gudang);
            $openingKeluarQuery->where('lokasi', $gudang);
        }

        $openingMasuk = (int) $openingMasukQuery->sum('qty');
        $openingKeluar = (int) $openingKeluarQuery->sum('qty');
        $openingBalance = $openingMasuk - $openingKeluar;

        $rows = [];
        $runningBalance = $openingBalance;

        // Add opening balance row
        $rows[] = [
            'tanggal' => "$year-01-01",
            'tanggal_formatted' => '01-Jan-' . substr($year, 2),
            'masuk' => 0,
            'keluar' => 0,
            'sisa_akhir' => $runningBalance,
            'no_po' => '',
            'no_prn' => '',
            'job_number' => '',
            'transfer_order' => '',
            'user_pemasok' => '',
            'lokasi' => $gudang ?? 'Semua',
            'pic' => '',
            'keterangan' => 'SALDO AWAL ' . $year,
        ];

        foreach ($transactions as $tx) {
            $masuk = $tx->tipe === 'masuk' ? $tx->qty : 0;
            $keluar = $tx->tipe === 'keluar' ? $tx->qty : 0;
            $runningBalance += $masuk - $keluar;

            $rows[] = [
                'tanggal' => $tx->transaction_date->format('Y-m-d'),
                'tanggal_formatted' => $tx->transaction_date->format('d-M-y'),
                'masuk' => $masuk,
                'keluar' => $keluar,
                'sisa_akhir' => $runningBalance,
                'no_po' => $tx->no_po ?? '',
                'no_prn' => $tx->no_prn ?? '',
                'job_number' => $tx->job_number ?? '',
                'transfer_order' => $tx->transfer_order ?? '',
                'user_pemasok' => $tx->tipe === 'masuk' ? ($tx->sumber ?? '') : ($tx->penerima ?? ''),
                'lokasi' => $tx->lokasi ?? '',
                'pic' => $tx->petugas ?? '',
                'keterangan' => $tx->catatan ?? '',
            ];
        }

        return [
            'item' => $this,
            'year' => $year,
            'gudang' => $gudang ?? 'Semua',
            'opening_balance' => $openingBalance,
            'rows' => $rows,
        ];
    }
}
