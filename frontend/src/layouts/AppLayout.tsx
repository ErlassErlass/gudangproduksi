import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Header } from './Header';
import { Sidebar } from './Sidebar';

export function AppLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="app-shell">
      <Sidebar open={sidebarOpen} onNavigate={() => setSidebarOpen(false)} />
      <div
        className={`sidebar-backdrop ${sidebarOpen ? 'sidebar-backdrop--open' : ''}`}
        onClick={() => setSidebarOpen(false)}
        aria-hidden="true"
      />
      <div className="main">
        <Header onToggleSidebar={() => setSidebarOpen((v) => !v)} />
        <main className="content" id="main-content">
          <Outlet />
        </main>
        <footer className="footer">
          © {new Date().getFullYear()} ERLASS Prokreatif Indonesia · Production &amp; Warehouse Management System
        </footer>
      </div>
    </div>
  );
}
