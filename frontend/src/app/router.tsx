import { createBrowserRouter } from 'react-router-dom';
import { AuditLogsPage } from '@/features/administration/AuditLogsPage';
import { RolesPage } from '@/features/administration/RolesPage';
import { UsersPage } from '@/features/administration/UsersPage';
import { LoginPage } from '@/features/auth/LoginPage';
import { BomPage } from '@/features/bom/BomPage';
import { ModulePlaceholderPage, NotFoundPage } from '@/features/common/PlaceholderPages';
import { DashboardPage } from '@/features/dashboard/DashboardPage';
import { InventoryPage, WarehousePage } from '@/features/inventory/InventoryPage';
import {
  CustomersPage,
  DocumentTemplatesPage,
  LocationsPage,
} from '@/features/master-data';
import {
  BoxesPage,
  ComponentsPage,
  FinishedGoodsPage,
  ModulesPage,
} from '@/features/catalog/CatalogPages';
import {
  DistributionPage,
  MaterialCostPage,
  ProcurementPage,
  ProductionPlanningPage,
  QualityControlPage,
  ReportsPage,
} from '@/features/operations/OperationsPages';
import { SchoolRentalsPage } from '@/features/rentals/SchoolRentalsPage';
import { SalesLoansPage } from '@/features/rentals/SalesLoansPage';
import { UserManualPage } from '@/features/manual/UserManualPage';
import { AppLayout } from '@/layouts/AppLayout';
import { NAVIGATION } from '@/routes/navigation';
import { RequireAuth, RequirePermission } from '@/routes/guards';

const IMPLEMENTED: Record<string, JSX.Element> = {
  '/': <DashboardPage />,
  '/user-manual': <UserManualPage />,
  '/catalog/components': <ComponentsPage />,
  '/catalog/modules': <ModulesPage />,
  '/catalog/boxes': <BoxesPage />,
  '/catalog/finished-goods': <FinishedGoodsPage />,
  '/master-data': <LocationsPage />,
  '/master-data/locations': <LocationsPage />,
  '/master-data/customers': <CustomersPage />,
  '/master-data/templates': <DocumentTemplatesPage />,
  '/administration/customers': <CustomersPage />,
  '/administration/templates': <DocumentTemplatesPage />,
  '/bom': <BomPage />,
  '/procurement': <ProcurementPage />,
  '/production-planning': <ProductionPlanningPage />,
  '/inventory': <InventoryPage />,
  '/warehouse': <WarehousePage />,
  '/quality-control': <QualityControlPage />,
  '/rental': <SchoolRentalsPage />,
  '/rentals/schools': <SchoolRentalsPage />,
  '/rentals/sales-loans': <SalesLoansPage />,
  '/distribution': <DistributionPage />,
  '/material-cost': <MaterialCostPage />,
  '/reports': <ReportsPage />,
  '/administration/users': <UsersPage />,
  '/administration/roles': <RolesPage />,
  '/administration/audit-logs': <AuditLogsPage />,
};

const moduleRoutes = NAVIGATION.flatMap((group) => group.items).map((item) => ({
  path: item.path === '/' ? undefined : item.path.replace(/^\//, ''),
  index: item.path === '/',
  element: (
    <RequirePermission permission={item.permission}>
      {item.available && IMPLEMENTED[item.path] ? IMPLEMENTED[item.path] : <ModulePlaceholderPage />}
    </RequirePermission>
  ),
}));

export const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: <RequireAuth />,
    children: [
      {
        element: <AppLayout />,
        children: [
          ...moduleRoutes.map((r) => (r.index ? { index: true as const, element: r.element } : { path: r.path, element: r.element })),
          { path: '*', element: <NotFoundPage /> },
        ],
      },
    ],
  },
]);
