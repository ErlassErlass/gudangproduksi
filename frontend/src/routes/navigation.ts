import type { LucideIcon } from 'lucide-react';
import {
  BarChart3,
  BookOpen,
  Boxes,
  ClipboardCheck,
  Cpu,
  FileCog,
  FileText,
  Gauge,
  KeyRound,
  Layers,
  LayoutDashboard,
  Network,
  Package,
  PackageCheck,
  PackageSearch,
  ScrollText,
  ShoppingCart,
  Truck,
  Users,
  Warehouse,
  Wallet,
} from 'lucide-react';

export interface NavItem {
  label: string;
  path: string;
  icon: LucideIcon;
  /** Any of these permissions grants visibility. Backend still enforces access. */
  permission?: string | string[];
  /** Roadmap phase in which the module is delivered (agent_docs/12_IMPLEMENTATION_ROADMAP.md). */
  phase: number;
  /** Whether the module is implemented in the current build. */
  available: boolean;
}

export interface NavGroup {
  label: string;
  items: NavItem[];
}

/** Main navigation follows agent_docs/10_UI_UX_GUIDELINES.md with dedicated catalog & product separation. */
export const NAVIGATION: NavGroup[] = [
  {
    label: 'Utama',
    items: [
      { label: 'Dashboard', path: '/', icon: LayoutDashboard, permission: 'dashboard.view', phase: 0, available: true },
    ],
  },
  {
    label: 'Katalog & Produk',
    items: [
      { label: 'Komponen', path: '/catalog/components', icon: Cpu, permission: 'master_data.view', phase: 1, available: true },
      { label: 'Modul', path: '/catalog/modules', icon: Layers, permission: 'master_data.view', phase: 1, available: true },
      { label: 'Box', path: '/catalog/boxes', icon: Package, permission: 'master_data.view', phase: 1, available: true },
      { label: 'Finished Good & Jasa', path: '/catalog/finished-goods', icon: PackageCheck, permission: 'master_data.view', phase: 1, available: true },
    ],
  },
  {
    label: 'Data & Perencanaan',
    items: [
      { label: 'BOM', path: '/bom', icon: Network, permission: 'bom.view', phase: 2, available: true },
      { label: 'Pengadaan', path: '/procurement', icon: ShoppingCart, permission: 'procurement.view', phase: 4, available: true },
      { label: 'Rencana Produksi', path: '/production-planning', icon: Gauge, permission: 'production.plan.view', phase: 5, available: true },
    ],
  },
  {
    label: 'Gudang & Operasional',
    items: [
      { label: 'Inventaris', path: '/inventory', icon: Boxes, permission: 'inventory.view', phase: 3, available: true },
      { label: 'Lokasi Penyimpanan', path: '/master-data/locations', icon: Warehouse, permission: ['master_data.view', 'warehouse.view'], phase: 1, available: true },
      { label: 'Quality Control', path: '/quality-control', icon: ClipboardCheck, permission: 'qc.view', phase: 7, available: true },
      { label: 'Sewa MLK Sekolah', path: '/rentals/schools', icon: PackageSearch, permission: 'rental.view', phase: 8, available: true },
      { label: 'Peminjaman Sales', path: '/rentals/sales-loans', icon: PackageSearch, permission: 'rental.view', phase: 8, available: true },
      { label: 'Distribusi', path: '/distribution', icon: Truck, permission: 'distribution.view', phase: 8, available: true },
    ],
  },
  {
    label: 'Analisis',
    items: [
      { label: 'Biaya Material', path: '/material-cost', icon: Wallet, permission: 'material_cost.view', phase: 9, available: true },
      { label: 'Laporan', path: '/reports', icon: BarChart3, permission: 'reports.view', phase: 9, available: true },
    ],
  },
  {
    label: 'Administrasi',
    items: [
      { label: 'Manual Pengguna', path: '/user-manual', icon: BookOpen, phase: 0, available: true },
      { label: 'Manajemen Customer', path: '/administration/customers', icon: Users, permission: 'master_data.view', phase: 1, available: true },
      { label: 'Template Surat', path: '/administration/templates', icon: FileText, permission: 'master_data.view', phase: 1, available: true },
      { label: 'Pengguna', path: '/administration/users', icon: Users, permission: 'administration.user.view', phase: 0, available: true },
      { label: 'Role & Izin', path: '/administration/roles', icon: KeyRound, permission: 'administration.role.view', phase: 0, available: true },
      { label: 'Penomoran Dokumen', path: '/administration/numbering', icon: FileCog, permission: 'administration.settings.manage', phase: 0, available: false },
      { label: 'Audit Log', path: '/administration/audit-logs', icon: ScrollText, permission: 'audit.view', phase: 0, available: true },
    ],
  },
];

export function findNavItem(pathname: string): NavItem | undefined {
  const items = NAVIGATION.flatMap((group) => group.items);
  return (
    items.find((item) => item.path === pathname) ??
    items
      .filter((item) => item.path !== '/' && pathname.startsWith(item.path))
      .sort((a, b) => b.path.length - a.path.length)[0]
  );
}
