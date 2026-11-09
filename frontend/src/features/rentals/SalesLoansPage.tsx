import { useState, useMemo, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Briefcase,
  CheckCircle2,
  Clock,
  FileText,
  Plus,
  RotateCcw,
} from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { StatCard } from '@/components/StatCard';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useCustomers, useProducts } from '@/features/master-data/api';
import { formatDate } from '@/lib/format';
import { ReturnSalesLoanModal } from './ReturnSalesLoanModal';
import { SalesLoanReceiptModal, type SalesLoanRecord } from './SalesLoanReceiptModal';

interface MarketingTeamGroup {
  group_leader: string;
  sales: string[];
}

export function SalesLoansPage() {
  const { can } = useAuth();
  const canManage = can('rental.manage');
  const qc = useQueryClient();

  const [isNewOpen, setIsNewOpen] = useState(false);
  const [returningLoan, setReturningLoan] = useState<SalesLoanRecord | null>(null);
  const [receiptLoan, setReceiptLoan] = useState<SalesLoanRecord | null>(null);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<'ALL' | 'DIPINJAM' | 'DIKEMBALIKAN'>('ALL');

  // Query sales loans dari backend
  const loansQuery = useQuery({
    queryKey: ['sales-loans'],
    queryFn: async () => {
      const res = await http.get<ResourceResponse<SalesLoanRecord[]>>('/sales-loans');
      return res.data.data;
    },
  });

  const loans = loansQuery.data ?? [];
  const activeLoans = loans.filter((l) => l.status === 'DIPINJAM');
  const returnedLoans = loans.filter((l) => l.status === 'DIKEMBALIKAN');

  const filteredLoans = useMemo(() => {
    return loans.filter((item) => {
      if (statusFilter !== 'ALL' && item.status !== statusFilter) return false;
      if (search.trim()) {
        const q = search.toLowerCase();
        const noMatch = item.loan_no.toLowerCase().includes(q);
        const glMatch = item.group_leader.toLowerCase().includes(q);
        const salesMatch = item.sales_name.toLowerCase().includes(q);
        const custMatch = item.customer_name?.toLowerCase().includes(q) ?? false;
        const prodMatch = item.product_name?.toLowerCase().includes(q) ?? false;
        return noMatch || glMatch || salesMatch || custMatch || prodMatch;
      }
      return true;
    });
  }, [loans, statusFilter, search]);

  return (
    <>
      <PageHeader
        eyebrow="Gudang & Operasional — Sales & Marketing"
        title="Pelacakan Peminjaman Unit Projek (Sales)"
        description="Pelacakan peminjaman unit sample Finished Good MLK oleh tim sales dan marketing untuk kebutuhan demo presentasi sekolah serta bukti tanda terima pengembalian unit."
        actions={
          canManage && (
            <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsNewOpen(true)}>
              + Catat Peminjaman Sales
            </Button>
          )
        }
      />

      <div className="grid grid--3 mb-6">
        <StatCard
          label="Total Peminjaman Sales"
          value={loans.length}
          hint="Keseluruhan transaksi pinjam"
          icon={<Briefcase size={18} />}
        />
        <StatCard
          label="Sedang Dipinjam Sales"
          value={activeLoans.length}
          hint="Unit demo aktif di luar kantor"
          icon={<Clock size={18} />}
          accent
        />
        <StatCard
          label="Telah Dikembalikan"
          value={returnedLoans.length}
          hint="Tuntas kembali ke stok gudang"
          icon={<CheckCircle2 size={18} />}
        />
      </div>

      <section className="card">
        {/* Toolbar & Filter */}
        <div className="toolbar" style={{ marginBottom: 16 }}>
          <div className="search-wrap" style={{ flex: 1, minWidth: 260 }}>
            <input
              type="text"
              className="input"
              placeholder="Cari No. Peminjaman, GL Marketing, Sales, Sekolah, Produk..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <select
            className="select"
            style={{ width: 200 }}
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as any)}
            aria-label="Filter status peminjaman"
          >
            <option value="ALL">Semua Status</option>
            <option value="DIPINJAM">Sedang Dipinjam</option>
            <option value="DIKEMBALIKAN">Telah Dikembalikan</option>
          </select>
        </div>

        {loansQuery.isLoading ? (
          <LoadingState label="Memuat riwayat peminjaman sales..." />
        ) : filteredLoans.length === 0 ? (
          <EmptyState
            title="Belum ada peminjaman unit sales"
            description="Catat pengeluaran unit demo atau sample pembelajaran pertama untuk tim sales."
            action={
              canManage ? (
                <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsNewOpen(true)}>
                  + Catat Peminjaman Sales
                </Button>
              ) : undefined
            }
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th style={{ width: '150px' }}>No. Peminjaman</th>
                  <th>GL Marketing & Sales</th>
                  <th>Sekolah (Customer)</th>
                  <th>Produk & Jumlah</th>
                  <th style={{ width: '130px' }}>Tgl. Pengambilan</th>
                  <th style={{ width: '130px' }}>Tgl. Kembali</th>
                  <th style={{ width: '120px' }}>Status</th>
                  <th style={{ width: '180px', textAlign: 'center' }}>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {filteredLoans.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <code className="font-bold text-[var(--color-primary-dark, #263f7d)]">{item.loan_no}</code>
                      {item.notes && <div className="text-xs text-[var(--color-text-muted)] mt-1">{item.notes}</div>}
                    </td>
                    <td>
                      <div className="font-semibold">{item.sales_name}</div>
                      <div className="text-xs text-[var(--color-text-muted)]">
                        GL Marketing: <strong>{item.group_leader}</strong>
                      </div>
                    </td>
                    <td>
                      <div className="font-semibold text-[var(--color-text-primary, #34466f)]">
                        {item.customer_name || 'Sekolah Mitra'}
                      </div>
                      {item.customer_code && (
                        <div className="text-xs text-[var(--color-text-muted)] font-mono">{item.customer_code}</div>
                      )}
                    </td>
                    <td>
                      <div className="font-medium">{item.product_name || `Produk #${item.product_id}`}</div>
                      <div className="text-xs font-mono font-bold text-[var(--color-primary-dark, #263f7d)]">
                        {item.quantity} Unit
                      </div>
                    </td>
                    <td className="font-mono text-xs">
                      {formatDate(item.loan_date)}
                    </td>
                    <td className="font-mono text-xs">
                      {item.return_date ? (
                        <strong className="text-emerald-700">{formatDate(item.return_date)}</strong>
                      ) : (
                        <span className="chip" style={{ background: '#fef3c7', color: '#92400e', fontSize: '0.75rem' }}>
                          Belum Kembali
                        </span>
                      )}
                    </td>
                    <td>
                      <span
                        className="chip"
                        style={{
                          background: item.status === 'DIKEMBALIKAN' ? '#dcfce7' : '#e0e7ff',
                          color: item.status === 'DIKEMBALIKAN' ? '#15803d' : '#3730a3',
                          fontWeight: 700,
                          fontSize: '0.75rem',
                        }}
                      >
                        {item.status}
                      </span>
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'inline-flex', gap: 6, alignItems: 'center' }}>
                        {item.status === 'DIPINJAM' ? (
                          canManage && (
                            <Button
                              size="sm"
                              variant="secondary"
                              icon={<RotateCcw size={13} />}
                              onClick={() => setReturningLoan(item)}
                            >
                              Kembalikan
                            </Button>
                          )
                        ) : (
                          <Button
                            size="sm"
                            variant="primary"
                            icon={<FileText size={13} />}
                            onClick={() => setReceiptLoan(item)}
                          >
                            Cetak Tanda Terima (PDF)
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Modal Peminjaman Baru */}
      {isNewOpen && (
        <NewSalesLoanModal
          onClose={() => setIsNewOpen(false)}
          onSuccess={() => {
            qc.invalidateQueries({ queryKey: ['sales-loans'] });
            setIsNewOpen(false);
          }}
        />
      )}

      {/* Modal Konfirmasi Pengembalian (Set Tanggal Kembali) */}
      {returningLoan && (
        <ReturnSalesLoanModal
          loan={returningLoan}
          onClose={() => setReturningLoan(null)}
          onSuccess={(updated) => {
            setReturningLoan(null);
            // Langsung buka modal cetak tanda terima PDF
            setReceiptLoan(updated);
          }}
        />
      )}

      {/* Modal Soft File PDF Bukti Tanda Terima */}
      {receiptLoan && (
        <SalesLoanReceiptModal
          loan={receiptLoan}
          onClose={() => setReceiptLoan(null)}
        />
      )}
    </>
  );
}

function NewSalesLoanModal({
  onClose,
  onSuccess,
}: {
  onClose: () => void;
  onSuccess: () => void;
}) {
  const { notify } = useToast();

  // 1. Fetch Marketing Team (GL Marketing dan Sales di bawahnya)
  const marketingTeamQuery = useQuery({
    queryKey: ['marketing-team'],
    queryFn: async () => {
      const res = await http.get<ResourceResponse<MarketingTeamGroup[]>>('/marketing-team');
      return res.data.data;
    },
  });

  // 2. Fetch Customers
  const customersQuery = useCustomers();

  // 3. Fetch Finished Goods
  const products = useProducts({ per_page: 50, item_group: 'finished_good' });

  const marketingTeams = useMemo(() => {
    return marketingTeamQuery.data ?? [
      { group_leader: 'Budi Santoso', sales: ['Andi Wijaya', 'Rina Marlina', 'Dimas Pratama'] },
      { group_leader: 'Dewi Sartika', sales: ['Siti Rahma', 'Fajar Hidayat', 'Bambang Pamungkas'] },
      { group_leader: 'Hendro Kusumo', sales: ['Bayu Setiawan', 'Citra Lestari', 'Eko Prasetyo'] },
    ];
  }, [marketingTeamQuery.data]);

  const [selectedGL, setSelectedGL] = useState(marketingTeams[0]?.group_leader ?? '');
  const [selectedSales, setSelectedSales] = useState(marketingTeams[0]?.sales[0] ?? '');
  const [selectedCustomerId, setSelectedCustomerId] = useState('');
  const [selectedProductId, setSelectedProductId] = useState('');
  const [quantity, setQuantity] = useState('1');
  const [loanDate, setLoanDate] = useState(new Date().toISOString().slice(0, 10));
  const [notes, setNotes] = useState('');

  // Saat GL Marketing berganti -> update daftar sales dan pilih sales pertama
  const availableSales = useMemo(() => {
    const gl = marketingTeams.find((t) => t.group_leader === selectedGL);
    return gl?.sales ?? [];
  }, [marketingTeams, selectedGL]);

  const handleGLChange = (gl: string) => {
    setSelectedGL(gl);
    const team = marketingTeams.find((t) => t.group_leader === gl);
    const firstSales = team?.sales[0] ?? '';
    setSelectedSales(firstSales);
    setSelectedCustomerId('');
  };

  const handleSalesChange = (sales: string) => {
    setSelectedSales(sales);
    setSelectedCustomerId('');
  };

  // Saat Sales dipilih -> filter sekolah (customer) yang sales_name nya cocok dengan selectedSales
  const availableCustomers = useMemo(() => {
    const allCusts = customersQuery.data ?? [];
    if (!selectedSales) return allCusts;
    const matched = allCusts.filter(
      (c) => c.sales_name && c.sales_name.toLowerCase() === selectedSales.toLowerCase()
    );
    // Jika ada sekolah yang di-assign khusus ke sales ini, utamakan yang cocok; jika belum ada, berikan opsi semua sekolah
    return matched.length > 0 ? matched : allCusts;
  }, [customersQuery.data, selectedSales]);

  // Set default product if available
  useMemo(() => {
    if (products.data?.data && products.data.data.length > 0 && !selectedProductId) {
      setSelectedProductId(String(products.data.data[0].id));
    }
  }, [products.data?.data, selectedProductId]);

  const createMutation = useMutation({
    mutationFn: async () => {
      await http.post('/sales-loans', {
        group_leader: selectedGL,
        sales_name: selectedSales,
        customer_id: Number(selectedCustomerId),
        product_id: Number(selectedProductId),
        quantity: Math.max(1, Number(quantity) || 1),
        loan_date: loanDate,
        notes: notes.trim() || null,
      });
    },
    onSuccess: () => {
      notify('Peminjaman sales berhasil dicatat.', 'success');
      onSuccess();
    },
    onError: (err: any) => {
      notify(err.message || 'Gagal mencatat peminjaman sales.', 'error');
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!selectedGL || !selectedSales) {
      notify('GL Marketing dan Sales wajib dipilih.', 'error');
      return;
    }
    if (!selectedCustomerId) {
      notify('Sekolah / Customer mitra wajib dipilih.', 'error');
      return;
    }
    if (!selectedProductId) {
      notify('Produk yang dipinjam wajib dipilih.', 'error');
      return;
    }
    createMutation.mutate();
  };

  return (
    <Modal
      open
      title="Catat Peminjaman Unit Projek (Sales)"
      description="Catat pengeluaran unit demo Finished Good / kit pembelajaran untuk presentasi atau penawaran sales ke sekolah."
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose} disabled={createMutation.isPending}>
            Batal
          </Button>
          <Button
            variant="primary"
            type="submit"
            form="new-sales-loan-form"
            loading={createMutation.isPending}
          >
            Simpan Peminjaman
          </Button>
        </div>
      }
    >
      <form id="new-sales-loan-form" onSubmit={handleSubmit} className="space-y-4">
        {/* Baris 1: GL Marketing & Sales */}
        <div className="grid grid--2">
          <FormField label="Nama GL Marketing" htmlFor="sl-gl" required>
            <select
              id="sl-gl"
              className="select font-semibold"
              value={selectedGL}
              onChange={(e) => handleGLChange(e.target.value)}
              required
            >
              {marketingTeams.map((team) => (
                <option key={team.group_leader} value={team.group_leader}>
                  {team.group_leader}
                </option>
              ))}
            </select>
          </FormField>

          <FormField
            label="Nama Sales"
            htmlFor="sl-sales"
            required
            hint={`Sales di bawah ${selectedGL || 'GL Marketing'}`}
          >
            <select
              id="sl-sales"
              className="select font-semibold text-[var(--color-primary-dark, #263f7d)]"
              value={selectedSales}
              onChange={(e) => handleSalesChange(e.target.value)}
              required
            >
              {availableSales.map((sales) => (
                <option key={sales} value={sales}>
                  {sales}
                </option>
              ))}
            </select>
          </FormField>
        </div>

        {/* Baris 2: Sekolah (Customer) */}
        <FormField
          label="Sekolah (Customer)"
          htmlFor="sl-customer"
          required
          hint={`Daftar sekolah mitra yang terhubung dengan ${selectedSales}`}
        >
          <select
            id="sl-customer"
            className="select"
            value={selectedCustomerId}
            onChange={(e) => setSelectedCustomerId(e.target.value)}
            required
          >
            <option value="">Pilih sekolah mitra tujuan...</option>
            {availableCustomers.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name} {c.code ? `(${c.code})` : ''} {c.sales_name ? `— Sales: ${c.sales_name}` : ''}
              </option>
            ))}
          </select>
        </FormField>

        {/* Baris 3: Produk & Jumlah Unit */}
        <div className="grid grid--2">
          <FormField label="Produk / Kit Demo" htmlFor="sl-prod" required>
            <select
              id="sl-prod"
              className="select"
              value={selectedProductId}
              onChange={(e) => setSelectedProductId(e.target.value)}
              required
            >
              <option value="">Pilih produk Finished Good...</option>
              {products.data?.data.map((p) => (
                <option key={p.id} value={p.id}>
                  [{p.code}] {p.name}
                </option>
              ))}
            </select>
          </FormField>

          <FormField label="Jumlah Unit Dipinjam" htmlFor="sl-qty" required>
            <input
              id="sl-qty"
              type="number"
              min="1"
              className="input font-mono font-bold"
              value={quantity}
              onChange={(e) => setQuantity(e.target.value)}
              required
            />
          </FormField>
        </div>

        {/* Baris 4: Tanggal Pengambilan */}
        <FormField label="Tanggal Pengambilan (Pinjam)" htmlFor="sl-date" required>
          <input
            id="sl-date"
            type="date"
            className="input font-medium"
            value={loanDate}
            onChange={(e) => setLoanDate(e.target.value)}
            required
          />
        </FormField>

        {/* Baris 5: Catatan Opsional */}
        <FormField label="Catatan Peminjaman (Opsional)" htmlFor="sl-notes">
          <input
            id="sl-notes"
            type="text"
            className="input"
            placeholder="Mis. Untuk demo praktikum guru fisika dan kurikulum merdeka"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  );
}
