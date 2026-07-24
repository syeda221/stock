<?php

use App\Http\Controllers\ArrivedFromController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpiryController;
use App\Http\Controllers\InboundController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\OutboundController;
use App\Http\Controllers\PackingTypeController;
// use App\Http\Controllers\PalletTransferController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\QcStatusController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransporterController;
use App\Http\Controllers\UomController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('notifications');
    Route::get('/expiry', [ExpiryController::class, 'index'])->name('expiry.index');
    Route::post('/expiry/{item}/toggle-sale', [ExpiryController::class, 'toggleSale'])->name('expiry.toggle-sale');

    // UOM Routes
    Route::get('/uoms', [UomController::class, 'index'])->name('uom.index');
    Route::get('/uoms/create', [UomController::class, 'create'])->name('uom.create');
    Route::post('/uoms', [UomController::class, 'store'])->name('uom.store');
    Route::get('/uoms/{uom}/edit', [UomController::class, 'edit'])->name('uom.edit');
    Route::put('/uoms/{uom}', [UomController::class, 'update'])->name('uom.update');
    Route::delete('/uoms/{uom}', [UomController::class, 'destroy'])->name('uom.destroy');

    // Packing Type Routes
    Route::get('/packing-types', [PackingTypeController::class, 'index'])->name('packing-type.index');
    Route::get('/packing-types/create', [PackingTypeController::class, 'create'])->name('packing-type.create');
    Route::post('/packing-types', [PackingTypeController::class, 'store'])->name('packing-type.store');
    Route::get('/packing-types/{packingType}/edit', [PackingTypeController::class, 'edit'])->name('packing-type.edit');
    Route::put('/packing-types/{packingType}', [PackingTypeController::class, 'update'])->name('packing-type.update');
    Route::delete('/packing-types/{packingType}', [PackingTypeController::class, 'destroy'])->name('packing-type.destroy');

    // Product Category Routes
    Route::get('/product-categories', [ProductCategoryController::class, 'index'])->name('product-category.index');
    Route::get('/product-categories/create', [ProductCategoryController::class, 'create'])->name('product-category.create');
    Route::post('/product-categories', [ProductCategoryController::class, 'store'])->name('product-category.store');
    Route::get('/product-categories/{productCategory}/edit', [ProductCategoryController::class, 'edit'])->name('product-category.edit');
    Route::put('/product-categories/{productCategory}', [ProductCategoryController::class, 'update'])->name('product-category.update');
    Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->name('product-category.destroy');

    // Product Group Routes
    Route::get('/product-groups', [ProductGroupController::class, 'index'])->name('product-group.index');
    Route::get('/product-groups/create', [ProductGroupController::class, 'create'])->name('product-group.create');
    Route::post('/product-groups', [ProductGroupController::class, 'store'])->name('product-group.store');
    Route::get('/product-groups/{productGroup}/edit', [ProductGroupController::class, 'edit'])->name('product-group.edit');
    Route::put('/product-groups/{productGroup}', [ProductGroupController::class, 'update'])->name('product-group.update');
    Route::delete('/product-groups/{productGroup}', [ProductGroupController::class, 'destroy'])->name('product-group.destroy');

    // Product Routes
    Route::get('/products', [ProductController::class, 'index'])->name('product.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('product.create');
    Route::post('/products', [ProductController::class, 'store'])->name('product.store');
    Route::get('/products/export/csv', [ProductController::class, 'export'])->name('product.export');
    Route::get('/products/import/template', [ProductController::class, 'downloadTemplate'])->name('product.import.template');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('product.import');
    Route::post('/products/import', [ProductController::class, 'importStore'])->name('product.import.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('product.destroy');

    // Warehouse Routes
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouse.index');
    Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouse.create');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouse.store');
    Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouse.edit');
    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouse.update');
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouse.destroy');

    // Warehouse Details (Drill-down: Warehouses → Rows → Pallets)
    Route::get('/warehouses/details', [WarehouseController::class, 'details'])->name('warehouse.details');
    Route::get('/warehouses/export/pdf', [WarehouseController::class, 'exportPdf'])->name('warehouse.export.pdf');
    Route::get('/warehouses/{warehouse}/rows', [WarehouseController::class, 'getRows'])->name('warehouse.rows');
    Route::get('/warehouses/rows/{row}/pallets', [WarehouseController::class, 'getPallets'])->name('warehouse.row.pallets');

    // Vendor Routes
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendor.index');
    Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendor.create');
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendor.store');
    Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendor.edit');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendor.update');
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendor.destroy');

    // Customer Routes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customer.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customer.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customer.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customer.destroy');


    // Transporter Routes
    Route::get('/transporters', [TransporterController::class, 'index'])->name('transporter.index');
    Route::get('/transporters/create', [TransporterController::class, 'create'])->name('transporter.create');
    Route::post('/transporters', [TransporterController::class, 'store'])->name('transporter.store');
    Route::get('/transporters/{transporter}/edit', [TransporterController::class, 'edit'])->name('transporter.edit');
    Route::put('/transporters/{transporter}', [TransporterController::class, 'update'])->name('transporter.update');
    Route::delete('/transporters/{transporter}', [TransporterController::class, 'destroy'])->name('transporter.destroy');

    // Arrived From Routes
    Route::get('/arrived-from', [ArrivedFromController::class, 'index'])->name('arrived-from.index');
    Route::get('/arrived-from/create', [ArrivedFromController::class, 'create'])->name('arrived-from.create');
    Route::post('/arrived-from', [ArrivedFromController::class, 'store'])->name('arrived-from.store');
    Route::get('/arrived-from/{arrivedFrom}/edit', [ArrivedFromController::class, 'edit'])->name('arrived-from.edit');
    Route::put('/arrived-from/{arrivedFrom}', [ArrivedFromController::class, 'update'])->name('arrived-from.update');
    Route::delete('/arrived-from/{arrivedFrom}', [ArrivedFromController::class, 'destroy'])->name('arrived-from.destroy');

    // Opening Stock Routes
    Route::get('/opening-stock', [OpeningStockController::class, 'index'])->name('opening-stock.index');
    Route::get('/opening-stock/create', [OpeningStockController::class, 'create'])->name('opening-stock.create');
    Route::post('/opening-stock', [OpeningStockController::class, 'store'])->name('opening-stock.store');
    Route::get('/opening-stock/export/csv', [OpeningStockController::class, 'export'])->name('opening-stock.export');
    Route::get('/opening-stock/import/template', [OpeningStockController::class, 'downloadTemplate'])->name('opening-stock.import.template');
    Route::get('/opening-stock/import', [OpeningStockController::class, 'importForm'])->name('opening-stock.import');
    Route::post('/opening-stock/import', [OpeningStockController::class, 'importStore'])->name('opening-stock.import.store');
    Route::get('/opening-stock/{item}/edit', [OpeningStockController::class, 'edit'])->name('opening-stock.edit');
    Route::put('/opening-stock/{item}', [OpeningStockController::class, 'update'])->name('opening-stock.update');
    Route::get('/opening-stock/product/{productId}/batches', [OpeningStockController::class, 'productBatches'])->name('opening-stock.product-batches');
    Route::post('/opening-stock/preview-pallets', [OpeningStockController::class, 'previewPallets'])->name('opening-stock.preview-pallets');
    Route::get('/opening-stock/transaction/{stockIn}', [OpeningStockController::class, 'showTransaction'])->name('opening-stock.transaction.show');
    Route::get('/opening-stock/transaction/{stockIn}/edit', [OpeningStockController::class, 'editTransaction'])->name('opening-stock.transaction.edit');
    Route::put('/opening-stock/transaction/{stockIn}', [OpeningStockController::class, 'updateTransaction'])->name('opening-stock.transaction.update');
    Route::delete('/opening-stock/transaction/{stockIn}', [OpeningStockController::class, 'destroyTransaction'])->name('opening-stock.transaction.destroy');

    // Inbound Routes
    Route::get('/inbound', [InboundController::class, 'index'])->name('inbound.index');
    Route::get('/inbound/create', [InboundController::class, 'create'])->name('inbound.create');
    Route::post('/inbound/preview-pallets', [InboundController::class, 'previewPallets'])->name('inbound.preview-pallets');
    Route::post('/inbound', [InboundController::class, 'store'])->name('inbound.store');
    Route::get('/inbound/{stockIn}/invoice', [InboundController::class, 'invoice'])->name('inbound.invoice');
    Route::get('/inbound/{stockIn}/print', [InboundController::class, 'print'])->name('inbound.print');
    Route::get('/inbound/{stockIn}/edit', [InboundController::class, 'edit'])->name('inbound.edit');
    Route::put('/inbound/{stockIn}', [InboundController::class, 'update'])->name('inbound.update');
    Route::get('/inbound/{stockIn}/gate-pass-export', [InboundController::class, 'gatePassExport'])->name('inbound.gate-pass-export');
    Route::get('/inbound/{stockIn}/items', [InboundController::class, 'getItems'])->name('inbound.items');
    Route::get('/inbound/export/csv', [InboundController::class, 'export'])->name('inbound.export');
    Route::post('/inbound/export/selected', [InboundController::class, 'export'])->name('inbound.exportSelected');
    Route::get('/inbound/import/template', [InboundController::class, 'downloadTemplate'])->name('inbound.import.template');
    Route::get('/inbound/import', [InboundController::class, 'importForm'])->name('inbound.import');
    Route::post('/inbound/import', [InboundController::class, 'importStore'])->name('inbound.import.store');

    // QC Management Routes
    Route::get('/qc-management', [QcStatusController::class, 'index'])->name('qc.index');
    Route::post('/qc-status/{item}/update', [QcStatusController::class, 'update'])->name('qc.status.update');
    Route::post('/qc-status/bulk-update', [QcStatusController::class, 'bulkUpdate'])->name('qc.bulk.update');
    Route::post('/qc-management/auto-reject-expired', [QcStatusController::class, 'autoRejectExpired'])->name('qc.auto.reject');

    // Pallet Transfers (Pending Controller Implementation)
    // Route::get('/pallet-transfers', [PalletTransferController::class, 'index'])->name('pallet-transfers.index');
    // Route::get('/pallet-transfers/create', [PalletTransferController::class, 'create'])->name('pallet-transfers.create');
    // Route::post('/pallet-transfers', [PalletTransferController::class, 'store'])->name('pallet-transfers.store');
    // Route::get('/pallet-transfers/api/occupied-pallets', [PalletTransferController::class, 'getOccupiedPallets'])->name('pallet-transfers.occupied');
    // Route::get('/pallet-transfers/api/free-pallets', [PalletTransferController::class, 'getFreePallets'])->name('pallet-transfers.free');
    // Route::get('/pallet-transfers/{id}/pdf', [PalletTransferController::class, 'pdf'])->name('pallet-transfers.pdf');

    // Outbound Routes
   // ================= OUTBOUND =================
Route::get('/outbound', [OutboundController::class, 'index'])->name('outbound.index');
Route::get('/outbound/create', [OutboundController::class, 'create'])->name('outbound.create');
Route::post('/outbound', [OutboundController::class, 'store'])->name('outbound.store');

// Static routes BEFORE parameterized {stockOut}
Route::get('/outbound/export/csv', [OutboundController::class, 'export'])->name('outbound.export');
Route::post('/outbound/export/selected', [OutboundController::class, 'export'])->name('outbound.exportSelected');
Route::get('/outbound/import/template', [OutboundController::class, 'downloadTemplate'])->name('outbound.import.template');
Route::get('/outbound/import', [OutboundController::class, 'importForm'])->name('outbound.import');
Route::post('/outbound/import', [OutboundController::class, 'importStore'])->name('outbound.import.store');

Route::get('/outbound/product-stock/{product}',
    [OutboundController::class, 'productStock']
)->name('outbound.product.stock');

Route::post('/outbound/preview-picks', [OutboundController::class, 'previewPicks'])->name('outbound.preview_picks');

// QUICK VIEW (modal / page)
Route::get('/outbound/{stockOut}', [OutboundController::class, 'show'])
    ->name('outbound.show');

// FULL INVOICE VIEW + PRINT
Route::get('/outbound/{stockOut}/invoice', [OutboundController::class, 'invoice'])
    ->name('outbound.invoice');

// DISPATCH DETAILS
Route::get('/outbound/{stockOut}/dispatch-details', [OutboundController::class, 'dispatchDetails'])
    ->name('outbound.dispatch_details');

// DC (Dispatch Challan – short)
Route::get('/outbound/{stockOut}/dc', [OutboundController::class, 'dc'])
    ->name('outbound.dc');

Route::get('/outbound/{stockOut}/print', [OutboundController::class, 'print'])->name('outbound.print');
// FUTURE (ready but baad mein use)
Route::get('/outbound/{stockOut}/edit', [OutboundController::class, 'edit'])->name('outbound.edit');
Route::put('/outbound/{stockOut}', [OutboundController::class, 'update'])->name('outbound.update');
Route::delete('/outbound/{stockOut}', [OutboundController::class, 'destroy'])->name('outbound.destroy');

    // Reporting Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/inbound', [ReportController::class, 'inbound'])->name('inbound');
        Route::get('/inbound/export', [ReportController::class, 'inboundExport'])->name('inbound.export');
        Route::get('/inbound/invoice-suggestions', [ReportController::class, 'inboundInvoiceSuggestions'])->name('inbound.invoice.suggestions');

        Route::get('/outbound', [ReportController::class, 'outbound'])->name('outbound');
        Route::get('/outbound/export', [ReportController::class, 'outboundExport'])->name('outbound.export');
        Route::get('/outbound/invoice-suggestions', [ReportController::class, 'outboundInvoiceSuggestions'])->name('outbound.invoice.suggestions');

        Route::get('/warehouse-stock', [ReportController::class, 'warehouseStock'])->name('warehouse-stock');
        Route::get('/warehouse-capacity', [ReportController::class, 'warehouseCapacity'])->name('warehouse-capacity');

        Route::get('/all-stocks', [ReportController::class, 'allStocks'])->name('all-stocks');
        Route::get('/all-stocks/export', [ReportController::class, 'allStocksExport'])->name('all-stocks.export');
        Route::get('/current-stock', [ReportController::class, 'currentStock'])->name('current-stock');
        Route::get('/current-stock/export', [ReportController::class, 'currentStockExport'])->name('current-stock.export');
        Route::get('/stock-ledger', [ReportController::class, 'stockLedger'])->name('stock-ledger');
        Route::get('/stock-ledger/export', [ReportController::class, 'stockLedgerExport'])->name('stock-ledger.export');
        Route::get('/stock-details/{product}', [ReportController::class, 'stockDetails'])->name('stock-details');
        // PDF endpoints for individual entries
        Route::get('/inbound/{stockIn}/pdf', [ReportController::class, 'inboundPdf'])->name('inbound.pdf');
        Route::get('/outbound/{stockOut}/pdf', [ReportController::class, 'outboundPdf'])->name('outbound.pdf');
    });

    // Roles & Permissions
    Route::resource('roles', \App\Http\Controllers\RoleController::class);
    Route::resource('users', \App\Http\Controllers\UserController::class);

});
