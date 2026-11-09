import { useLocation } from 'react-router-dom';
import { ChevronRight, Menu } from 'lucide-react';
import { Button } from '@/components/Button';
import { formatLongDate } from '@/lib/format';
import { findNavItem } from '@/routes/navigation';
import { ProfileDropdown } from './ProfileDropdown';

interface HeaderProps {
  onToggleSidebar: () => void;
}

export function Header({ onToggleSidebar }: HeaderProps) {
  const location = useLocation();
  const current = findNavItem(location.pathname);

  return (
    <header className="header">
      <Button
        className="header__menu"
        variant="ghost"
        iconOnly
        aria-label="Buka menu navigasi"
        icon={<Menu size={20} />}
        onClick={onToggleSidebar}
      />

      <nav className="breadcrumb" aria-label="Breadcrumb">
        <span>ERLASS PWMS</span>
        <ChevronRight size={14} aria-hidden="true" />
        <span className="breadcrumb__current" aria-current="page">
          {current?.label ?? 'Halaman'}
        </span>
      </nav>

      <div className="header__right">
        <span className="header__date">{formatLongDate()}</span>
        <ProfileDropdown />
      </div>
    </header>
  );
}
