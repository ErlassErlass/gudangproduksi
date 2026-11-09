import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { BookOpen, ChevronDown, KeyRound, LogOut, Shield } from 'lucide-react';
import { useAuth } from '@/features/auth/AuthContext';
import { ProfileModal } from '@/features/auth/ProfileModal';
import { initials } from '@/lib/format';

export function ProfileDropdown() {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Tutup dropdown saat klik di luar elemen
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  if (!user) return null;

  const roleName = user.roles.map((r) => r.name).join(', ') || 'Pengguna';
  const userInitials = initials(user.name);

  const handleLogout = async () => {
    setLoggingOut(true);
    setIsOpen(false);
    try {
      await logout();
    } finally {
      setLoggingOut(false);
    }
  };

  return (
    <>
      <div style={{ position: 'relative' }} ref={dropdownRef}>
        {/* Tombol Trigger Profil Dropdown */}
        <button
          type="button"
          onClick={() => setIsOpen((prev) => !prev)}
          className="profile-dropdown-btn"
          aria-haspopup="true"
          aria-expanded={isOpen}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 10,
            padding: '5px 12px 5px 6px',
            borderRadius: '9999px',
            border: isOpen ? '1px solid var(--color-primary, #3859ac)' : '1px solid var(--color-border, #e2e8f0)',
            backgroundColor: isOpen ? '#f1f5f9' : '#ffffff',
            cursor: 'pointer',
            transition: 'all 0.15s ease-in-out',
            textAlign: 'left',
          }}
        >
          {/* Avatar Bulat dengan Inisial (DS) */}
          <span
            className="avatar"
            style={{
              width: 34,
              height: 34,
              borderRadius: '50%',
              backgroundColor: 'var(--color-primary-dark, #263f7d)',
              color: '#ffffff',
              display: 'grid',
              placeItems: 'center',
              fontWeight: 700,
              fontSize: '0.85rem',
              letterSpacing: '0.5px',
            }}
            aria-hidden="true"
          >
            {userInitials}
          </span>

          {/* Nama Pengguna & Role */}
          <div style={{ display: 'flex', flexDirection: 'column', lineHeight: 1.2 }}>
            <span
              style={{
                fontSize: '0.825rem',
                fontWeight: 700,
                color: 'var(--color-primary-dark, #263f7d)',
                whiteSpace: 'nowrap',
              }}
            >
              {user.name}
            </span>
            <span
              style={{
                fontSize: '0.72rem',
                color: 'var(--color-text-secondary, #64748b)',
                fontWeight: 500,
                whiteSpace: 'nowrap',
              }}
            >
              {roleName}
            </span>
          </div>

          <ChevronDown
            size={14}
            style={{
              color: 'var(--color-text-secondary, #64748b)',
              marginLeft: 2,
              transform: isOpen ? 'rotate(180deg)' : 'none',
              transition: 'transform 0.15s ease',
            }}
          />
        </button>

        {/* Menu Dropdown Popup */}
        {isOpen && (
          <div
            style={{
              position: 'absolute',
              right: 0,
              top: 'calc(100% + 8px)',
              width: 260,
              backgroundColor: '#ffffff',
              borderRadius: 12,
              boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
              border: '1px solid var(--color-border, #e2e8f0)',
              padding: '8px 0',
              zIndex: 1000,
              animation: 'fadeIn 0.15s ease-out',
            }}
          >
            {/* Header Informasi Pengguna */}
            <div style={{ padding: '10px 16px', borderBottom: '1px solid #f1f5f9' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                <span
                  style={{
                    width: 38,
                    height: 38,
                    borderRadius: '50%',
                    backgroundColor: 'var(--color-primary-dark, #263f7d)',
                    color: '#ffffff',
                    display: 'grid',
                    placeItems: 'center',
                    fontWeight: 700,
                    fontSize: '0.9rem',
                  }}
                >
                  {userInitials}
                </span>
                <div style={{ overflow: 'hidden' }}>
                  <div
                    style={{
                      fontWeight: 700,
                      fontSize: '0.875rem',
                      color: 'var(--color-primary-dark, #263f7d)',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis',
                      whiteSpace: 'nowrap',
                    }}
                  >
                    {user.name}
                  </div>
                  <div
                    style={{
                      fontSize: '0.75rem',
                      color: 'var(--color-text-secondary, #64748b)',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis',
                      whiteSpace: 'nowrap',
                    }}
                  >
                    {user.email}
                  </div>
                </div>
              </div>

              <div style={{ marginTop: 8 }}>
                <span
                  className="chip"
                  style={{
                    fontSize: '0.7rem',
                    fontWeight: 600,
                    backgroundColor: '#e0e7ff',
                    color: '#3730a3',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: 4,
                  }}
                >
                  <Shield size={10} />
                  {roleName}
                </span>
              </div>
            </div>

            {/* Menu Items */}
            <div style={{ padding: '4px 6px', display: 'flex', flexDirection: 'column', gap: 2 }}>
              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  navigate('/user-manual');
                }}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  padding: '9px 12px',
                  borderRadius: 6,
                  border: 'none',
                  backgroundColor: 'transparent',
                  color: 'var(--color-text-main, #0f172a)',
                  fontSize: '0.825rem',
                  fontWeight: 500,
                  cursor: 'pointer',
                  textAlign: 'left',
                  transition: 'background-color 0.15s ease',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#f8fafc')}
                onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
              >
                <BookOpen size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
                <span>Manual Pengguna ({roleName})</span>
              </button>

              <button
                type="button"
                onClick={() => {
                  setIsOpen(false);
                  setIsProfileModalOpen(true);
                }}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  padding: '9px 12px',
                  borderRadius: 6,
                  border: 'none',
                  backgroundColor: 'transparent',
                  color: 'var(--color-text-main, #0f172a)',
                  fontSize: '0.825rem',
                  fontWeight: 500,
                  cursor: 'pointer',
                  textAlign: 'left',
                  transition: 'background-color 0.15s ease',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#f8fafc')}
                onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
              >
                <KeyRound size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
                <span>Ubah Email & Password</span>
              </button>
            </div>

            <div style={{ borderTop: '1px solid #f1f5f9', margin: '4px 0' }} />

            {/* Tombol Logout */}
            <div style={{ padding: '4px 6px' }}>
              <button
                type="button"
                onClick={handleLogout}
                disabled={loggingOut}
                style={{
                  width: '100%',
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  padding: '9px 12px',
                  borderRadius: 6,
                  border: 'none',
                  backgroundColor: 'transparent',
                  color: '#dc2626',
                  fontSize: '0.825rem',
                  fontWeight: 600,
                  cursor: 'pointer',
                  textAlign: 'left',
                  transition: 'background-color 0.15s ease',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#fef2f2')}
                onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
              >
                <LogOut size={16} style={{ color: '#dc2626' }} />
                <span>{loggingOut ? 'Keluar...' : 'Keluar'}</span>
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal Update Email & Password */}
      {isProfileModalOpen && (
        <ProfileModal
          isOpen={isProfileModalOpen}
          onClose={() => setIsProfileModalOpen(false)}
        />
      )}
    </>
  );
}
