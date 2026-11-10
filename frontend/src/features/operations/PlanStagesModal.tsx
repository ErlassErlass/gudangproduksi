import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, Clock, Edit2, Play, Plus, Trash2, TrendingDown, TrendingUp } from 'lucide-react';
import { http } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { ConfirmationDialog } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { formatNumber } from '@/lib/format';

export interface ProductionPlanStageItem {
  id: number;
  production_plan_id: number;
  sequence: number;
  name: string;
  weight_percentage: string | number;
  target_units?: string | number | null;
  planned_date?: string | null;
  produced_qty?: string | number;
  output_category?: 'KURANG' | 'SESUAI' | 'LEBIH' | null;
  output_variance?: number | null;
  status: 'PENDING' | 'IN_PROGRESS' | 'COMPLETED';
  started_at?: string | null;
  completed_at?: string | null;
  notes?: string | null;
}

interface PlanStagesModalProps {
  plan: {
    id: number;
    plan_no: string;
    title: string;
    target_qty?: number;
    produced_qty?: number;
    output_category?: 'KURANG' | 'SESUAI' | 'LEBIH' | null;
    output_variance?: number | null;
    stages?: ProductionPlanStageItem[];
    progress_percentage?: number;
    total_stage_weight?: number;
  };
  isOpen: boolean;
  onClose: () => void;
  canManage: boolean;
}

export function PlanStagesModal({ plan, isOpen, onClose, canManage }: PlanStagesModalProps) {
  const { notify } = useToast();
  const qc = useQueryClient();

  const [stages, setStages] = useState<ProductionPlanStageItem[]>(plan.stages ?? []);
  const [isAdding, setIsAdding] = useState(false);
  const [editingStage, setEditingStage] = useState<ProductionPlanStageItem | null>(null);
  const [deletingStage, setDeletingStage] = useState<ProductionPlanStageItem | null>(null);

  const planTargetQty = Number(plan.target_qty ?? 0);

  const [formName, setFormName] = useState('');
  const [formWeight, setFormWeight] = useState('25.00');
  const [formTargetUnits, setFormTargetUnits] = useState(planTargetQty > 0 ? String(planTargetQty) : '0');
  const [formPlannedDate, setFormPlannedDate] = useState(new Date().toISOString().slice(0, 10));
  const [formProducedQty, setFormProducedQty] = useState('0');
  const [formNotes, setFormNotes] = useState('');

  // State untuk edit kuantitas dibuat pada tahap terpilih
  const [editTargetUnits, setEditTargetUnits] = useState('0');
  const [editPlannedDate, setEditPlannedDate] = useState('');
  const [editProducedQty, setEditProducedQty] = useState('0');
  const [editStatus, setEditStatus] = useState<'PENDING' | 'IN_PROGRESS' | 'COMPLETED'>('COMPLETED');
  const [editNotes, setEditNotes] = useState('');

  // Calculate weights
  const currentTotalWeight = stages.reduce((acc, s) => acc + Number(s.weight_percentage), 0);
  const completedWeight = stages
    .filter((s) => s.status === 'COMPLETED')
    .reduce((acc, s) => acc + Number(s.weight_percentage), 0);
  const remainingWeight = Math.max(0, 100 - currentTotalWeight);

  // Evaluasi total produk yang dibuat dari tahapan
  // Di alur produksi, tahap selesai terbaru atau tahap dengan kuantitas dibuat menentukan berapa banyak produk yang telah jadi
  const completedStagesWithQty = stages.filter((s) => s.status === 'COMPLETED' && Number(s.produced_qty ?? 0) > 0);
  const latestCompletedQty = completedStagesWithQty.length > 0
    ? Number(completedStagesWithQty[completedStagesWithQty.length - 1].produced_qty)
    : 0;
  const maxProducedAcrossStages = stages.reduce((max, s) => Math.max(max, Number(s.produced_qty ?? 0)), 0);
  const overallProducedQty = latestCompletedQty > 0 ? latestCompletedQty : maxProducedAcrossStages;

  // Tentukan kategori output keseluruhan terhadap target produksi
  let overallCategory: 'KURANG' | 'SESUAI' | 'LEBIH' | null = null;
  let overallVariance = 0;
  if (overallProducedQty > 0 && planTargetQty > 0) {
    overallVariance = overallProducedQty - planTargetQty;
    if (overallProducedQty < planTargetQty) {
      overallCategory = 'KURANG';
    } else if (overallProducedQty > planTargetQty) {
      overallCategory = 'LEBIH';
    } else {
      overallCategory = 'SESUAI';
    }
  }

  const addMutation = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/production-plans/${plan.id}/stages`, {
        name: formName.trim(),
        weight_percentage: Number(formWeight),
        target_units: formTargetUnits ? Number(formTargetUnits) : null,
        planned_date: formPlannedDate || null,
        produced_qty: Number(formProducedQty) || 0,
        notes: formNotes.trim() || undefined,
      });
      return (res.data as any).data;
    },
    onSuccess: (newStage) => {
      setStages((prev) => [...prev, newStage]);
      qc.invalidateQueries({ queryKey: ['production-plans'] });
      notify('Tahap pengerjaan berhasil ditambahkan.', 'success');
      setIsAdding(false);
      setFormName('');
      setFormWeight(String(Math.min(25, remainingWeight)));
      setFormTargetUnits(planTargetQty > 0 ? String(planTargetQty) : '0');
      setFormPlannedDate(new Date().toISOString().slice(0, 10));
      setFormProducedQty('0');
      setFormNotes('');
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? err.message ?? 'Gagal menambahkan tahap pengerjaan.', 'error');
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) => {
      const res = await http.put(`/production-plan-stages/${id}`, payload);
      return (res.data as any).data;
    },
    onSuccess: (updatedStage) => {
      setStages((prev) => prev.map((s) => (s.id === updatedStage.id ? updatedStage : s)));
      qc.invalidateQueries({ queryKey: ['production-plans'] });
      notify('Tahap pengerjaan dan kuantitas produk berhasil diperbarui.', 'success');
      setEditingStage(null);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? err.message ?? 'Gagal memperbarui tahap pengerjaan.', 'error');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      return (await http.delete(`/production-plan-stages/${id}`)).data;
    },
    onSuccess: () => {
      if (deletingStage) {
        setStages((prev) => prev.filter((s) => s.id !== deletingStage.id));
      }
      qc.invalidateQueries({ queryKey: ['production-plans'] });
      notify('Tahap pengerjaan berhasil dihapus.', 'success');
      setDeletingStage(null);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? err.message ?? 'Gagal menghapus tahap pengerjaan.', 'error');
    },
  });

  const handleCreateSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!formName.trim() || Number(formWeight) <= 0) {
      notify('Nama tahap dan bobot persentase (> 0%) wajib diisi.', 'error');
      return;
    }
    if (Number(formWeight) > remainingWeight + 0.01) {
      notify(`Bobot tidak boleh melebihi sisa yang tersedia (${remainingWeight.toFixed(1)}%).`, 'error');
      return;
    }
    addMutation.mutate();
  };

  const handleUpdateStatus = (stage: ProductionPlanStageItem, newStatus: 'IN_PROGRESS' | 'COMPLETED') => {
    updateMutation.mutate({
      id: stage.id,
      payload: {
        status: newStatus,
        // Jika ditandai selesai dan belum ada kuantitas dibuat, defaultkan ke target rencana
        ...(newStatus === 'COMPLETED' && Number(stage.produced_qty ?? 0) === 0 && planTargetQty > 0
          ? { produced_qty: planTargetQty }
          : {}),
      },
    });
  };

  const openEditModal = (stage: ProductionPlanStageItem) => {
    setEditingStage(stage);
    setEditTargetUnits(stage.target_units !== null && stage.target_units !== undefined ? String(stage.target_units) : (planTargetQty > 0 ? String(planTargetQty) : '0'));
    setEditPlannedDate(stage.planned_date ? stage.planned_date.slice(0, 10) : new Date().toISOString().slice(0, 10));
    setEditProducedQty(String(Number(stage.produced_qty ?? 0)));
    setEditStatus(stage.status);
    setEditNotes(stage.notes ?? '');
  };

  const handleSaveEditStage = (e: FormEvent) => {
    e.preventDefault();
    if (!editingStage) return;
    updateMutation.mutate({
      id: editingStage.id,
      payload: {
        target_units: editTargetUnits ? Number(editTargetUnits) : null,
        planned_date: editPlannedDate || null,
        produced_qty: Number(editProducedQty),
        status: editStatus,
        notes: editNotes.trim() || undefined,
      },
    });
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Tahapan Pengerjaan: ${plan.plan_no}`}
      description={`Tahapan di rencana produksi menentukan berapa banyak produk yang dibuat dan mengkategorikan hasil produksi (Kurang / Sesuai / Lebih dari target).`}
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', width: '100%' }}>
          <Button variant="secondary" onClick={onClose}>
            Tutup
          </Button>
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* Card Rekapitulasi Output & Target Produksi */}
        <div
          style={{
            padding: '16px 18px',
            borderRadius: 'var(--radius-md, 12px)',
            background: 'var(--color-surface-muted, #f7f9fe)',
            border: '1px solid var(--color-border, #e1e8f5)',
            display: 'flex',
            flexDirection: 'column',
            gap: 12,
          }}
        >
          {/* Baris Perbandingan Target vs Realisasi */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 10 }}>
            <div>
              <div style={{ fontSize: '0.75rem', color: 'var(--color-text-secondary)', textTransform: 'uppercase', letterSpacing: '0.05em', fontWeight: 600 }}>
                Target vs Realisasi Produksi
              </div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginTop: 2 }}>
                <span style={{ fontSize: '1.25rem', fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)' }}>
                  {formatNumber(overallProducedQty)} unit dibuat
                </span>
                <span style={{ fontSize: '0.85rem', color: 'var(--color-text-secondary)' }}>
                  dari target <strong>{formatNumber(planTargetQty)} unit</strong>
                </span>
              </div>
            </div>

            {/* Badge Kategori Terhadap Target */}
            <div>
              {overallCategory === 'KURANG' && (
                <div className="chip" style={{ background: '#fef3c7', color: '#b45309', fontWeight: 700, padding: '6px 12px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: 5 }}>
                  <TrendingDown size={15} />
                  Kategori: Kurang dari Target ({overallVariance} unit)
                </div>
              )}
              {overallCategory === 'LEBIH' && (
                <div className="chip" style={{ background: '#e0e7ff', color: '#4338ca', fontWeight: 700, padding: '6px 12px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: 5 }}>
                  <TrendingUp size={15} />
                  Kategori: Lebih dari Target (+{overallVariance} unit)
                </div>
              )}
              {overallCategory === 'SESUAI' && (
                <div className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 700, padding: '6px 12px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: 5 }}>
                  <CheckCircle2 size={15} />
                  Kategori: Sesuai Target (Tepat {formatNumber(planTargetQty)} unit)
                </div>
              )}
              {!overallCategory && (
                <div className="chip" style={{ background: '#f1f5f9', color: '#64748b', fontWeight: 600, padding: '6px 12px', fontSize: '0.8rem' }}>
                  Target: {formatNumber(planTargetQty)} unit (Belum ada output selesai)
                </div>
              )}
            </div>
          </div>

          {/* Progress Bar Bobot */}
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6, fontSize: '0.8rem' }}>
              <span style={{ fontWeight: 600, color: 'var(--color-text-main)' }}>
                Progress Pengerjaan: {completedWeight.toFixed(1)}% / 100%
              </span>
              <span className="table__muted">
                Total Bobot: <strong>{currentTotalWeight.toFixed(1)}%</strong>
                {remainingWeight > 0 && ` (Tersisa: ${remainingWeight.toFixed(1)}%)`}
              </span>
            </div>
            <div
              style={{
                width: '100%',
                height: 8,
                backgroundColor: '#e2e8f0',
                borderRadius: 9999,
                overflow: 'hidden',
                display: 'flex',
              }}
            >
              <div
                style={{
                  width: `${Math.min(100, completedWeight)}%`,
                  backgroundColor: 'var(--color-success, #2f9d62)',
                  transition: 'width 0.3s ease',
                }}
              />
              {currentTotalWeight > completedWeight && (
                <div
                  style={{
                    width: `${Math.min(100 - completedWeight, currentTotalWeight - completedWeight)}%`,
                    backgroundColor: 'var(--color-primary, #3859ac)',
                    opacity: 0.35,
                    transition: 'width 0.3s ease',
                  }}
                />
              )}
            </div>
          </div>
        </div>

        {/* Toolbar Tambah Tahap */}
        {canManage && !isAdding && remainingWeight > 0.01 && (
          <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
            <Button
              size="sm"
              variant="primary"
              icon={<Plus size={14} />}
              onClick={() => {
                setFormWeight(String(Math.min(25, remainingWeight)));
                setFormProducedQty(planTargetQty > 0 ? String(planTargetQty) : '0');
                setIsAdding(true);
              }}
            >
              + Tambah Tahap Pengerjaan
            </Button>
          </div>
        )}

        {/* Form Tambah Tahap Dinamis */}
        {isAdding && (
          <form
            onSubmit={handleCreateSubmit}
            style={{
              padding: 14,
              borderRadius: 8,
              border: '1px solid var(--color-primary, #3859ac)',
              backgroundColor: '#ffffff',
              display: 'flex',
              flexDirection: 'column',
              gap: 12,
            }}
          >
            <div style={{ fontWeight: 700, fontSize: '0.875rem', color: 'var(--color-primary, #3859ac)' }}>
              Tambah Tahap Pengerjaan Baru
            </div>

            <div className="grid grid--2">
              <FormField label="Nama Tahap Pengerjaan" htmlFor="stg-name" required>
                <input
                  id="stg-name"
                  className="input"
                  placeholder="Mis. Tahap 1: Perakitan Modul M01 & M02"
                  value={formName}
                  onChange={(e) => setFormName(e.target.value)}
                  required
                />
              </FormField>

              <FormField
                label={`Bobot (%) — Maks: ${remainingWeight.toFixed(1)}%`}
                htmlFor="stg-weight"
                required
              >
                <input
                  id="stg-weight"
                  type="number"
                  step="0.01"
                  min="0.01"
                  max={remainingWeight}
                  className="input font-mono font-bold"
                  value={formWeight}
                  onChange={(e) => setFormWeight(e.target.value)}
                  required
                />
              </FormField>
            </div>

            <div className="grid grid--3">
              <FormField
                label="Target Unit Tahapan"
                htmlFor="stg-target-units"
                hint={`Target total rencana: ${formatNumber(planTargetQty)} unit`}
              >
                <input
                  id="stg-target-units"
                  type="number"
                  step="any"
                  min="0"
                  className="input font-mono font-bold"
                  placeholder="Unit yang akan diselesaikan"
                  value={formTargetUnits}
                  onChange={(e) => setFormTargetUnits(e.target.value)}
                />
              </FormField>

              <FormField label="Tanggal Rencana Pengerjaan" htmlFor="stg-date" required>
                <input
                  id="stg-date"
                  type="date"
                  className="input font-mono"
                  value={formPlannedDate}
                  onChange={(e) => setFormPlannedDate(e.target.value)}
                  required
                />
              </FormField>

              <FormField
                label="Realisasi Unit Selesai"
                htmlFor="stg-qty"
                hint="Dapat diisi saat pengerjaan"
              >
                <input
                  id="stg-qty"
                  type="number"
                  step="any"
                  min="0"
                  className="input font-mono"
                  placeholder="0 jika baru direncanakan"
                  value={formProducedQty}
                  onChange={(e) => setFormProducedQty(e.target.value)}
                />
              </FormField>
            </div>

            <FormField label="Catatan / SOP Tahap (Opsional)" htmlFor="stg-notes">
              <input
                id="stg-notes"
                className="input"
                placeholder="Mis. Mengikuti checklist standar QC ERLASS"
                value={formNotes}
                onChange={(e) => setFormNotes(e.target.value)}
              />
            </FormField>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 4 }}>
              <Button size="sm" variant="secondary" onClick={() => setIsAdding(false)}>
                Batal
              </Button>
              <Button size="sm" variant="primary" type="submit" loading={addMutation.isPending}>
                Simpan Tahap
              </Button>
            </div>
          </form>
        )}

        {/* Tabel Tahapan Pengerjaan */}
        <div className="table-wrap">
          <table className="table" style={{ fontSize: '0.85rem' }}>
            <thead>
              <tr>
                <th style={{ width: '36px' }}>#</th>
                <th>Nama Tahap Pengerjaan</th>
                <th style={{ width: '70px', textAlign: 'right' }}>Bobot</th>
                <th style={{ width: '100px' }}>Tgl. Rencana</th>
                <th style={{ width: '100px', textAlign: 'right' }}>Target Unit</th>
                <th style={{ width: '110px', textAlign: 'right' }}>Realisasi Selesai</th>
                <th style={{ width: '140px' }}>Kategori Target</th>
                <th style={{ width: '100px' }}>Status</th>
                {canManage && <th style={{ width: '110px', textAlign: 'center' }}>Aksi</th>}
              </tr>
            </thead>
            <tbody>
              {stages.length === 0 ? (
                <tr>
                  <td colSpan={9} style={{ textAlign: 'center', padding: 24, color: '#94a3b8' }}>
                    Belum ada tahapan pengerjaan. Klik '+ Tambah Tahap Pengerjaan' untuk menentukan target unit dan tanggal pengerjaan.
                  </td>
                </tr>
              ) : (
                stages.map((stage, idx) => {
                  const isCompleted = stage.status === 'COMPLETED';
                  const isInProgress = stage.status === 'IN_PROGRESS';
                  const stageQty = Number(stage.produced_qty ?? 0);
                  const stgTargetUnits = stage.target_units ? Number(stage.target_units) : planTargetQty;
                  const stageVariance = stageQty > 0 && stgTargetUnits > 0 ? stageQty - stgTargetUnits : null;

                  return (
                    <tr key={stage.id} style={{ backgroundColor: isCompleted ? '#f0fdf4' : undefined }}>
                      <td>
                        <span className="font-mono text-xs">{idx + 1}</span>
                      </td>
                      <td>
                        <div style={{ fontWeight: 600, color: 'var(--color-text-primary, #34466f)' }}>
                          {stage.name}
                        </div>
                        {stage.notes && <div className="table__muted" style={{ fontSize: '0.75rem' }}>{stage.notes}</div>}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700 }}>
                        {Number(stage.weight_percentage).toFixed(1)}%
                      </td>
                      <td style={{ fontSize: '0.8rem', fontFamily: 'monospace' }}>
                        {stage.planned_date ? stage.planned_date.slice(0, 10) : '-'}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700 }}>
                        {stage.target_units ? (
                          <span className="text-[var(--color-primary-dark,#263f7d)] font-bold">
                            {formatNumber(stage.target_units)} unit
                          </span>
                        ) : planTargetQty > 0 ? (
                          <span className="text-gray-500 font-medium">
                            {formatNumber(planTargetQty)} unit
                          </span>
                        ) : (
                          <span className="text-gray-400 font-normal">-</span>
                        )}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700 }}>
                        {stageQty > 0 ? (
                          <span className="text-[var(--color-primary-dark,#263f7d)] font-bold">
                            {formatNumber(stageQty)} unit
                          </span>
                        ) : (
                          <span className="text-gray-400 font-normal">-</span>
                        )}
                      </td>
                      <td>
                        {stageQty > 0 && stgTargetUnits > 0 ? (
                          stageVariance! < 0 ? (
                            <span className="chip" style={{ background: '#fef3c7', color: '#b45309', fontWeight: 600, fontSize: '0.75rem' }}>
                              Kurang ({stageVariance} unit)
                            </span>
                          ) : stageVariance! > 0 ? (
                            <span className="chip" style={{ background: '#e0e7ff', color: '#4338ca', fontWeight: 600, fontSize: '0.75rem' }}>
                              Lebih (+{stageVariance} unit)
                            </span>
                          ) : (
                            <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600, fontSize: '0.75rem' }}>
                              Sesuai Target
                            </span>
                          )
                        ) : (
                          <span className="text-gray-400 text-xs">-</span>
                        )}
                      </td>
                      <td>
                        {isCompleted ? (
                          <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600 }}>
                            <CheckCircle2 size={12} style={{ display: 'inline', marginRight: 4 }} />
                            Selesai
                          </span>
                        ) : isInProgress ? (
                          <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1', fontWeight: 600 }}>
                            <Clock size={12} style={{ display: 'inline', marginRight: 4 }} />
                            Dikerjakan
                          </span>
                        ) : (
                          <span className="chip" style={{ background: '#f1f5f9', color: '#64748b' }}>
                            Pending
                          </span>
                        )}
                      </td>
                      {canManage && (
                        <td style={{ textAlign: 'center' }}>
                          <div style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}>
                            {/* Tombol Edit Output Kuantitas */}
                            <Button
                              size="sm"
                              variant="ghost"
                              iconOnly
                              icon={<Edit2 size={13} color="var(--color-primary, #3859ac)" />}
                              onClick={() => openEditModal(stage)}
                              title="Edit kuantitas target unit, tgl rencana & realisasi"
                              aria-label="Edit kuantitas produk dibuat"
                            />

                            {/* Tombol transisi status */}
                            {!isCompleted && !isInProgress && (
                              <Button
                                size="sm"
                                variant="ghost"
                                iconOnly
                                icon={<Play size={13} color="var(--color-primary, #3859ac)" />}
                                onClick={() => handleUpdateStatus(stage, 'IN_PROGRESS')}
                                title="Mulai pengerjaan"
                                aria-label="Mulai pengerjaan tahap ini"
                              />
                            )}
                            {isInProgress && (
                              <Button
                                size="sm"
                                variant="ghost"
                                iconOnly
                                icon={<CheckCircle2 size={13} color="var(--color-success, #2f9d62)" />}
                                onClick={() => handleUpdateStatus(stage, 'COMPLETED')}
                                title="Tandai selesai"
                                aria-label="Tandai selesai"
                              />
                            )}

                            {/* Tombol Hapus: Jika COMPLETED, tidak dapat lagi dihapus! */}
                            {isCompleted ? (
                              <span
                                title="Tahap yang sudah selesai (COMPLETED) tidak dapat lagi dihapus!"
                                style={{ display: 'inline-block', opacity: 0.35, cursor: 'not-allowed', padding: 4 }}
                              >
                                <Trash2 size={14} color="#94a3b8" />
                              </span>
                            ) : (
                              <Button
                                size="sm"
                                variant="ghost"
                                iconOnly
                                icon={<Trash2 size={14} color="var(--color-danger, #d96a36)" />}
                                onClick={() => setDeletingStage(stage)}
                                title="Hapus tahap"
                                aria-label="Hapus tahap ini"
                              />
                            )}
                          </div>
                        </td>
                      )}
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Edit Output Kuantitas & Status Tahap */}
      {editingStage && (
        <Modal
          open
          title={`Update Tahap Pengerjaan — ${editingStage.name}`}
          description={`Atur target unit yang akan diselesaikan, tanggal rencana pengerjaan, dan realisasi produk dibuat.`}
          size="md"
          onClose={() => setEditingStage(null)}
          footer={
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
              <Button variant="secondary" onClick={() => setEditingStage(null)}>
                Batal
              </Button>
              <Button variant="primary" onClick={handleSaveEditStage} loading={updateMutation.isPending}>
                Simpan Perubahan
              </Button>
            </div>
          }
        >
          <form onSubmit={handleSaveEditStage} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            <div className="grid grid--2">
              <FormField label="Target Unit Tahapan" htmlFor="edit-stg-target" hint={`Total rencana: ${formatNumber(planTargetQty)} unit`}>
                <input
                  id="edit-stg-target"
                  type="number"
                  step="any"
                  min="0"
                  className="input font-mono font-bold"
                  value={editTargetUnits}
                  onChange={(e) => setEditTargetUnits(e.target.value)}
                />
              </FormField>

              <FormField label="Tanggal Rencana Pengerjaan" htmlFor="edit-stg-date">
                <input
                  id="edit-stg-date"
                  type="date"
                  className="input font-mono"
                  value={editPlannedDate}
                  onChange={(e) => setEditPlannedDate(e.target.value)}
                />
              </FormField>
            </div>

            <FormField label="Realisasi Produk Dibuat (Unit)" htmlFor="edit-stg-qty" required hint={`Target tahapan ini: ${formatNumber(Number(editTargetUnits) || planTargetQty)} unit`}>
              <input
                id="edit-stg-qty"
                type="number"
                step="any"
                min="0"
                className="input font-mono font-bold"
                value={editProducedQty}
                onChange={(e) => setEditProducedQty(e.target.value)}
                required
              />
            </FormField>

            {/* Preview Status Kategori Output */}
            {(() => {
              const targetRef = Number(editTargetUnits) > 0 ? Number(editTargetUnits) : planTargetQty;
              const prodQty = Number(editProducedQty);
              if (prodQty > 0 && targetRef > 0) {
                const diff = prodQty - targetRef;
                return (
                  <div
                    style={{
                      padding: '10px 12px',
                      borderRadius: 6,
                      background: diff < 0 ? '#fffbeb' : diff > 0 ? '#eef2ff' : '#f0fdf4',
                      border: `1px solid ${diff < 0 ? '#fde68a' : diff > 0 ? '#c7d2fe' : '#bbf7d0'}`,
                      fontSize: '0.8rem',
                    }}
                  >
                    {diff < 0 && (
                      <span style={{ color: '#b45309', fontWeight: 600 }}>
                        Kategori: <strong>Kurang dari Target ({diff} unit dari target {targetRef})</strong>
                      </span>
                    )}
                    {diff > 0 && (
                      <span style={{ color: '#4338ca', fontWeight: 600 }}>
                        Kategori: <strong>Lebih dari Target (+{diff} unit dari target {targetRef})</strong>
                      </span>
                    )}
                    {diff === 0 && (
                      <span style={{ color: '#15803d', fontWeight: 600 }}>
                        Kategori: <strong>Sesuai Target (Tepat {formatNumber(targetRef)} unit)</strong>
                      </span>
                    )}
                  </div>
                );
              }
              return null;
            })()}

            <FormField label="Status Tahap" htmlFor="edit-stg-status" required>
              <select
                id="edit-stg-status"
                className="select"
                value={editStatus}
                onChange={(e) => setEditStatus(e.target.value as any)}
              >
                <option value="PENDING">Pending (Belum Mulai)</option>
                <option value="IN_PROGRESS">Dikerjakan (In Progress)</option>
                <option value="COMPLETED">Selesai (Completed)</option>
              </select>
            </FormField>

            <FormField label="Catatan / SOP Tambahan" htmlFor="edit-stg-notes">
              <input
                id="edit-stg-notes"
                className="input"
                placeholder="Catatan hasil pengerjaan..."
                value={editNotes}
                onChange={(e) => setEditNotes(e.target.value)}
              />
            </FormField>
          </form>
        </Modal>
      )}

      {/* Dialog Konfirmasi Hapus Tahap */}
      <ConfirmationDialog
        open={Boolean(deletingStage)}
        title="Hapus Tahap Pengerjaan"
        message={`Apakah Anda yakin ingin menghapus tahap "${deletingStage?.name}" (${deletingStage?.weight_percentage}%)?`}
        confirmLabel="Hapus Tahap"
        danger
        loading={deleteMutation.isPending}
        onConfirm={() => deletingStage && deleteMutation.mutate(deletingStage.id)}
        onCancel={() => setDeletingStage(null)}
      />
    </Modal>
  );
}
