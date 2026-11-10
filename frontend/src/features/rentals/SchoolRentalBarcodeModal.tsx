import { useRef } from 'react';
import { Printer, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';

export interface SchoolRentalRecord {
  id: number;
  rental_no: string;
  customer_name?: string;
  customer_code?: string;
  school_name?: string;
  product_id?: number;
  product_name?: string;
  product_code?: string;
  start_date: string;
  status: string;
  barcode_prefix?: string;
  start_unit_no?: number;
  end_unit_no?: number;
  total_units?: number;
  unit_codes?: string[];
  notes?: string | null;
  production_plan_title?: string;
  production_plan_no?: string;
  available_fg_stock?: number;
  is_stock_shortage?: boolean;
  shortage_qty?: number;
  stock_notice?: string;
}

interface SchoolRentalBarcodeModalProps {
  rental: SchoolRentalRecord;
  onClose: () => void;
}

export function SchoolRentalBarcodeModal({ rental, onClose }: SchoolRentalBarcodeModalProps) {
  const printAreaRef = useRef<HTMLDivElement>(null);

  const unitCodes = rental.unit_codes && rental.unit_codes.length > 0
    ? rental.unit_codes
    : Array.from({ length: rental.total_units || 1 }, (_, i) => {
        const num = (rental.start_unit_no || 1) + i;
        return `${rental.product_code || '1001'}_${String(num).padStart(2, '0')}`;
      });

  const handlePrint = () => {
    if (!printAreaRef.current) return;
    const printWindow = window.open('', '_blank', 'width=900,height=900');
    if (!printWindow) {
      window.print();
      return;
    }

    const contentHtml = printAreaRef.current.innerHTML;

    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <title>Cetak Barcode Unit Sewa - ${rental.rental_no}</title>
          <style>
            @page {
              size: A4 portrait;
              margin: 10mm;
            }
            body {
              font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
              color: #0f172a;
              margin: 0;
              padding: 0;
            }
            .header-banner {
              text-align: center;
              border-bottom: 2px solid #263f7d;
              padding-bottom: 8px;
              margin-bottom: 16px;
            }
            .header-banner h1 {
              font-size: 16px;
              margin: 0;
              color: #263f7d;
              font-weight: 800;
            }
            .header-banner p {
              font-size: 11px;
              margin: 2px 0 0 0;
              color: #64748b;
            }
            .grid-stickers {
              display: grid;
              grid-template-columns: repeat(3, 1fr);
              gap: 12px;
            }
            .sticker {
              border: 1.5px dashed #64748b;
              border-radius: 6px;
              padding: 8px 10px;
              text-align: center;
              background: #ffffff;
              page-break-inside: avoid;
            }
            .corp-tag {
              font-size: 9px;
              font-weight: 800;
              color: #263f7d;
              letter-spacing: 0.5px;
              text-transform: uppercase;
            }
            .prod-title {
              font-size: 11px;
              font-weight: 700;
              margin: 2px 0;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
            }
            .cust-label {
              font-size: 10px;
              color: #475569;
              margin-bottom: 4px;
            }
            .barcode-svg {
              margin: 4px auto;
              display: flex;
              justify-content: center;
            }
            .unit-code {
              font-family: monospace;
              font-size: 13px;
              font-weight: 800;
              letter-spacing: 1px;
              color: #0f172a;
              margin-top: 2px;
            }
          </style>
        </head>
        <body>
          <div class="header-banner">
            <h1>ERLASS Prokreatif Indonesia</h1>
            <p>Lembar Stiker Barcode Unit Serial MLK (Properti Inventaris Fisik) &bull; Unit: ${unitCodes[0]} s/d ${unitCodes[unitCodes.length - 1]} (${unitCodes.length} Unit Fisik)</p>
          </div>
          <div class="grid-stickers">
            ${contentHtml}
          </div>
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

  return (
    <Modal
      open
      title={`Cetak Barcode Unit Sewa MLK (${unitCodes.length} Stiker)`}
      description={`Stiker identifikasi barcode aset unit fisik MLK. Nomor unit dapat digunakan berulang kali untuk penyewaan ke sekolah yang berbeda. Format: [kode_produk]_[kode_unit].`}
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
          <div className="text-xs text-[var(--color-text-muted)] flex items-center gap-1.5">
            <CheckCircle2 size={15} color="#16a34a" />
            Unit berurutan sekuensial: <strong>{unitCodes[0]}</strong> s/d <strong>{unitCodes[unitCodes.length - 1]}</strong>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button variant="secondary" onClick={onClose}>
              Tutup
            </Button>
            <Button variant="primary" icon={<Printer size={15} />} onClick={handlePrint}>
              Cetak {unitCodes.length} Stiker Barcode
            </Button>
          </div>
        </div>
      }
    >
      <div className="mb-3 p-2.5 bg-blue-50 border border-blue-200 rounded text-xs text-blue-900 flex items-center gap-2">
        <CheckCircle2 size={16} className="text-blue-600 shrink-0" />
        <div>
          <strong>Stiker Inventaris Netral (Dapat Dipakai Berulang Kali):</strong> Nama sekolah tidak dicantumkan pada label stiker barcode agar unit fisik MLK dapat disewakan kembali ke berbagai sekolah berbeda tanpa perlu mencetak ulang stiker.
        </div>
      </div>

      <div style={{ maxHeight: '60vh', overflowY: 'auto', paddingRight: 4 }}>
        <div
          ref={printAreaRef}
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))',
            gap: 12,
          }}
        >
          {unitCodes.map((code) => (
            <div
              key={code}
              className="sticker"
              style={{
                border: '1.5px dashed #94a3b8',
                borderRadius: 8,
                padding: '10px 12px',
                textAlign: 'center',
                background: '#ffffff',
                boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
              }}
            >
              <div
                style={{
                  fontSize: 9,
                  fontWeight: 800,
                  color: '#263f7d',
                  letterSpacing: '0.5px',
                  textTransform: 'uppercase',
                }}
              >
                ERLASS Prokreatif Indonesia
              </div>
              <div
                style={{
                  fontSize: 11,
                  fontWeight: 700,
                  color: '#0f172a',
                  margin: '2px 0',
                  whiteSpace: 'nowrap',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                }}
                title={rental.product_name}
              >
                {rental.product_name || 'Mobile Learning Kit (MLK)'}
              </div>
              <div
                style={{
                  fontSize: 10,
                  color: '#475569',
                  marginBottom: 6,
                  fontWeight: 600,
                  whiteSpace: 'nowrap',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                }}
              >
                Properti Inventaris Sewa (Fisik)
              </div>

              {/* Barcode Visual Code128 Mock SVG */}
              <div style={{ display: 'flex', justifyContent: 'center', margin: '4px 0' }}>
                <svg width="180" height="42" viewBox="0 0 180 42">
                  <rect width="180" height="42" fill="#ffffff" />
                  <g fill="#0f172a">
                    {/* Barcode bars */}
                    <rect x="10" y="2" width="3" height="34" />
                    <rect x="15" y="2" width="2" height="34" />
                    <rect x="19" y="2" width="4" height="34" />
                    <rect x="25" y="2" width="2" height="34" />
                    <rect x="30" y="2" width="5" height="34" />
                    <rect x="37" y="2" width="2" height="34" />
                    <rect x="42" y="2" width="3" height="34" />
                    <rect x="48" y="2" width="4" height="34" />
                    <rect x="54" y="2" width="2" height="34" />
                    <rect x="58" y="2" width="6" height="34" />
                    <rect x="66" y="2" width="3" height="34" />
                    <rect x="71" y="2" width="2" height="34" />
                    <rect x="75" y="2" width="4" height="34" />
                    <rect x="82" y="2" width="3" height="34" />
                    <rect x="87" y="2" width="5" height="34" />
                    <rect x="94" y="2" width="2" height="34" />
                    <rect x="99" y="2" width="4" height="34" />
                    <rect x="105" y="2" width="3" height="34" />
                    <rect x="110" y="2" width="2" height="34" />
                    <rect x="114" y="2" width="6" height="34" />
                    <rect x="122" y="2" width="3" height="34" />
                    <rect x="127" y="2" width="4" height="34" />
                    <rect x="133" y="2" width="2" height="34" />
                    <rect x="137" y="2" width="5" height="34" />
                    <rect x="144" y="2" width="2" height="34" />
                    <rect x="148" y="2" width="4" height="34" />
                    <rect x="154" y="2" width="3" height="34" />
                    <rect x="160" y="2" width="2" height="34" />
                    <rect x="164" y="2" width="4" height="34" />
                  </g>
                </svg>
              </div>

              {/* Code Label */}
              <div
                style={{
                  fontFamily: 'monospace',
                  fontSize: 13,
                  fontWeight: 800,
                  letterSpacing: '1px',
                  color: '#263f7d',
                  marginTop: 2,
                }}
              >
                {code}
              </div>
            </div>
          ))}
        </div>
      </div>
    </Modal>
  );
}
