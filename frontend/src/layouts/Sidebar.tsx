import { NavLink } from 'react-router-dom';
import { useAuth } from '@/features/auth/AuthContext';
import { NAVIGATION } from '@/routes/navigation';
import logoErlass from '@/assets/logo-erlass.png';

interface SidebarProps {
  open: boolean;
  onNavigate: () => void;
}

export function Sidebar({ open, onNavigate }: SidebarProps) {
  const { can } = useAuth();

  return (
    <aside className={`sidebar ${open ? 'sidebar--open' : ''}`} aria-label="Navigasi utama">
      <div className="sidebar__brand">
        <NavLink to="/" onClick={onNavigate} style={{ display: 'flex', alignItems: 'center', width: '100%', textDecoration: 'none' }}>
          <img
            src={logoErlass}
            alt="ERLASS Prokreatif Indonesia"
            className="sidebar__logo-img"
            style={{
              maxHeight: 44,
              width: 'auto',
              maxWidth: '100%',
              objectFit: 'contain',
            }}
          />
        </NavLink>
      </div>

      <nav className="sidebar__nav">
        {NAVIGATION.map((group) => {
          const items = group.items.filter((item) => can(item.permission));
          if (items.length === 0) return null;
          return (
            <div key={group.label}>
              <div className="sidebar__group-label">{group.label}</div>
              {items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  end={item.path === '/'}
                  onClick={onNavigate}
                  className={({ isActive }) => `nav-link ${isActive ? 'nav-link--active' : ''}`}
                >
                  <item.icon size={18} aria-hidden="true" />
                  <span>{item.label}</span>
                  {!item.available && <span className="nav-link__soon">Fase {item.phase}</span>}
                </NavLink>
              ))}
            </div>
          );
        })}
      </nav>

      <div className="sidebar__footer">
        <div className="help-card">
          <strong>Butuh bantuan?</strong>
          Hubungi administrator sistem untuk akses dan konfigurasi.
        </div>
        <div className="sidebar__version">ERLASS PWMS v{import.meta.env.VITE_APP_VERSION ?? '0.1.0'}</div>
      </div>
    </aside>
  );
}
