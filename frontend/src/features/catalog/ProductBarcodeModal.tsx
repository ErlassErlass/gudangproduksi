import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertCircle,
  Clock,
  History,
  Printer,
  RotateCw,
} from 'lucide-react';
import { http } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { formatDateTime, formatNumber } from '@/lib/format';
import type { ProductItem } from '@/features/master-data/api';

interface ProductBarcodeModalProps {
  product: ProductItem;
  isOpen: boolean;
  onClose: () => void;
}

interface BarcodePrintItem {
  id: number;
  product_id: number;
  batch_no: string;
  barcode: string;
  quantity: number;
  printed_at: string;
  reprint_count: number;
  last_reprinted_at: string | null;
  notes?: string | null;
  user?: { id: number; name: string } | null;
}

interface BarcodePrintsResponse {
  data: BarcodePrintItem[];
  summary: {
    total_batches: number;
    total_quantity: number;
    last_printed_at: string | null;
    last_reprinted_at: string | null;
  };
}

export function ProductBarcodeModal({ product, isOpen, onClose }: ProductBarcodeModalProps) {
  const { notify } = useToast();
  const qc = useQueryClient();

  const [activeTab, setActiveTab] = useState<'print' | 'history'>('print');
  const [printQty, setPrintQty] = useState('10');
  const [notes, setNotes] = useState('');

  const existingBarcode =
    product.barcodes?.find((b) => b.is_primary)?.barcode ?? product.barcodes?.[0]?.barcode ?? `899${String(product.id).padStart(9, '0')}`;

  // Query Riwayat Pencetakan Barcode
  const {
    data: historyData,
    isLoading: isHistoryLoading,
  } = useQuery({
    queryKey: ['products', product.id, 'barcode-prints'],
    enabled: isOpen,
    queryFn: async () => {
      const res = await http.get<BarcodePrintsResponse>(`/products/${product.id}/barcode-prints`);
      return res.data;
    },
  });

  const availableStock = Number(product.available_qty ?? 0);

  // Helper untuk mencetak stiker secara terisolasi via iframe (bebas dari halaman aktif)
  const printStickersIsolated = (barcode: string, quantity: number, batchNo: string) => {
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

    // Generate pseudo-bars SVG
    const lines: string[] = [];
    lines.push('<rect x="0" y="0" width="3" height="48" fill="#000" />');
    lines.push('<rect x="5" y="0" width="2" height="48" fill="#000" />');
    let xOffset = 9;
    for (let i = 0; i < barcode.length; i++) {
      const c = barcode.charCodeAt(i);
      const w1 = (c % 3) + 1;
      const w2 = ((c >> 1) % 2) + 1;
      lines.push(`<rect x="${xOffset}" y="0" width="${w1 * 2}" height="48" fill="#000" />`);
      xOffset += w1 * 2 + w2 + 1;
    }
    lines.push(`<rect x="${xOffset}" y="0" width="3" height="48" fill="#000" />`);
    xOffset += 5;

    const stickersHtml: string[] = [];
    for (let i = 1; i <= quantity; i++) {
      stickersHtml.push(`
        <div class="sticker">
          <div class="company">ERLASS Prokreatif Indonesia</div>
          <div class="prod-name">${product.name}</div>
          <div class="barcode-wrap">
            <svg viewBox="0 0 ${xOffset + 4} 48" class="barcode-svg">
              ${lines.join('')}
            </svg>
            <div class="barcode-num">${barcode}</div>
          </div>
          <div class="meta">
            <span>SKU: <strong>${product.code}</strong></span>
            <span>Batch: <strong>${batchNo}</strong></span>
            <span>Stiker #${i}/${quantity}</span>
          </div>
        </div>
      `);
    }

    doc.open();
    doc.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Cetak Barcode - ${product.name}</title>
          <style>
            @page {
              size: A4;
              margin: 12mm 10mm;
            }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
              margin: 0;
              padding: 0;
              background: #fff;
              color: #000;
            }
            .grid {
              display: grid;
              grid-template-columns: repeat(2, 1fr);
              gap: 12px;
            }
            .sticker {
              border: 1px dashed #94a3b8;
              border-radius: 8px;
              padding: 12px 14px;
              page-break-inside: avoid;
              display: flex;
              flex-direction: column;
              align-items: center;
              text-align: center;
            }
            .company {
              font-size: 8pt;
              font-weight: 700;
              text-transform: uppercase;
              letter-spacing: 0.08em;
              color: #475569;
            }
            .prod-name {
              font-size: 10pt;
              font-weight: 800;
              color: #1e293b;
              margin: 3px 0 6px 0;
            }
            .barcode-wrap {
              width: 100%;
              display: flex;
              flex-direction: column;
              align-items: center;
            }
            .barcode-svg {
              width: 80%;
              max-width: 220px;
              height: 42px;
            }
            .barcode-num {
              font-family: monospace;
              font-size: 10pt;
              font-weight: 700;
              letter-spacing: 0.12em;
              margin-top: 2px;
            }
            .meta {
              width: 100%;
              display: flex;
              justify-content: space-between;
              font-size: 7pt;
              color: #64748b;
              margin-top: 6px;
              padding-top: 4px;
              border-top: 1px dotted #cbd5e1;
            }
          </style>
        </head>
        <body>
          <div class="grid">
            ${stickersHtml.join('')}
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

  // Mutasi Cetak Baru (Potong Stok)
  const printMutation = useMutation({
    mutationFn: async () => {
      const res = await http.post<{
        data: { id: number; batch_no: string; barcode: string; quantity: number };
        message: string;
      }>(`/products/${product.id}/print-barcodes`, {
        quantity: Number(printQty),
        notes: notes.trim() || undefined,
      });
      return res.data;
    },
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['master-data', 'products'] });
      qc.invalidateQueries({ queryKey: ['products', product.id, 'barcode-prints'] });
      notify(res.message, 'success');
      // Langsung cetak stiker terisolasi
      printStickersIsolated(res.data.barcode, res.data.quantity, res.data.batch_no);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? 'Gagal mencetak barcode.', 'error');
    },
  });

  // Mutasi Cetak Ulang (Reprint - Tanpa Potong Stok)
  const reprintMutation = useMutation({
    mutationFn: async (item: BarcodePrintItem) => {
      const res = await http.post<{
        data: { id: number; batch_no: string; barcode: string; quantity: number };
        message: string;
      }>(`/barcode-prints/${item.id}/reprint`);
      return { apiData: res.data, item };
    },
    onSuccess: ({ apiData, item }) => {
      qc.invalidateQueries({ queryKey: ['products', product.id, 'barcode-prints'] });
      notify(apiData.message, 'success');
      // Langsung cetak stiker terisolasi
      printStickersIsolated(item.barcode, item.quantity, item.batch_no);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? 'Gagal mencetak ulang barcode.', 'error');
    },
  });

  const handlePrintSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const qty = Number(printQty);
    if (!qty || qty < 1) {
      notify('Jumlah stiker barcode harus minimal 1.', 'error');
      return;
    }
    if (qty > availableStock) {
      notify(
        `Jumlah yang diminta (${qty}) melebihi stok fisik Finished Good yang tersedia (${availableStock}).`,
        'error'
      );
      return;
    }
    printMutation.mutate();
  };

  const summary = historyData?.summary;
  const historyList = historyData?.data ?? [];

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Barcode Finished Good: ${product.name}`}
      description="Pencetakan stiker barcode keluar untuk produk Finished Good. Setiap pencetakan baru akan memotong saldo stok fisik."
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose}>
            Tutup
          </Button>
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* Navigation Tabs */}
        <div style={{ display: 'flex', borderBottom: '1px solid var(--color-border, #e1e8f5)', gap: 8 }}>
          <button
            type="button"
            className="btn"
            style={{
              borderRadius: '8px 8px 0 0',
              borderBottom: activeTab === 'print' ? '2px solid var(--color-primary, #3859ac)' : 'none',
              color: activeTab === 'print' ? 'var(--color-primary, #3859ac)' : '#64748b',
              fontWeight: activeTab === 'print' ? 700 : 500,
              background: activeTab === 'print' ? 'var(--color-surface-muted, #f8faff)' : 'transparent',
              padding: '8px 16px',
            }}
            onClick={() => setActiveTab('print')}
          >
            <Printer size={15} style={{ marginRight: 6 }} />
            Cetak Barcode Keluar (Potong Stok)
          </button>

          <button
            type="button"
            className="btn"
            style={{
              borderRadius: '8px 8px 0 0',
              borderBottom: activeTab === 'history' ? '2px solid var(--color-primary, #3859ac)' : 'none',
              color: activeTab === 'history' ? 'var(--color-primary, #3859ac)' : '#64748b',
              fontWeight: activeTab === 'history' ? 700 : 500,
              background: activeTab === 'history' ? 'var(--color-surface-muted, #f8faff)' : 'transparent',
              padding: '8px 16px',
            }}
            onClick={() => setActiveTab('history')}
          >
            <History size={15} style={{ marginRight: 6 }} />
            Riwayat Pencetakan Barcode ({historyList.length})
          </button>
        </div>

        {/* Tab 1: Form Cetak Barcode Baru & Pengeluaran Stok */}
        {activeTab === 'print' && (
          <form onSubmit={handlePrintSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {/* Kartu Ringkasan Stok & Produk */}
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                gap: 12,
                padding: 14,
                borderRadius: 10,
                background: 'var(--color-surface-muted, #f8faff)',
                border: '1px solid var(--color-border, #e2e8f0)',
              }}
            >
              <div>
                <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Perusahaan / Penerbit</div>
                <div style={{ fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)', fontSize: '0.95rem' }}>
                  ERLASS Prokreatif Indonesia
                </div>
              </div>

              <div>
                <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Stok Fisik Finished Good Tersedia</div>
                <div style={{ fontWeight: 800, color: availableStock > 0 ? '#15803d' : '#b91c1c', fontSize: '1.1rem', fontFamily: 'monospace' }}>
                  {formatNumber(availableStock)} Unit
                </div>
              </div>

              <div>
                <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Kode Barcode Aktif</div>
                <div style={{ fontWeight: 700, color: 'var(--color-primary, #3859ac)', fontFamily: 'monospace' }}>
                  {existingBarcode}
                </div>
              </div>
            </div>

            {/* Input Form */}
            <div className="grid grid--2">
              <FormField
                label={`Jumlah Stiker Barcode (Maks: ${availableStock} unit fisik)`}
                htmlFor="print-qty"
                required
              >
                <input
                  id="print-qty"
                  type="number"
                  min="1"
                  max={Math.max(1, availableStock)}
                  className="input font-mono font-bold"
                  value={printQty}
                  onChange={(e) => setPrintQty(e.target.value)}
                  disabled={availableStock <= 0}
                  required
                />
              </FormField>

              <FormField label="Catatan Pengeluaran Barcode (Opsional)" htmlFor="print-notes">
                <input
                  id="print-notes"
                  type="text"
                  className="input"
                  placeholder="Mis. Batch Distribusi Sekolah / Event"
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                />
              </FormField>
            </div>

            {/* Peringatan Stok */}
            {availableStock <= 0 ? (
              <div className="alert alert--danger" style={{ display: 'flex', alignItems: 'center', gap: 8, margin: 0 }}>
                <AlertCircle size={18} />
                <span>
                  Stok fisik Finished Good <strong>0 unit</strong>. Anda tidak dapat mencetak stiker keluar sebelum stok Finished Good tersedia dari perakitan BOM.
                </span>
              </div>
            ) : (
              <div
                style={{
                  padding: 10,
                  borderRadius: 6,
                  background: '#fef3c7',
                  border: '1px solid #fde68a',
                  color: '#92400e',
                  fontSize: '0.8rem',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 8,
                }}
              >
                <Clock size={16} />
                <span>
                  Mencetak <strong>{printQty || 0} stiker barcode</strong> akan langsung mengurangi <strong>{printQty || 0} unit</strong> stok fisik Finished Good secara otomatis di buku besar persediaan.
                </span>
              </div>
            )}

            {/* Tombol Eksekusi Cetak & Pengeluaran Stok */}
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
              <Button
                type="submit"
                variant="primary"
                icon={<Printer size={16} />}
                disabled={availableStock <= 0 || !printQty || Number(printQty) > availableStock}
                loading={printMutation.isPending}
              >
                Cetak {printQty || 0} Stiker & Kurangi Stok
              </Button>
            </div>
          </form>
        )}

        {/* Tab 2: Riwayat Pencetakan Barcode & Cetak Ulang */}
        {activeTab === 'history' && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            {/* Summary Header */}
            {summary && (
              <div
                style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
                  gap: 10,
                  padding: 12,
                  borderRadius: 8,
                  background: 'var(--color-surface-muted, #f8faff)',
                  border: '1px solid var(--color-border, #e2e8f0)',
                  fontSize: '0.825rem',
                }}
              >
                <div>
                  <span className="table__muted">Total Batch Dicetak:</span>
                  <div style={{ fontWeight: 800, fontSize: '1rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                    {summary.total_batches} kali
                  </div>
                </div>
                <div>
                  <span className="table__muted">Total Stiker Dikeluarkan:</span>
                  <div style={{ fontWeight: 800, fontSize: '1rem', color: '#15803d' }}>
                    {formatNumber(summary.total_quantity)} stiker
                  </div>
                </div>
                <div>
                  <span className="table__muted">Terakhir Kali Dicetak:</span>
                  <div style={{ fontWeight: 600 }}>
                    {summary.last_printed_at ? formatDateTime(summary.last_printed_at) : '—'}
                  </div>
                </div>
                <div>
                  <span className="table__muted">Terakhir Kali Cetak Ulang:</span>
                  <div style={{ fontWeight: 600 }}>
                    {summary.last_reprinted_at ? formatDateTime(summary.last_reprinted_at) : 'Belum pernah'}
                  </div>
                </div>
              </div>
            )}

            {/* Tabel Riwayat */}
            {isHistoryLoading ? (
              <LoadingState />
            ) : historyList.length === 0 ? (
              <EmptyState
                title="Belum ada riwayat pencetakan barcode"
                description="Lakukan pencetakan stiker pertama kali pada tab 'Cetak Barcode Keluar'."
              />
            ) : (
              <div className="table-wrap">
                <table className="table" style={{ fontSize: '0.825rem' }}>
                  <thead>
                    <tr>
                      <th style={{ width: '130px' }}>No. Batch</th>
                      <th style={{ width: '140px' }}>Waktu Cetak Pertama</th>
                      <th style={{ width: '70px', textAlign: 'right' }}>Stiker</th>
                      <th style={{ width: '100px', textAlign: 'center' }}>Cetak Ulang</th>
                      <th>Terakhir Cetak Ulang</th>
                      <th style={{ width: '110px', textAlign: 'center' }}>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    {historyList.map((item) => (
                      <tr key={item.id}>
                        <td>
                          <code>{item.batch_no}</code>
                        </td>
                        <td style={{ fontFamily: 'monospace' }}>
                          {formatDateTime(item.printed_at)}
                        </td>
                        <td style={{ textAlign: 'right', fontWeight: 700, fontFamily: 'monospace' }}>
                          {item.quantity}
                        </td>
                        <td style={{ textAlign: 'center' }}>
                          <span
                            className="chip"
                            style={{
                              background: item.reprint_count > 0 ? '#e0f2fe' : '#f1f5f9',
                              color: item.reprint_count > 0 ? '#0369a1' : '#64748b',
                              fontWeight: 600,
                            }}
                          >
                            {item.reprint_count}x
                          </span>
                        </td>
                        <td>
                          {item.last_reprinted_at ? (
                            <span style={{ fontFamily: 'monospace' }}>
                              {formatDateTime(item.last_reprinted_at)}
                            </span>
                          ) : (
                            <span className="table__muted">—</span>
                          )}
                        </td>
                        <td style={{ textAlign: 'center' }}>
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<RotateCw size={13} />}
                            onClick={() => reprintMutation.mutate(item)}
                            loading={reprintMutation.isPending}
                            title="Cetak ulang batch ini (TIDAK mengurangi stok)"
                          >
                            Cetak Ulang
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}
      </div>
    </Modal>
  );
}
