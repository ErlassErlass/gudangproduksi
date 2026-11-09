import { useState, type FormEvent } from 'react';
import { KeyRound, Mail, Shield, User as UserIcon } from 'lucide-react';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { useAuth } from './AuthContext';
import { authApi } from './api';

interface ProfileModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function ProfileModal({ isOpen, onClose }: ProfileModalProps) {
  const { user, updateCurrentUser } = useAuth();
  const { notify } = useToast();

  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  if (!user) return null;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (!email.trim()) {
      setErrorMsg('Email pengguna wajib diisi.');
      return;
    }

    if (newPassword) {
      if (newPassword.length < 8) {
        setErrorMsg('Password baru minimal harus 8 karakter.');
        return;
      }
      if (newPassword !== confirmPassword) {
        setErrorMsg('Konfirmasi password baru tidak cocok.');
        return;
      }
      if (!currentPassword) {
        setErrorMsg('Masukkan password saat ini (lama) untuk verifikasi keamanan.');
        return;
      }
    }

    setIsSubmitting(true);
    try {
      const updated = await authApi.updateProfile({
        name: name.trim() || undefined,
        email: email.trim(),
        current_password: currentPassword || undefined,
        password: newPassword || undefined,
        password_confirmation: confirmPassword || undefined,
      });

      updateCurrentUser(updated);
      notify('Profil, email, dan password berhasil diperbarui.', 'success');
      onClose();
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.errors?.current_password?.[0] ||
        err?.response?.data?.errors?.email?.[0] ||
        'Gagal memperbarui profil.';
      setErrorMsg(msg);
      notify(msg, 'error');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="Pengaturan Profil & Keamanan Akun"
      description="Perbarui informasi akun, email login, dan kata sandi pengguna Anda langsung dari dalam sistem."
      size="md"
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose} disabled={isSubmitting}>
            Batal
          </Button>
          <Button variant="primary" loading={isSubmitting} onClick={handleSubmit}>
            Simpan Perubahan
          </Button>
        </div>
      }
    >
      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        {errorMsg && (
          <div className="alert alert--danger" style={{ margin: 0, fontSize: '0.85rem' }}>
            {errorMsg}
          </div>
        )}

        {/* Informasi Role / Peran Akun */}
        <div
          style={{
            background: 'var(--color-surface-muted, #f8fafc)',
            border: '1px solid var(--color-border, #e2e8f0)',
            borderRadius: 8,
            padding: '10px 14px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <Shield size={18} style={{ color: 'var(--color-primary, #3859ac)' }} />
            <div>
              <div style={{ fontSize: '0.75rem', color: 'var(--color-text-secondary, #64748b)' }}>
                Peran Pengguna (Role)
              </div>
              <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                {user.roles.map((r) => r.name).join(', ') || 'Pengguna Terdaftar'}
              </div>
            </div>
          </div>
          <span className="chip" style={{ background: '#dbeafe', color: '#1e40af', fontWeight: 600, fontSize: '0.75rem' }}>
            Aktif
          </span>
        </div>

        {/* Nama Lengkap */}
        <FormField label="Nama Lengkap" htmlFor="profile-name">
          <div style={{ position: 'relative' }}>
            <input
              id="profile-name"
              type="text"
              className="input"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Nama lengkap Anda..."
              style={{ paddingLeft: 34 }}
              required
            />
            <UserIcon size={16} style={{ position: 'absolute', left: 10, top: 11, color: '#94a3b8' }} />
          </div>
        </FormField>

        {/* Email Pengguna */}
        <FormField label="Email Pengguna" htmlFor="profile-email" required hint="Email ini digunakan untuk masuk / login ke ERLASS PWMS.">
          <div style={{ position: 'relative' }}>
            <input
              id="profile-email"
              type="email"
              className="input font-mono"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="nama@erlass.test"
              style={{ paddingLeft: 34 }}
              required
            />
            <Mail size={16} style={{ position: 'absolute', left: 10, top: 11, color: '#94a3b8' }} />
          </div>
        </FormField>

        {/* Seksi Ubah Kata Sandi */}
        <div
          style={{
            borderTop: '1px dashed #e2e8f0',
            paddingTop: 12,
            marginTop: 4,
            display: 'flex',
            flexDirection: 'column',
            gap: 12,
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
            <KeyRound size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
            <h4 style={{ margin: 0, fontSize: '0.9rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)' }}>
              Ubah Kata Sandi (Password)
            </h4>
          </div>
          <p style={{ margin: 0, fontSize: '0.75rem', color: '#64748b' }}>
            Kosongkan kolom kata sandi di bawah jika Anda hanya ingin memperbarui email atau nama profil.
          </p>

          <FormField label="Kata Sandi Saat Ini (Lama)" htmlFor="profile-current-pwd">
            <input
              id="profile-current-pwd"
              type="password"
              className="input"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              placeholder="Masukkan password saat ini jika ingin ganti password"
              autoComplete="current-password"
            />
          </FormField>

          <div className="grid grid--2" style={{ gap: 10 }}>
            <FormField label="Kata Sandi Baru" htmlFor="profile-new-pwd" hint="Minimal 8 karakter">
              <input
                id="profile-new-pwd"
                type="password"
                className="input"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="Minimal 8 karakter"
                autoComplete="new-password"
              />
            </FormField>

            <FormField label="Ulangi Kata Sandi Baru" htmlFor="profile-confirm-pwd">
              <input
                id="profile-confirm-pwd"
                type="password"
                className="input"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Ulangi kata sandi baru"
                autoComplete="new-password"
              />
            </FormField>
          </div>
        </div>
      </form>
    </Modal>
  );
}
