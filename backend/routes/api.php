<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BomController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\OperationsController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::patch('/auth/profile', [AuthController::class, 'updateProfile'])->name('api.v1.auth.profile.update');

        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('api.v1.dashboard.summary');

        // Phase 0 — Administration
        Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('api.v1.users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('api.v1.users.update');

        Route::get('/roles', [RoleController::class, 'index'])->name('api.v1.roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('api.v1.roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('api.v1.roles.update');
        Route::get('/permissions', [RoleController::class, 'permissions'])->name('api.v1.permissions.index');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.v1.audit-logs.index');

        // Phase 1 — Master Data
        Route::get('/products', [MasterDataController::class, 'products'])->name('api.v1.products.index');
        Route::post('/products', [MasterDataController::class, 'storeProduct'])->name('api.v1.products.store');
        Route::get('/products/{product}', [MasterDataController::class, 'showProduct'])->name('api.v1.products.show');
        Route::put('/products/{product}', [MasterDataController::class, 'updateProduct'])->name('api.v1.products.update');
        Route::delete('/products/{product}', [MasterDataController::class, 'destroyProduct'])->name('api.v1.products.destroy');
        Route::get('/products/{product}/stock', [MasterDataController::class, 'productStock'])->name('api.v1.products.stock');
        Route::post('/products/{product}/generate-barcode', [MasterDataController::class, 'generateBarcode'])->name('api.v1.products.barcode.generate');
        Route::post('/products/{product}/print-barcodes', [MasterDataController::class, 'printBarcodes'])->name('api.v1.products.barcode.print');
        Route::get('/products/{product}/barcode-prints', [MasterDataController::class, 'barcodePrints'])->name('api.v1.products.barcode.prints');
        Route::post('/barcode-prints/{barcodePrint}/reprint', [MasterDataController::class, 'reprintBarcode'])->name('api.v1.barcode.reprint');
        Route::get('/products/scan/{barcode}', [MasterDataController::class, 'scanBarcode'])->name('api.v1.products.barcode.scan');
        Route::post('/products/{product}/components', [MasterDataController::class, 'syncComponents'])->name('api.v1.products.components.sync');

        Route::get('/categories', [MasterDataController::class, 'categories'])->name('api.v1.categories.index');
        Route::post('/categories', [MasterDataController::class, 'storeCategory'])->name('api.v1.categories.store');
        Route::get('/uoms', [MasterDataController::class, 'uoms'])->name('api.v1.uoms.index');
        Route::post('/uoms', [MasterDataController::class, 'storeUom'])->name('api.v1.uoms.store');
        Route::get('/warehouses', [MasterDataController::class, 'warehouses'])->name('api.v1.warehouses.index');
        Route::post('/warehouses', [MasterDataController::class, 'storeWarehouse'])->name('api.v1.warehouses.store');
        Route::put('/warehouses/{warehouse}', [MasterDataController::class, 'updateWarehouse'])->name('api.v1.warehouses.update');
        Route::delete('/warehouses/{warehouse}', [MasterDataController::class, 'destroyWarehouse'])->name('api.v1.warehouses.destroy');
        Route::get('/locations', [MasterDataController::class, 'locations'])->name('api.v1.locations.index');
        Route::post('/locations', [MasterDataController::class, 'storeLocation'])->name('api.v1.locations.store');
        Route::put('/locations/{location}', [MasterDataController::class, 'updateLocation'])->name('api.v1.locations.update');
        Route::delete('/locations/{location}', [MasterDataController::class, 'destroyLocation'])->name('api.v1.locations.destroy');
        Route::get('/customers', [MasterDataController::class, 'customers'])->name('api.v1.customers.index');
        Route::post('/customers', [MasterDataController::class, 'storeCustomer'])->name('api.v1.customers.store');
        Route::put('/customers/{customer}', [MasterDataController::class, 'updateCustomer'])->name('api.v1.customers.update');
        Route::delete('/customers/{customer}', [MasterDataController::class, 'destroyCustomer'])->name('api.v1.customers.destroy');
        Route::get('/suppliers', [MasterDataController::class, 'suppliers'])->name('api.v1.suppliers.index');
        Route::post('/suppliers', [MasterDataController::class, 'storeSupplier'])->name('api.v1.suppliers.store');
        Route::get('/vendors', [MasterDataController::class, 'vendors'])->name('api.v1.vendors.index');
        Route::post('/vendors', [MasterDataController::class, 'storeVendor'])->name('api.v1.vendors.store');

        Route::get('/document-templates', [MasterDataController::class, 'documentTemplates'])->name('api.v1.document-templates.index');
        Route::post('/document-templates', [MasterDataController::class, 'storeDocumentTemplate'])->name('api.v1.document-templates.store');
        Route::put('/document-templates/{template}', [MasterDataController::class, 'updateDocumentTemplate'])->name('api.v1.document-templates.update');
        Route::delete('/document-templates/{template}', [MasterDataController::class, 'destroyDocumentTemplate'])->name('api.v1.document-templates.destroy');

        // Phase 2 — BOM
        Route::get('/boms', [BomController::class, 'index'])->name('api.v1.boms.index');
        Route::post('/boms', [BomController::class, 'store'])->name('api.v1.boms.store');
        Route::post('/boms/quick-save', [BomController::class, 'quickSave'])->name('api.v1.boms.quick-save');
        Route::post('/boms/assemble-stage', [BomController::class, 'assembleStage'])->name('api.v1.boms.assemble-stage');
        Route::get('/boms/by-product/{product}', [BomController::class, 'byProduct'])->name('api.v1.boms.by-product');
        Route::get('/boms/{bom}', [BomController::class, 'show'])->name('api.v1.boms.show');
        Route::put('/boms/{bom}', [BomController::class, 'update'])->name('api.v1.boms.update');
        Route::delete('/boms/{bom}', [BomController::class, 'destroy'])->name('api.v1.boms.destroy');
        Route::post('/boms/{bom}/versions', [BomController::class, 'storeVersion'])->name('api.v1.boms.versions.store');
        Route::post('/bom-versions/{bomVersion}/submit', [BomController::class, 'submitVersion'])->name('api.v1.bom-versions.submit');
        Route::post('/bom-versions/{bomVersion}/activate', [BomController::class, 'activateVersion'])->name('api.v1.bom-versions.activate');
        Route::get('/bom-versions/{bomVersion}/explosion', [BomController::class, 'explosion'])->name('api.v1.bom-versions.explosion');
        Route::get('/stage-templates', [BomController::class, 'stageTemplates'])->name('api.v1.stage-templates.index');

        // Phase 3 — Inventory & Warehouse (Gate A)
        Route::get('/inventory/balances', [InventoryController::class, 'balances'])->name('api.v1.inventory.balances');
        Route::get('/inventory/transactions', [InventoryController::class, 'transactions'])->name('api.v1.inventory.transactions');
        Route::get('/inventory/stock-card', [InventoryController::class, 'stockCard'])->name('api.v1.inventory.stock-card');
        Route::post('/inventory/opening-balances', [InventoryController::class, 'receiveOpening'])->name('api.v1.inventory.opening');
        Route::post('/inventory/transfers', [InventoryController::class, 'transfer'])->name('api.v1.inventory.transfers');
        Route::get('/inventory/adjustments', [InventoryController::class, 'adjustments'])->name('api.v1.inventory.adjustments.index');
        Route::post('/inventory/adjustments', [InventoryController::class, 'storeAdjustment'])->name('api.v1.inventory.adjustments.store');
        Route::post('/inventory/adjustments/{stockAdjustment}/approve', [InventoryController::class, 'approveAdjustment'])->name('api.v1.inventory.adjustments.approve');
        Route::post('/inventory/transactions/{inventoryTransaction}/reverse', [InventoryController::class, 'reverse'])->name('api.v1.inventory.transactions.reverse');

        // Phase 4 — Procurement (Gate B)
        Route::get('/purchase-orders', [OperationsController::class, 'purchaseOrders'])->name('api.v1.po.index');
        Route::post('/purchase-orders', [OperationsController::class, 'storePurchaseOrder'])->name('api.v1.po.store');
        Route::patch('/purchase-orders/{purchaseOrder}/status', [OperationsController::class, 'updatePurchaseOrderStatus'])->name('api.v1.po.status');
        Route::post('/purchase-orders/{purchaseOrder}/goods-receipts', [OperationsController::class, 'storeGoodsReceipt'])->name('api.v1.po.grn.store');

        // Phase 5–6 — Production Planning / MRP & Execution (Gate C)
        Route::get('/production-plans', [OperationsController::class, 'productionPlans'])->name('api.v1.production-plans.index');
        Route::post('/production-plans', [OperationsController::class, 'storeProductionPlan'])->name('api.v1.production-plans.store');
        Route::post('/production-plans/{productionPlan}/mrp-runs', [OperationsController::class, 'runMrp'])->name('api.v1.production-plans.mrp');
        Route::post('/production-plans/{productionPlan}/stages', [OperationsController::class, 'storeProductionPlanStage'])->name('api.v1.production-plans.stages.store');
        Route::put('/production-plan-stages/{stage}', [OperationsController::class, 'updateProductionPlanStage'])->name('api.v1.production-plan-stages.update');
        Route::delete('/production-plan-stages/{stage}', [OperationsController::class, 'destroyProductionPlanStage'])->name('api.v1.production-plan-stages.destroy');

        Route::get('/production-orders', [OperationsController::class, 'productionOrders'])->name('api.v1.production-orders.index');
        Route::post('/production-orders/release', [OperationsController::class, 'releaseProductionOrder'])->name('api.v1.production-orders.release');
        Route::post('/work-orders/{workOrder}/material-issues', [OperationsController::class, 'issueWorkOrderMaterials'])->name('api.v1.work-orders.issue');
        Route::post('/material-issue-items/{materialIssueItem}/return-unused', [OperationsController::class, 'returnUnusedMaterial'])->name('api.v1.material-issues.return');
        Route::post('/work-orders/{workOrder}/results', [OperationsController::class, 'recordWorkOrderResult'])->name('api.v1.work-orders.results');

        // Phase 7 — Vendor / GAP & Quality Control (Gate D)
        Route::get('/vendor-orders', [OperationsController::class, 'vendorOrders'])->name('api.v1.vendor-orders.index');
        Route::post('/vendor-orders', [OperationsController::class, 'storeVendorOrder'])->name('api.v1.vendor-orders.store');
        Route::post('/vendor-orders/{vendorOrder}/receipts', [OperationsController::class, 'receiveVendorOrder'])->name('api.v1.vendor-orders.receipts');
        Route::get('/quality-inspections', [OperationsController::class, 'qualityInspections'])->name('api.v1.qc.index');
        Route::get('/quality-inspections/checklist-template/{product}', [OperationsController::class, 'qcChecklistTemplate'])->name('api.v1.qc.checklist-template');
        Route::post('/quality-inspections', [OperationsController::class, 'storeQualityInspection'])->name('api.v1.qc.store');

        // Phase 8 — Rental & Distribution (Gate D)
        Route::get('/rental-orders', [OperationsController::class, 'rentalOrders'])->name('api.v1.rental-orders.index');
        Route::get('/rental-orders/units-tracking', [OperationsController::class, 'schoolRentalUnits'])->name('api.v1.rental-orders.units-tracking');
        Route::get('/rental-orders/units/scan/{unitCode}', [OperationsController::class, 'scanSchoolRentalUnit'])->name('api.v1.rental-orders.units.scan');
        Route::patch('/rental-orders/units/update-assignment', [OperationsController::class, 'updateUnitAssignment'])->name('api.v1.rental-orders.units.update-assignment');
        Route::post('/rental-orders', [OperationsController::class, 'storeRentalOrder'])->name('api.v1.rental-orders.store');
        Route::patch('/rental-orders/{rentalOrder}/status', [OperationsController::class, 'updateRentalOrderStatus'])->name('api.v1.rental-orders.status');
        Route::post('/rental-orders/{rentalOrder}/dispatch', [OperationsController::class, 'dispatchRental'])->name('api.v1.rental-orders.dispatch');
        Route::post('/rental-orders/{rentalOrder}/generate-do', [OperationsController::class, 'generateRentalDeliveryOrder'])->name('api.v1.rental-orders.generate-do');
        Route::post('/rental-orders/{rentalOrder}/returns', [OperationsController::class, 'returnRental'])->name('api.v1.rental-orders.returns');
        Route::post('/rental-returns/{rentalReturn}/inspect', [OperationsController::class, 'inspectRentalReturn'])->name('api.v1.rental-returns.inspect');

        // Sales Loans & Marketing Team
        Route::get('/marketing-team', [OperationsController::class, 'marketingTeam'])->name('api.v1.marketing-team.index');
        Route::get('/sales-loans', [OperationsController::class, 'salesLoans'])->name('api.v1.sales-loans.index');
        Route::post('/sales-loans', [OperationsController::class, 'storeSalesLoan'])->name('api.v1.sales-loans.store');
        Route::post('/sales-loans/{salesLoan}/return', [OperationsController::class, 'returnSalesLoan'])->name('api.v1.sales-loans.return');

        Route::get('/shipments', [OperationsController::class, 'shipments'])->name('api.v1.shipments.index');
        Route::post('/shipments', [OperationsController::class, 'storeShipment'])->name('api.v1.shipments.store');
        Route::patch('/shipments/{shipment}/status', [OperationsController::class, 'updateShipmentStatus'])->name('api.v1.shipments.status');

        // Phase 9 — Material Cost & Operational Reports (Gate E)
        Route::get('/reports/material-cost', [OperationsController::class, 'materialCostReport'])->name('api.v1.reports.material-cost');
        Route::get('/reports/overview', [OperationsController::class, 'operationalOverviewReport'])->name('api.v1.reports.overview');
    });
});
