import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { CheckCircle2, Clock, Play, Plus, Trash2 } from 'lucide-react';
import { http } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { ConfirmationDialog } from '@/components/Modal';
import { useToast } from '@/components/Toast';

export interface ProductionPlanStageItem {
  id: number;
  production_plan_id: number;
  sequence: number;
  name: string;
  weight_percentage: string | number;
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
  const [deletingStage, setDeletingStage] = useState<ProductionPlanStageItem | null>(null);

  const [formName, setFormName] = useState('');
  const [formWeight, setFormWeight] = useState('25.00');
  const [formNotes, setFormNotes] = useState('');

  // Calculate weights
  const currentTotalWeight = stages.reduce((acc, s) => acc + Number(s.weight_percentage), 0);
  const completedWeight = stages
    .filter((s) => s.status === 'COMPLETED')
    .reduce((acc, s) => acc + Number(s.weight_percentage), 0);
  const remainingWeight = Math.max(0, 100 - currentTotalWeight);

  const addMutation = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/production-plans/${plan.id}/stages`, {
        name: formName.trim(),
        weight_percentage: Number(formWeight),
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
      notify('Tahap pengerjaan berhasil diperbarui.', 'success');
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
      payload: { status: newStatus },
    });
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Tahapan Pengerjaan: ${plan.plan_no}`}
      description={`Pembagian tahapan pengerjaan dinamis untuk [${plan.title}]. Akumulasi bobot hingga 100%.`}
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', width: '100%' }}>
          <Button variant="secondary" onClick={onClose}>
            Tutup
          </Button>
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
        {/* Progress Akumulatif Card */}
        <div
          style={{
            padding: '14px 18px',
            borderRadius: 'var(--radius-md, 12px)',
            background: 'var(--color-surface-muted, #f7f9fe)',
            border: '1px solid var(--color-border, #e1e8f5)',
          }}
        >
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
            <span style={{ fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)', fontSize: '0.9rem' }}>
              Progress Pengerjaan: {completedWeight.toFixed(1)}% / 100%
            </span>
            <span className="table__muted" style={{ fontSize: '0.8rem' }}>
              Total Bobot Terdaftar: <strong>{currentTotalWeight.toFixed(1)}%</strong>
              {remainingWeight > 0 && ` (Sisa: ${remainingWeight.toFixed(1)}%)`}
            </span>
          </div>

          {/* Progress Bar Container */}
          <div
            style={{
              width: '100%',
              height: 10,
              backgroundColor: '#e2e8f0',
              borderRadius: 6,
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
                  opacity: 0.3,
                  transition: 'width 0.3s ease',
                }}
              />
            )}
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
              <FormField label="Nama Tahap" htmlFor="stg-name" required>
                <input
                  id="stg-name"
                  className="input"
                  placeholder="Mis. Pemotongan & Solder Kabel"
                  value={formName}
                  onChange={(e) => setFormName(e.target.value)}
                />
              </FormField>

              <FormField
                label={`Bobot Persentase (%) — Maks: ${remainingWeight.toFixed(1)}%`}
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
                />
              </FormField>
            </div>

            <FormField label="Catatan / SOP Tahap (Opsional)" htmlFor="stg-notes">
              <input
                id="stg-notes"
                className="input"
                placeholder="Mis. Mengikuti checklist standar QC"
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
                <th style={{ width: '40px' }}>#</th>
                <th>Nama Tahap Pengerjaan</th>
                <th style={{ width: '90px', textAlign: 'right' }}>Bobot (%)</th>
                <th style={{ width: '130px' }}>Status</th>
                {canManage && <th style={{ width: '150px', textAlign: 'center' }}>Aksi</th>}
              </tr>
            </thead>
            <tbody>
              {stages.length === 0 ? (
                <tr>
                  <td colSpan={5} style={{ textAlign: 'center', padding: 24, color: '#94a3b8' }}>
                    Belum ada tahapan pengerjaan. Klik '+ Tambah Tahap Pengerjaan' untuk membagi alur kerja.
                  </td>
                </tr>
              ) : (
                stages.map((stage, idx) => {
                  const isCompleted = stage.status === 'COMPLETED';
                  const isInProgress = stage.status === 'IN_PROGRESS';

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
                            {/* Tombol transisi status */}
                            {!isCompleted && !isInProgress && (
                              <Button
                                size="sm"
                                variant="ghost"
                                iconOnly
                                icon={<Play size={13} color="var(--color-primary, #3859ac)" />}
                                onClick={() => handleUpdateStatus(stage, 'IN_PROGRESS')}
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
