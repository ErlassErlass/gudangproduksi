import { useRef } from 'react';
import { Printer, Truck } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { formatDate } from '@/lib/format';
import type { SchoolRentalRecord } from './SchoolRentalBarcodeModal';

export interface DeliveryOrderData {
  shipment: {
    id: number;
    shipment_no: string;
    shipment_type: string;
    status: string;
    dispatched_at: string;
    tracking_reference?: string;
    recipient_name?: string;
    notes?: string;
  };
  rental: SchoolRentalRecord & {
    customer_address?: string;
    customer_phone?: string;
  };
  items?: Array<{
    id: number;
    product_id: number;
    product_code?: string;
    product_name?: string;
    quantity: number | string;
  }>;
}

interface DeliveryOrderModalProps {
  data: DeliveryOrderData;
  onClose: () => void;
}

export function DeliveryOrderModal({ data, onClose }: DeliveryOrderModalProps) {
  const printAreaRef = useRef<HTMLDivElement>(null);
  const { shipment, rental, items = [] } = data;

  const startCode = rental.barcode_prefix && rental.start_unit_no
    ? `${rental.barcode_prefix}_${String(rental.start_unit_no).padStart(2, '0')}`
    : null;
  const endCode = rental.barcode_prefix && rental.end_unit_no
    ? `${rental.barcode_prefix}_${String(rental.end_unit_no).padStart(2, '0')}`
    : null;
  const rangeStr = startCode && endCode ? `${startCode} s/d ${endCode}` : `${rental.total_units || 1} Unit`;

  const handlePrint = () => {
    if (!printAreaRef.current) return;
    const printWindow = window.open('', '_blank', 'width=950,height=950');
    if (!printWindow) {
      window.print();
      return;
    }

    const contentHtml = printAreaRef.current.innerHTML;

    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Surat Jalan Delivery Order - ${shipment.shipment_no}</title>
          <style>
            @page {
              size: A4 portrait;
              margin: 12mm 15mm;
            }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
              color: #0f172a;
              margin: 0;
              padding: 0;
              font-size: 13px;
              line-height: 1.4;
            }
            .kop {
              border-bottom: 2px solid #1e3a8a;
              padding-bottom: 12px;
              margin-bottom: 18px;
            }
            .company-name {
              font-size: 20px;
              font-weight: 800;
              color: #1e3a8a;
              letter-spacing: -0.3px;
              margin: 0 0 2px 0;
            }
            .company-sub {
              font-size: 11px;
              color: #475569;
              margin: 0;
            }
            .doc-title {
              text-align: center;
              margin: 16px 0 20px 0;
            }
            .doc-title h1 {
              font-size: 17px;
              font-weight: 800;
              text-transform: uppercase;
              letter-spacing: 0.5px;
              margin: 0 0 4px 0;
              color: #0f172a;
            }
            .doc-title p {
              margin: 0;
              font-size: 12px;
              font-weight: 700;
              color: #1e3a8a;
              font-family: monospace;
            }
            .info-grid {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 24px;
              margin-bottom: 20px;
            }
            .info-box {
              border: 1px solid #cbd5e1;
              border-radius: 6px;
              padding: 12px;
              background-color: #f8fafc;
            }
            .info-box-title {
              font-size: 11px;
              font-weight: 700;
              text-transform: uppercase;
              color: #475569;
              margin-bottom: 8px;
              border-bottom: 1px solid #e2e8f0;
              padding-bottom: 4px;
            }
            .info-row {
              display: flex;
              margin-bottom: 4px;
              font-size: 12px;
            }
            .info-row:last-child {
              margin-bottom: 0;
            }
            .info-label {
              width: 130px;
              color: #64748b;
              font-weight: 500;
            }
            .info-value {
              flex: 1;
              color: #0f172a;
              font-weight: 600;
            }
            table.items-table {
              width: 100%;
              border-collapse: collapse;
              margin: 20px 0;
              font-size: 12px;
            }
            table.items-table th, table.items-table td {
              border: 1px solid #cbd5e1;
              padding: 8px 10px;
              text-align: left;
            }
            table.items-table th {
              background-color: #f1f5f9;
              font-weight: 700;
              color: #1e3a8a;
            }
            table.items-table td.center {
              text-align: center;
            }
            .notes-box {
              border: 1px dashed #94a3b8;
              border-radius: 6px;
              padding: 10px 14px;
              font-size: 11px;
              color: #475569;
              margin-bottom: 30px;
              background-color: #fafaf9;
            }
            .sig-grid {
              display: grid;
              grid-template-columns: 1fr 1fr 1fr;
              gap: 16px;
              text-align: center;
              margin-top: 20px;
              page-break-inside: avoid;
            }
            .sig-box {
              border: 1px solid #cbd5e1;
              border-radius: 6px;
              padding: 10px;
              background-color: #ffffff;
            }
            .sig-role {
              font-size: 11px;
              font-weight: 700;
              color: #334155;
              text-transform: uppercase;
              margin-bottom: 55px;
            }
            .sig-name {
              border-top: 1px dashed #94a3b8;
              padding-top: 4px;
              font-size: 11px;
              font-weight: 600;
              color: #0f172a;
            }
          </style>
        </head>
        <body>
          ${contentHtml}
        </body>
      </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 350);
  };

  return (
    <Modal
      open
      title="Surat Jalan Pengiriman (Delivery Order)"
      onClose={onClose}
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%', alignItems: 'center' }}>
          <div style={{ fontSize: '0.85rem', color: 'var(--color-text-muted)' }}>
            Status DO: <strong className="text-emerald-700">Tersinkronisasi ke Distribusi</strong>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button onClick={onClose}>Tutup</Button>
            <Button variant="primary" icon={<Printer size={16} />} onClick={handlePrint}>
              Cetak / Simpan Surat DO
            </Button>
          </div>
        </div>
      }
    >
      <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3 text-xs text-blue-900">
        <Truck className="text-blue-600 shrink-0" size={18} />
        <div>
          <strong>Otomatis Terdaftar di Modul Distribusi:</strong> Dokumen DO ini telah diverifikasi dan nomor pengiriman <code>{shipment.shipment_no}</code> langsung tercatat pada menu Distribusi.
        </div>
      </div>

      {/* Printable Area */}
      <div
        ref={printAreaRef}
        style={{
          backgroundColor: '#ffffff',
          color: '#0f172a',
          padding: '24px',
          border: '1px solid #cbd5e1',
          borderRadius: '8px',
          fontSize: '13px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
        }}
      >
        {/* Kop Surat */}
        <div style={{ borderBottom: '2px solid #1e3a8a', paddingBottom: '12px', marginBottom: '16px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
            <div>
              <h2 style={{ fontSize: '18px', fontWeight: 800, color: '#1e3a8a', margin: '0 0 3px 0' }}>
                ERLASS Prokreatif Indonesia
              </h2>
              <div style={{ fontSize: '11px', color: '#475569', lineHeight: 1.4 }}>
                Pejaten Office Park Blok D, Jl. Warung Buncit Raya No. 79, Jakarta Selatan 12740<br />
                Telepon: (021) 7918-0909 | Email: logistik@erlass.co.id | Web: erlass.co.id
              </div>
            </div>
            <div style={{ textAlign: 'right' }}>
              <span
                style={{
                  display: 'inline-block',
                  backgroundColor: '#dbeafe',
                  color: '#1e40af',
                  fontSize: '11px',
                  fontWeight: 700,
                  padding: '3px 8px',
                  borderRadius: '4px',
                }}
              >
                DELIVERY ORDER
              </span>
            </div>
          </div>
        </div>

        {/* Title */}
        <div style={{ textAlign: 'center', margin: '14px 0 18px 0' }}>
          <h3 style={{ fontSize: '16px', fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.5px', margin: '0 0 3px 0', color: '#0f172a' }}>
            SURAT JALAN PENGIRIMAN (DELIVERY ORDER)
          </h3>
          <div style={{ fontFamily: 'monospace', fontWeight: 700, color: '#1e3a8a', fontSize: '12px' }}>
            NO: {shipment.shipment_no}
          </div>
        </div>

        {/* Info Grid */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '18px' }}>
          {/* Box Kiri: Pengiriman */}
          <div style={{ border: '1px solid #cbd5e1', borderRadius: '6px', padding: '10px 12px', backgroundColor: '#f8fafc' }}>
            <div style={{ fontSize: '11px', fontWeight: 700, textTransform: 'uppercase', color: '#475569', borderBottom: '1px solid #e2e8f0', paddingBottom: '4px', marginBottom: '8px' }}>
              Informasi Dokumen Pengiriman
            </div>
            <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
              <span style={{ width: '130px', color: '#64748b' }}>Nomor DO:</span>
              <span style={{ fontWeight: 700, fontFamily: 'monospace' }}>{shipment.shipment_no}</span>
            </div>
            <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
              <span style={{ width: '130px', color: '#64748b' }}>Tanggal Dispatch:</span>
              <span style={{ fontWeight: 600 }}>{formatDate(shipment.dispatched_at)}</span>
            </div>
            <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
              <span style={{ width: '130px', color: '#64748b' }}>No. Order Sewa:</span>
              <span style={{ fontWeight: 700, fontFamily: 'monospace', color: '#1e3a8a' }}>{rental.rental_no}</span>
            </div>
            <div style={{ display: 'flex', fontSize: '12px' }}>
              <span style={{ width: '130px', color: '#64748b' }}>Jenis Pengiriman:</span>
              <span style={{ fontWeight: 600 }}>Sewa Finished Good MLK (Sekolah)</span>
            </div>
          </div>

          {/* Box Kanan: Tujuan Sekolah */}
          <div style={{ border: '1px solid #cbd5e1', borderRadius: '6px', padding: '10px 12px', backgroundColor: '#f8fafc' }}>
            <div style={{ fontSize: '11px', fontWeight: 700, textTransform: 'uppercase', color: '#475569', borderBottom: '1px solid #e2e8f0', paddingBottom: '4px', marginBottom: '8px' }}>
              Pihak Penerima (Sekolah Mitra)
            </div>
            <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
              <span style={{ width: '110px', color: '#64748b' }}>Nama Sekolah:</span>
              <span style={{ fontWeight: 700, color: '#1e3a8a' }}>
                {rental.school_name || rental.customer_name || 'Sekolah Mitra'}
              </span>
            </div>
            {rental.customer_code && (
              <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
                <span style={{ width: '110px', color: '#64748b' }}>Kode Sekolah:</span>
                <span style={{ fontFamily: 'monospace' }}>{rental.customer_code}</span>
              </div>
            )}
            <div style={{ display: 'flex', fontSize: '12px', marginBottom: '4px' }}>
              <span style={{ width: '110px', color: '#64748b' }}>Alamat Tujuan:</span>
              <span style={{ fontSize: '11px', color: '#334155' }}>
                {rental.customer_address || 'Lokasi Sekolah Penerima (Sesuai Kontrak Sewa)'}
              </span>
            </div>
            {rental.customer_phone && (
              <div style={{ display: 'flex', fontSize: '12px' }}>
                <span style={{ width: '110px', color: '#64748b' }}>Kontak / Telp:</span>
                <span>{rental.customer_phone}</span>
              </div>
            )}
          </div>
        </div>

        {/* Tabel Rincian Barang */}
        <div style={{ marginBottom: '18px' }}>
          <table
            style={{
              width: '100%',
              borderCollapse: 'collapse',
              fontSize: '12px',
              border: '1px solid #cbd5e1',
            }}
          >
            <thead>
              <tr style={{ backgroundColor: '#f1f5f9', color: '#1e3a8a' }}>
                <th style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center', width: '36px' }}>No</th>
                <th style={{ border: '1px solid #cbd5e1', padding: '8px', width: '100px' }}>Kode Produk</th>
                <th style={{ border: '1px solid #cbd5e1', padding: '8px' }}>Deskripsi Barang / Produk</th>
                <th style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center', width: '80px' }}>Jumlah</th>
                <th style={{ border: '1px solid #cbd5e1', padding: '8px', width: '220px' }}>Rincian Serial Barcode Unit</th>
              </tr>
            </thead>
            <tbody>
              {items.length > 0 ? (
                items.map((item, idx) => (
                  <tr key={item.id || idx}>
                    <td style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center' }}>{idx + 1}</td>
                    <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontFamily: 'monospace', fontWeight: 600 }}>
                      {item.product_code || rental.product_code || '1001'}
                    </td>
                    <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontWeight: 600 }}>
                      {item.product_name || rental.product_name || 'Mobile Learning Kit (MLK) Standar'}
                      <div style={{ fontSize: '10px', color: '#64748b', marginTop: '2px' }}>
                        Finished Good Fisik — Kelengkapan Box, Modul & Komponen
                      </div>
                    </td>
                    <td style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center', fontWeight: 700 }}>
                      {item.quantity} Unit
                    </td>
                    <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontFamily: 'monospace', fontSize: '11px', color: '#047857', fontWeight: 700 }}>
                      {rangeStr}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center' }}>1</td>
                  <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontFamily: 'monospace', fontWeight: 600 }}>
                    {rental.product_code || '1001'}
                  </td>
                  <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontWeight: 600 }}>
                    {rental.product_name || 'Mobile Learning Kit (MLK) Standar'}
                    <div style={{ fontSize: '10px', color: '#64748b', marginTop: '2px' }}>
                      Finished Good Fisik — Kelengkapan Box, Modul & Komponen
                    </div>
                  </td>
                  <td style={{ border: '1px solid #cbd5e1', padding: '8px', textAlign: 'center', fontWeight: 700 }}>
                    {rental.total_units || 1} Unit
                  </td>
                  <td style={{ border: '1px solid #cbd5e1', padding: '8px', fontFamily: 'monospace', fontSize: '11px', color: '#047857', fontWeight: 700 }}>
                    {rangeStr}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {/* Catatan Ketentuan */}
        <div style={{ border: '1px dashed #94a3b8', borderRadius: '6px', padding: '8px 12px', fontSize: '11px', color: '#475569', marginBottom: '22px', backgroundColor: '#fafaf9' }}>
          <strong>Syarat & Ketentuan Penerimaan:</strong>
          <ol style={{ margin: '4px 0 0 0', paddingLeft: '16px' }}>
            <li>Barang yang dikirim telah melalui proses Quality Control (QC) dan diserahkan dalam kondisi lengkap dan tersegel barcode unit.</li>
            <li>Harap periksa kecocokan nomor serial unit barcode dengan fisik paket saat serah terima.</li>
            <li>Segala bentuk kehilangan komponen atau modul pada masa sewa akan dicatat pada berita acara QC saat pengembalian barang.</li>
          </ol>
        </div>

        {/* Kolom Tanda Tangan */}
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '14px', textAlign: 'center' }}>
          <div style={{ border: '1px solid #cbd5e1', borderRadius: '6px', padding: '8px', backgroundColor: '#ffffff' }}>
            <div style={{ fontSize: '10px', fontWeight: 700, color: '#334155', textTransform: 'uppercase', marginBottom: '48px' }}>
              Bagian Ekspedisi / Kurir
            </div>
            <div style={{ borderTop: '1px dashed #94a3b8', paddingTop: '4px', fontSize: '11px', fontWeight: 600, color: '#0f172a' }}>
              ( ........................................ )
            </div>
            <div style={{ fontSize: '10px', color: '#64748b' }}>Tgl: __/__/2026</div>
          </div>

          <div style={{ border: '1px solid #cbd5e1', borderRadius: '6px', padding: '8px', backgroundColor: '#ffffff' }}>
            <div style={{ fontSize: '10px', fontWeight: 700, color: '#334155', textTransform: 'uppercase', marginBottom: '48px' }}>
              Logistik / Gudang ERLASS
            </div>
            <div style={{ borderTop: '1px dashed #94a3b8', paddingTop: '4px', fontSize: '11px', fontWeight: 600, color: '#0f172a' }}>
              ( Petugas Gudang ERLASS )
            </div>
            <div style={{ fontSize: '10px', color: '#64748b' }}>Tgl: {formatDate(shipment.dispatched_at)}</div>
          </div>

          <div style={{ border: '1px solid #cbd5e1', borderRadius: '6px', padding: '8px', backgroundColor: '#ffffff' }}>
            <div style={{ fontSize: '10px', fontWeight: 700, color: '#334155', textTransform: 'uppercase', marginBottom: '48px' }}>
              Penerima (Pihak Sekolah)
            </div>
            <div style={{ borderTop: '1px dashed #94a3b8', paddingTop: '4px', fontSize: '11px', fontWeight: 600, color: '#0f172a' }}>
              ( {rental.school_name || rental.customer_name || '........................................'} )
            </div>
            <div style={{ fontSize: '10px', color: '#64748b' }}>Tanda Tangan & Cap Sekolah</div>
          </div>
        </div>
      </div>
    </Modal>
  );
}
