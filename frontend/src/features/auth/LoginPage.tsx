import { useState, type FormEvent } from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { useAuth } from './AuthContext';
import logoErlass from '@/assets/logo-erlass.png';

export function LoginPage() {
  const { status, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<ApiError | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const from = (location.state as { from?: string } | null)?.from ?? '/';

  if (status === 'authenticated') {
    return <Navigate to={from} replace />;
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await login({ email, password });
      navigate(from, { replace: true });
    } catch (err) {
      setError(err instanceof ApiError ? err : new ApiError(0, { message: 'Login gagal.' }));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="login">
      <section className="login__hero" aria-hidden="true">
        <div className="sidebar__brand-name" style={{ color: '#fff' }}>
          ERLASS
        </div>
        <div>
          <h1>Production &amp; Warehouse Management System</h1>
          <p>
            Satu platform untuk perencanaan produksi, persediaan, gudang, QC, vendor GAP, rental, dan
            distribusi yang dapat ditelusuri.
          </p>
        </div>
        <small style={{ opacity: 0.7 }}>ERLASS Prokreatif Indonesia</small>
      </section>

      <section className="login__panel">
        <form className="login__form" onSubmit={handleSubmit} noValidate>
          <div style={{ marginBottom: 16 }}>
            <img
              src={logoErlass}
              alt="ERLASS Prokreatif Indonesia"
              style={{ maxHeight: 46, width: 'auto', objectFit: 'contain' }}
            />
          </div>
          <div className="page-header__eyebrow">ERLASS PWMS</div>
          <h2>Masuk ke akun Anda</h2>
          <p className="field__hint" style={{ marginBottom: 24 }}>
            Gunakan email dan kata sandi yang diberikan administrator.
          </p>

          {error && !error.isValidation && (
            <div className="alert alert--danger" role="alert">
              {error.message}
            </div>
          )}

          <FormField label="Email" htmlFor="email" required error={error?.fieldError('email')}>
            <input
              id="email"
              type="email"
              className="input"
              autoComplete="username"
              autoFocus
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </FormField>

          <FormField label="Kata sandi" htmlFor="password" required error={error?.fieldError('password')}>
            <input
              id="password"
              type="password"
              className="input"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </FormField>

          <Button type="submit" variant="primary" block loading={submitting}>
            Masuk
          </Button>
        </form>
      </section>
    </div>
  );
}
