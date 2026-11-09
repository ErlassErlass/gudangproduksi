import { useRef } from 'react';
import { Printer, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { formatDate } from '@/lib/format';

export interface SalesLoanRecord {
  id: number;
  loan_no: string;
  group_leader: string;
  sales_name: string;
  customer_id: number;
  customer_code?: string;
  customer_name?: string;
  product_id: number;
  product_code?: string;
  product_name?: string;
  quantity: number | string;
  loan_date: string;
  return_date?: string | null;
  status: 'DIPINJAM' | 'DIKEMBALIKAN';
  notes?: string | null;
  return_notes?: string | null;
  receipt_no?: string | null;
  returned_at?: string | null;
}

interface SalesLoanReceiptModalProps {
  loan: SalesLoanRecord;
  onClose: () => void;
}

export function SalesLoanReceiptModal({ loan, onClose }: SalesLoanReceiptModalProps) {
  const printAreaRef = useRef<HTMLDivElement>(null);

  const handlePrint = () => {
    if (!printAreaRef.current) return;
    const printWindow = window.open('', '_blank', 'width=850,height=900');
    if (!printWindow) {
      window.print();
      return;
    }

    const contentHtml = printAreaRef.current.innerHTML;

    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Bukti Tanda Terima - ${loan.receipt_no || loan.loan_no}</title>
          <style>
            @page {
              size: A4 portrait;
              margin: 15mm 20mm;
            }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
              color: #1e293b;
              margin: 0;
              padding: 10px;
              line-height: 1.5;
            }
            .header {
              border-bottom: 2px solid #263f7d;
              padding-bottom: 12px;
              margin-bottom: 20px;
              display: flex;
              justify-content: space-between;
              align-items: flex-start;
            }
            .company-name {
              font-size: 20px;
              font-weight: 800;
              color: #263f7d;
              letter-spacing: 0.5px;
            }
            .company-sub {
              font-size: 11px;
              color: #64748b;
            }
            .doc-title {
              text-align: center;
              margin: 16px 0 24px 0;
            }
            .doc-title h2 {
              margin: 0;
              font-size: 16px;
              text-transform: uppercase;
              letter-spacing: 1px;
              color: #0f172a;
            }
            .doc-title .doc-no {
              font-size: 13px;
              color: #263f7d;
              font-weight: 700;
              margin-top: 4px;
            }
            .info-grid {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 16px;
              background: #f8fafc;
              border: 1px solid #e2e8f0;
              border-radius: 6px;
              padding: 12px 16px;
              font-size: 12px;
              margin-bottom: 20px;
            }
            .info-row {
              margin-bottom: 6px;
            }
            .info-label {
              color: #64748b;
              display: inline-block;
              width: 130px;
              font-weight: 500;
            }
            .info-val {
              color: #0f172a;
              font-weight: 600;
            }
            table {
              width: 100%;
              border-collapse: collapse;
              margin: 20px 0;
              font-size: 12px;
            }
            th, td {
              border: 1px solid #cbd5e1;
              padding: 8px 10px;
              text-align: left;
            }
            th {
              background-color: #f1f5f9;
              font-weight: 700;
              color: #1e293b;
            }
            .signature-section {
              margin-top: 40px;
              display: grid;
              grid-template-columns: 1fr 1fr 1fr;
              text-align: center;
              font-size: 12px;
            }
            .sig-title {
              font-weight: 600;
              color: #475569;
              margin-bottom: 60px;
            }
            .sig-name {
              font-weight: 700;
              border-top: 1px dashed #94a3b8;
              display: inline-block;
              min-width: 160px;
              padding-top: 4px;
              color: #0f172a;
            }
            .status-badge {
              display: inline-block;
              padding: 3px 8px;
              border-radius: 4px;
              font-size: 11px;
              font-weight: 700;
              background: #dcfce7;
              color: #15803d;
            }
          </style>
        </head>
        <body>
          ${contentHtml}
          <script>
            window.onload = function() {
              window.print();
            };
          </script>
        </body>
      </html>
    `);
    printWindow.document.close();
  };

  const receiptNo = loan.receipt_no || `BTT-LOAN-${loan.id}`;

  return (
    <Modal
      open
      title="Bukti Tanda Terima Pengembalian Unit (Soft File PDF)"
      description="Format dokumen tanda terima resmi pengembalian unit pinjaman sales. Anda dapat mencetak atau mengunduh sebagai soft file PDF."
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
          <div className="text-xs text-[var(--color-text-muted)] flex items-center gap-1.5">
            <CheckCircle2 size={15} color="#16a34a" />
            Unit tuntas dikembalikan ke stok gudang
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button variant="secondary" onClick={onClose}>
              Tutup
            </Button>
            <Button variant="primary" icon={<Printer size={15} />} onClick={handlePrint}>
              Cetak / Simpan PDF
            </Button>
          </div>
        </div>
      }
    >
      <div
        ref={printAreaRef}
        style={{
          background: '#ffffff',
          color: '#1e293b',
          border: '1px solid #e2e8f0',
          borderRadius: 8,
          padding: 24,
          fontSize: 13,
        }}
      >
        {/* Kop Surat Resmi */}
        <div
          style={{
            borderBottom: '2px solid #263f7d',
            paddingBottom: 12,
            marginBottom: 20,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'flex-start',
          }}
        >
          <div>
            <div style={{ fontSize: 18, fontWeight: 800, color: '#263f7d', letterSpacing: '0.5px' }}>
              ERLASS Prokreatif Indonesia
            </div>
            <div style={{ fontSize: 11, color: '#64748b', marginTop: 2 }}>
              PT. ERLASS PROKREATIF INDONESIA &bull; Education & Educational Technology Solutions
            </div>
            <div style={{ fontSize: 11, color: '#64748b' }}>
              Jl. Kemandoran I No. 4, Kebayoran Lama, Jakarta Selatan 12210 &bull; Telp: (021) 536 78888
            </div>
          </div>
          <div style={{ textAlign: 'right' }}>
            <span
              style={{
                display: 'inline-block',
                padding: '4px 10px',
                borderRadius: 4,
                fontSize: 11,
                fontWeight: 700,
                background: '#dcfce7',
                color: '#15803d',
              }}
            >
              DIKEMBALIKAN
            </span>
          </div>
        </div>

        {/* Judul Dokumen */}
        <div style={{ textAlign: 'center', margin: '14px 0 20px 0' }}>
          <h2 style={{ margin: 0, fontSize: 15, fontWeight: 800, letterSpacing: '0.5px', color: '#0f172a' }}>
            SURAT BUKTI TANDA TERIMA PENGEMBALIAN PINJAMAN UNIT
          </h2>
          <div style={{ fontSize: 12, color: '#263f7d', fontWeight: 700, marginTop: 4 }}>
            No. Dokumen: <code>{receiptNo}</code>
          </div>
        </div>

        {/* Informasi Peminjaman & Pengembalian */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: '1fr 1fr',
            gap: 12,
            background: '#f8fafc',
            border: '1px solid #e2e8f0',
            borderRadius: 6,
            padding: 12,
            fontSize: 12,
            marginBottom: 18,
          }}
        >
          <div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 120 }}>No. Peminjaman:</span>
              <strong style={{ color: '#0f172a' }}>{loan.loan_no}</strong>
            </div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 120 }}>GL Marketing:</span>
              <strong style={{ color: '#263f7d' }}>{loan.group_leader}</strong>
            </div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 120 }}>Nama Sales:</span>
              <strong style={{ color: '#0f172a' }}>{loan.sales_name}</strong>
            </div>
          </div>
          <div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 130 }}>Sekolah / Klien:</span>
              <strong style={{ color: '#0f172a' }}>
                {loan.customer_name || 'Sekolah Mitra'} {loan.customer_code ? `(${loan.customer_code})` : ''}
              </strong>
            </div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 130 }}>Tgl. Pengambilan:</span>
              <span>{formatDate(loan.loan_date)}</span>
            </div>
            <div style={{ marginBottom: 4 }}>
              <span style={{ color: '#64748b', display: 'inline-block', width: 130 }}>Tgl. Pengembalian:</span>
              <strong style={{ color: '#15803d' }}>
                {loan.return_date ? formatDate(loan.return_date) : '-'}
              </strong>
            </div>
          </div>
        </div>

        {/* Tabel Barang yang Dikembalikan */}
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12, marginBottom: 16 }}>
          <thead>
            <tr style={{ background: '#f1f5f9' }}>
              <th style={{ border: '1px solid #cbd5e1', padding: '8px 10px', width: 40, textAlign: 'center' }}>No</th>
              <th style={{ border: '1px solid #cbd5e1', padding: '8px 10px', width: 120 }}>Kode Produk</th>
              <th style={{ border: '1px solid #cbd5e1', padding: '8px 10px' }}>Nama Produk / Kit Demo</th>
              <th style={{ border: '1px solid #cbd5e1', padding: '8px 10px', width: 80, textAlign: 'center' }}>Qty</th>
              <th style={{ border: '1px solid #cbd5e1', padding: '8px 10px' }}>Kondisi & Catatan Pengembalian</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style={{ border: '1px solid #cbd5e1', padding: '8px 10px', textAlign: 'center' }}>1</td>
              <td style={{ border: '1px solid #cbd5e1', padding: '8px 10px' }}>
                <code>{loan.product_code || 'FG-MLK'}</code>
              </td>
              <td style={{ border: '1px solid #cbd5e1', padding: '8px 10px', fontWeight: 600 }}>
                {loan.product_name || 'Mobile Learning Kit (MLK) Standar'}
              </td>
              <td style={{ border: '1px solid #cbd5e1', padding: '8px 10px', textAlign: 'center', fontWeight: 700 }}>
                {loan.quantity} Unit
              </td>
              <td style={{ border: '1px solid #cbd5e1', padding: '8px 10px' }}>
                {loan.return_notes || 'Unit kembali lengkap dan berfungsi dengan baik.'}
              </td>
            </tr>
          </tbody>
        </table>

        {loan.notes && (
          <div style={{ fontSize: 11, color: '#64748b', marginBottom: 20 }}>
            <em>Catatan Peminjaman Awal: {loan.notes}</em>
          </div>
        )}

        {/* Tanda Tangan */}
        <div
          style={{
            marginTop: 30,
            display: 'grid',
            gridTemplateColumns: '1fr 1fr 1fr',
            textAlign: 'center',
            fontSize: 11,
          }}
        >
          <div>
            <div style={{ color: '#64748b', marginBottom: 50 }}>Pihak Yang Mengembalikan (Sales)</div>
            <div
              style={{
                fontWeight: 700,
                borderTop: '1px dashed #94a3b8',
                display: 'inline-block',
                minWidth: 150,
                paddingTop: 4,
                color: '#0f172a',
              }}
            >
              ( {loan.sales_name} )
            </div>
          </div>

          <div>
            <div style={{ color: '#64748b', marginBottom: 50 }}>Petugas Penerima (Gudang)</div>
            <div
              style={{
                fontWeight: 700,
                borderTop: '1px dashed #94a3b8',
                display: 'inline-block',
                minWidth: 150,
                paddingTop: 4,
                color: '#0f172a',
              }}
            >
              ( PIC Logistik ERLASS )
            </div>
          </div>

          <div>
            <div style={{ color: '#64748b', marginBottom: 50 }}>Mengetahui (GL Marketing)</div>
            <div
              style={{
                fontWeight: 700,
                borderTop: '1px dashed #94a3b8',
                display: 'inline-block',
                minWidth: 150,
                paddingTop: 4,
                color: '#0f172a',
              }}
            >
              ( {loan.group_leader} )
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
}
