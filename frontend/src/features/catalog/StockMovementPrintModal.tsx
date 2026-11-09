import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Printer, RefreshCw } from 'lucide-react';
import { http } from '@/api/client';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { EmptyState, LoadingState } from '@/components/States';
import { formatDateTime, formatNumber } from '@/lib/format';
import type { ProductItem } from '@/features/master-data/api';

interface StockMovementPrintModalProps {
  product: ProductItem;
  isOpen: boolean;
  onClose: () => void;
}

interface StockCardResponse {
  product: {
    id: number;
    code: string;
    name: string;
    uom: string;
  };
  from_date: string | null;
  to_date: string | null;
  period_opening_balance: string;
  total_in: string;
  total_out: string;
  closing_balance: string;
  movements: {
    id: number;
    document_no: string;
    transaction_type: string;
    transaction_date: string;
    from_location: string | null;
    to_location: string | null;
    quantity: string;
    in_qty: string;
    out_qty: string;
    delta: string;
    opening_balance: string;
    closing_balance: string;
    unit_cost: string | null;
    notes: string | null;
    posted_by: string | null;
  }[];
}

export function StockMovementPrintModal({ product, isOpen, onClose }: StockMovementPrintModalProps) {
  // Default range: 30 days ago until today
  const defaultFrom = useMemo(() => {
    const d = new Date();
    d.setDate(d.getDate() - 30);
    return d.toISOString().split('T')[0];
  }, []);

  const defaultTo = useMemo(() => {
    return new Date().toISOString().split('T')[0];
  }, []);

  const [fromDate, setFromDate] = useState(defaultFrom);
  const [toDate, setToDate] = useState(defaultTo);

  const { data, isLoading, refetch, isFetching } = useQuery({
    queryKey: ['inventory', 'stock-card', product.id, fromDate, toDate],
    enabled: isOpen,
    queryFn: async () => {
      const res = await http.get<{ data: StockCardResponse }>('/inventory/stock-card', {
        params: {
          product_id: product.id,
          from_date: fromDate || undefined,
          to_date: toDate || undefined,
        },
      });
      return res.data.data;
    },
  });

  // Cetak terisolasi: hanya mencetak kartu riwayat keluar masuk barang, halaman aktif tidak akan ikut tercetak
  const handlePrint = () => {
    if (!data) return;

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) return;

    const rowsHtml = data.movements
      .map((m) => {
        const hasIn = Number(m.in_qty) > 0;
        const hasOut = Number(m.out_qty) > 0;
        const loc = m.from_location ? `${m.from_location} &rarr; ` : '';
        const toLoc = m.to_location ? m.to_location : '—';

        return `
          <tr>
            <td style="font-family: monospace; white-space: nowrap;">${formatDateTime(m.transaction_date)}</td>
            <td><code style="font-size: 8.5pt;">${m.document_no}</code></td>
            <td>${m.transaction_type}</td>
            <td>${loc}${toLoc}</td>
            <td style="text-align: right; font-family: monospace; font-weight: ${hasIn ? 'bold' : 'normal'}; color: ${hasIn ? '#15803d' : '#64748b'};">
              ${hasIn ? `+${formatNumber(m.in_qty)}` : '—'}
            </td>
            <td style="text-align: right; font-family: monospace; font-weight: ${hasOut ? 'bold' : 'normal'}; color: ${hasOut ? '#b91c1c' : '#64748b'};">
              ${hasOut ? `-${formatNumber(m.out_qty)}` : '—'}
            </td>
            <td style="text-align: right; font-family: monospace; font-weight: bold; color: #1e3a8a;">
              ${formatNumber(m.closing_balance)}
            </td>
            <td style="font-size: 8pt; color: #475569;">
              ${m.notes || '—'}
              ${m.posted_by ? `<br/><small>Oleh: ${m.posted_by}</small>` : ''}
            </td>
          </tr>
        `;
      })
      .join('');

    doc.open();
    doc.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Kartu Riwayat Keluar Masuk - ${product.name}</title>
          <style>
            @page {
              size: A4 landscape;
              margin: 12mm 10mm;
            }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
              margin: 0;
              padding: 0;
              background: #fff;
              color: #0f172a;
              font-size: 9pt;
              line-height: 1.4;
            }
            .header-table {
              width: 100%;
              border-bottom: 2px solid #2563eb;
              padding-bottom: 8px;
              margin-bottom: 12px;
            }
            .company {
              font-size: 14pt;
              font-weight: 800;
              color: #1e3a8a;
              margin: 0;
            }
            .subtitle {
              font-size: 8pt;
              color: #64748b;
              margin-top: 2px;
            }
            .doc-title {
              text-align: right;
              font-size: 12pt;
              font-weight: 800;
              color: #0f172a;
            }
            .meta-grid {
              display: grid;
              grid-template-columns: repeat(4, 1fr);
              gap: 8px;
              margin-bottom: 12px;
            }
            .meta-card {
              border: 1px solid #cbd5e1;
              border-radius: 6px;
              padding: 6px 10px;
              background: #f8fafc;
            }
            .meta-label {
              font-size: 7.5pt;
              color: #64748b;
              text-transform: uppercase;
            }
            .meta-val {
              font-size: 11pt;
              font-weight: 800;
              font-family: monospace;
              margin-top: 2px;
            }
            table.data {
              width: 100%;
              border-collapse: collapse;
              margin-top: 8px;
            }
            table.data th {
              background: #f1f5f9;
              border: 1px solid #cbd5e1;
              padding: 6px 8px;
              font-size: 8pt;
              text-align: left;
            }
            table.data td {
              border: 1px solid #e2e8f0;
              padding: 5px 8px;
              font-size: 8.5pt;
            }
            .footer-sign {
              margin-top: 24px;
              display: flex;
              justify-content: space-between;
              padding: 0 40px;
              page-break-inside: avoid;
            }
            .sign-box {
              text-align: center;
              font-size: 8pt;
            }
            .sign-line {
              margin-top: 50px;
              width: 160px;
              border-bottom: 1px solid #000;
            }
          </style>
        </head>
        <body>
          <table class="header-table">
            <tr>
              <td>
                <div class="company">ERLASS Prokreatif Indonesia</div>
                <div class="subtitle">Production & Warehouse Management System</div>
              </td>
              <td class="doc-title">
                KARTU RIWAYAT KELUAR MASUK BARANG
                <div style="font-size: 8pt; font-weight: normal; color: #475569; margin-top: 2px;">
                  Periode: <strong>${fromDate || 'Awal'}</strong> s/d <strong>${toDate || 'Hari ini'}</strong>
                </div>
              </td>
            </tr>
          </table>

          <div style="margin-bottom: 8px; font-size: 9pt;">
            Produk: <strong>[${product.code}] ${product.name}</strong> &bull; Satuan: <strong>${data.product.uom}</strong>
          </div>

          <div class="meta-grid">
            <div class="meta-card">
              <div class="meta-label">Saldo Awal Periode</div>
              <div class="meta-val" style="color: #475569;">${formatNumber(data.period_opening_balance)}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Total Masuk (+)</div>
              <div class="meta-val" style="color: #15803d;">+${formatNumber(data.total_in)}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Total Keluar (-)</div>
              <div class="meta-val" style="color: #b91c1c;">-${formatNumber(data.total_out)}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Saldo Akhir Periode</div>
              <div class="meta-val" style="color: #1e3a8a;">${formatNumber(data.closing_balance)}</div>
            </div>
          </div>

          <table class="data">
            <thead>
              <tr>
                <th style="width: 120px;">Tanggal & Waktu</th>
                <th style="width: 140px;">No. Dokumen</th>
                <th style="width: 120px;">Tipe Transaksi</th>
                <th>Lokasi / Aliran</th>
                <th style="width: 80px; text-align: right;">Masuk</th>
                <th style="width: 80px; text-align: right;">Keluar</th>
                <th style="width: 90px; text-align: right;">Saldo</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              ${rowsHtml || '<tr><td colspan="8" style="text-align: center; padding: 14px; color: #64748b;">Tidak ada mutasi transaksi pada rentang tanggal ini.</td></tr>'}
            </tbody>
          </table>

          <div class="footer-sign">
            <div class="sign-box">
              <div>Petugas Gudang / Mutasi</div>
              <div class="sign-line"></div>
              <div style="margin-top: 4px;">Tanggal: ${new Date().toLocaleDateString('id-ID')}</div>
            </div>
            <div class="sign-box">
              <div>Penanggung Jawab / Kepala Gudang</div>
              <div class="sign-line"></div>
              <div style="margin-top: 4px;">ERLASS Prokreatif Indonesia</div>
            </div>
          </div>
        </body>
      </html>
    `);
    doc.close();

    setTimeout(() => {
      iframe.contentWindow?.focus();
      iframe.contentWindow?.print();
      setTimeout(() => {
        document.body.removeChild(iframe);
      }, 1000);
    }, 400);
  };

  const getTransactionBadge = (type: string) => {
    switch (type) {
      case 'PRODUCTION_OUTPUT':
        return <span className="chip" style={{ background: '#dcfce7', color: '#15803d' }}>Perakitan BOM</span>;
      case 'DISTRIBUTION_DISPATCH':
        return <span className="chip" style={{ background: '#fee2e2', color: '#b91c1c' }}>Pengeluaran Barcode FG</span>;
      case 'PURCHASE_RECEIPT':
        return <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1' }}>Penerimaan PO</span>;
      case 'SALE_DELIVERY':
      case 'RENTAL_DISPATCH':
        return <span className="chip" style={{ background: '#fee2e2', color: '#b91c1c' }}>Pengeluaran/Kirim</span>;
      case 'STOCK_ADJUSTMENT':
      case 'ADJUSTMENT':
        return <span className="chip" style={{ background: '#fef3c7', color: '#b45309' }}>Penyesuaian</span>;
      case 'TRANSFER':
        return <span className="chip" style={{ background: '#f3e8ff', color: '#7e22ce' }}>Transfer Lokasi</span>;
      default:
        return <span className="chip">{type}</span>;
    }
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Riwayat Keluar Masuk: ${product.name}`}
      description={`Laporan mutasi keluar masuk barang dan kartu stok untuk kode [${product.code}].`}
      size="xl"
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%', alignItems: 'center' }}>
          <span className="table__muted" style={{ fontSize: '0.8rem' }}>
            ERLASS Prokreatif Indonesia &bull; Production & Warehouse Management
          </span>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button variant="secondary" onClick={onClose}>
              Tutup
            </Button>
            <Button variant="primary" icon={<Printer size={16} />} onClick={handlePrint} disabled={!data}>
              Cetak Riwayat (Print)
            </Button>
          </div>
        </div>
      }
    >
      <div className="printable-stock-card" style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* Filter Rentang Tanggal Toolbar */}
        <div
          className="no-print"
          style={{
            display: 'flex',
            flexWrap: 'wrap',
            alignItems: 'center',
            gap: 12,
            padding: 12,
            borderRadius: 'var(--radius-sm, 8px)',
            background: 'var(--color-surface-muted, #f7f9fe)',
            border: '1px solid var(--color-border, #e1e8f5)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            <Calendar size={16} color="var(--color-primary, #3859ac)" />
            <span style={{ fontSize: '0.85rem', fontWeight: 600 }}>Filter Rentang Tanggal:</span>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            <input
              type="date"
              className="input select--sm"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              aria-label="Dari tanggal"
            />
            <span>s/d</span>
            <input
              type="date"
              className="input select--sm"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              aria-label="Sampai tanggal"
            />
          </div>

          <Button size="sm" icon={<RefreshCw size={13} />} onClick={() => refetch()} loading={isFetching}>
            Terapkan Filter
          </Button>
        </div>

        {/* Header Dokumen Cetak */}
        <div
          style={{
            borderBottom: '2px solid var(--color-primary, #3859ac)',
            paddingBottom: 12,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-start',
          }}
        >
          <div>
            <h2 style={{ margin: 0, fontSize: '1.25rem', fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)' }}>
              KARTU RIWAYAT KELUAR MASUK BARANG
            </h2>
            <div style={{ color: 'var(--color-text-secondary, #64748b)', fontSize: '0.85rem', marginTop: 4 }}>
              Produk: <strong>[{product.code}] {product.name}</strong> &bull; Satuan: <strong>{data?.product.uom ?? product.base_uom?.name ?? 'Unit'}</strong>
            </div>
          </div>

          <div style={{ textAlign: 'right' }}>
            <div style={{ fontSize: '0.75rem', fontWeight: 700, textTransform: 'uppercase', color: 'var(--color-primary, #3859ac)' }}>
              ERLASS Prokreatif Indonesia
            </div>
            <div style={{ fontSize: '0.8rem', color: '#64748b' }}>
              Filter: {fromDate || 'Awal'} s/d {toDate || 'Hari ini'}
            </div>
          </div>
        </div>

        {/* State Loading */}
        {isLoading && <LoadingState label="Memuat kartu riwayat stok..." />}

        {/* Ringkasan Saldo dan Mutasi Periode Ini */}
        {data && (
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))',
              gap: 12,
              padding: 12,
              borderRadius: 8,
              background: 'var(--color-surface-muted, #f7f9fe)',
              border: '1px solid var(--color-border, #e1e8f5)',
            }}
          >
            <div>
              <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Saldo Awal Periode</div>
              <div style={{ fontSize: '1.15rem', fontWeight: 700, fontFamily: 'monospace' }}>
                {formatNumber(data.period_opening_balance)}
              </div>
            </div>

            <div>
              <div style={{ fontSize: '0.75rem', color: '#15803d' }}>Total Masuk (+)</div>
              <div style={{ fontSize: '1.15rem', fontWeight: 700, fontFamily: 'monospace', color: '#15803d' }}>
                +{formatNumber(data.total_in)}
              </div>
            </div>

            <div>
              <div style={{ fontSize: '0.75rem', color: '#b91c1c' }}>Total Keluar (-)</div>
              <div style={{ fontSize: '1.15rem', fontWeight: 700, fontFamily: 'monospace', color: '#b91c1c' }}>
                -{formatNumber(data.total_out)}
              </div>
            </div>

            <div>
              <div style={{ fontSize: '0.75rem', color: 'var(--color-primary, #3859ac)' }}>Saldo Akhir Periode</div>
              <div
                style={{
                  fontSize: '1.15rem',
                  fontWeight: 800,
                  fontFamily: 'monospace',
                  color: 'var(--color-primary, #3859ac)',
                }}
              >
                {formatNumber(data.closing_balance)}
              </div>
            </div>
          </div>
        )}

        {/* Tabel Mutasi Stok */}
        {data && data.movements.length === 0 ? (
          <EmptyState
            title="Tidak ada mutasi pada rentang tanggal ini"
            description="Tidak ditemukan catatan keluar masuk barang untuk rentang tanggal yang dipilih. Ubah filter rentang tanggal di atas."
          />
        ) : data && (
          <div className="table-wrap">
            <table className="table" style={{ fontSize: '0.825rem' }}>
              <thead>
                <tr>
                  <th style={{ width: '125px' }}>Tanggal</th>
                  <th style={{ width: '150px' }}>No. Dokumen</th>
                  <th style={{ width: '130px' }}>Tipe Transaksi</th>
                  <th>Lokasi / Referensi</th>
                  <th style={{ width: '90px', textAlign: 'right' }}>Masuk</th>
                  <th style={{ width: '90px', textAlign: 'right' }}>Keluar</th>
                  <th style={{ width: '100px', textAlign: 'right' }}>Saldo</th>
                  <th>Petugas / Catatan</th>
                </tr>
              </thead>
              <tbody>
                {data.movements.map((m) => {
                  const hasIn = Number(m.in_qty) > 0;
                  const hasOut = Number(m.out_qty) > 0;

                  return (
                    <tr key={m.id}>
                      <td style={{ fontFamily: 'monospace', whiteSpace: 'nowrap' }}>
                        {formatDateTime(m.transaction_date)}
                      </td>
                      <td>
                        <code style={{ fontSize: '0.8rem' }}>{m.document_no}</code>
                      </td>
                      <td>{getTransactionBadge(m.transaction_type)}</td>
                      <td>
                        <span className="table__muted">
                          {m.from_location ? `${m.from_location} → ` : ''}
                          {m.to_location ? m.to_location : '—'}
                        </span>
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: hasIn ? 700 : 400, color: hasIn ? '#15803d' : '#94a3b8' }}>
                        {hasIn ? `+${formatNumber(m.in_qty)}` : '—'}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: hasOut ? 700 : 400, color: hasOut ? '#b91c1c' : '#94a3b8' }}>
                        {hasOut ? `-${formatNumber(m.out_qty)}` : '—'}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700, color: 'var(--color-primary, #3859ac)' }}>
                        {formatNumber(m.closing_balance)}
                      </td>
                      <td>
                        <div>{m.notes || '—'}</div>
                        {m.posted_by && <div className="table__muted" style={{ fontSize: '0.75rem' }}>Oleh: {m.posted_by}</div>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </Modal>
  );
}
