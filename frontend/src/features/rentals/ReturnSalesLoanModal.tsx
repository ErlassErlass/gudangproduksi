import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { RotateCcw } from 'lucide-react';
import { http } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { formatDate } from '@/lib/format';
import type { SalesLoanRecord } from './SalesLoanReceiptModal';

interface ReturnSalesLoanModalProps {
  loan: SalesLoanRecord;
  onClose: () => void;
  onSuccess: (updatedLoan: SalesLoanRecord) => void;
}

export function ReturnSalesLoanModal({ loan, onClose, onSuccess }: ReturnSalesLoanModalProps) {
  const { notify } = useToast();
  const qc = useQueryClient();

  const [returnDate, setReturnDate] = useState(new Date().toISOString().slice(0, 10));
  const [returnNotes, setReturnNotes] = useState('Unit kembali lengkap dan berfungsi dengan baik.');

  const returnMutation = useMutation({
    mutationFn: async () => {
      const res = await http.post<{ data: SalesLoanRecord }>(`/sales-loans/${loan.id}/return`, {
        return_date: returnDate,
        return_notes: returnNotes.trim() || null,
      });
      return res.data.data;
    },
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: ['sales-loans'] });
      notify('Unit pinjaman sales berhasil dikembalikan. Bukti tanda terima siap dicetak.', 'success');
      onSuccess(data);
    },
    onError: (err: any) => {
      notify(err.message || 'Gagal memproses pengembalian unit.', 'error');
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!returnDate) {
      notify('Tanggal kembali wajib diisi.', 'error');
      return;
    }
    returnMutation.mutate();
  };

  return (
    <Modal
      open
      title="Konfirmasi Pengembalian Pinjaman Unit"
      description="Set tanggal kembali unit dan simpan catatan kondisi fisik untuk penerbitan bukti tanda terima pengembalian."
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose} disabled={returnMutation.isPending}>
            Batal
          </Button>
          <Button
            variant="primary"
            type="submit"
            form="return-loan-form"
            loading={returnMutation.isPending}
            icon={<RotateCcw size={15} />}
          >
            Konfirmasi Pengembalian
          </Button>
        </div>
      }
    >
      <form id="return-loan-form" onSubmit={handleSubmit} className="space-y-4">
        {/* Ringkasan Peminjaman */}
        <div
          style={{
            background: '#f8fafc',
            border: '1px solid #e2e8f0',
            borderRadius: 8,
            padding: 14,
            fontSize: 13,
          }}
        >
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
            <div>
              <span className="text-xs text-[var(--color-text-muted)]">No. Peminjaman:</span>
              <div className="font-mono font-bold">{loan.loan_no}</div>
            </div>
            <div>
              <span className="text-xs text-[var(--color-text-muted)]">Sales & GL:</span>
              <div className="font-semibold">{loan.sales_name} ({loan.group_leader})</div>
            </div>
            <div>
              <span className="text-xs text-[var(--color-text-muted)]">Sekolah / Klien:</span>
              <div className="font-semibold">{loan.customer_name || 'Sekolah'}</div>
            </div>
            <div>
              <span className="text-xs text-[var(--color-text-muted)]">Unit & Jumlah:</span>
              <div className="font-semibold text-[var(--color-primary-dark, #263f7d)]">
                {loan.product_name} &bull; <strong>{loan.quantity} Unit</strong>
              </div>
            </div>
            <div>
              <span className="text-xs text-[var(--color-text-muted)]">Tgl. Pengambilan:</span>
              <div>{formatDate(loan.loan_date)}</div>
            </div>
          </div>
        </div>

        <FormField label="Tanggal Kembali Sebenarnya" htmlFor="ret-date" required>
          <input
            id="ret-date"
            type="date"
            className="input font-medium"
            value={returnDate}
            onChange={(e) => setReturnDate(e.target.value)}
            required
          />
        </FormField>

        <FormField label="Catatan Kondisi Unit Saat Kembali (Opsional)" htmlFor="ret-notes">
          <textarea
            id="ret-notes"
            rows={3}
            className="input"
            placeholder="Mis. Unit kembali lengkap, modul sensor berfungsi normal, kotak bersih."
            value={returnNotes}
            onChange={(e) => setReturnNotes(e.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  );
}
