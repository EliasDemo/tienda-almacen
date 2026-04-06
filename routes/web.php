<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Almacen\EntryController;
use App\Http\Controllers\Almacen\PackageController;
use App\Http\Controllers\Almacen\TransferController;
use App\Http\Controllers\Almacen\OrderController as AlmacenOrderController;
use App\Http\Controllers\Pos\CreditController;
use App\Http\Controllers\Pos\OrderController as PosOrderController;
use App\Http\Controllers\Tienda\ReceptionController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Reports\CashReportController;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ══════════════════════════════════════════════════════════
    // ALMACÉN
    // ══════════════════════════════════════════════════════════
    Route::prefix('almacen')->name('almacen.')->middleware('role:admin|almacen')->group(function () {

        // Entradas (lotes)
        Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
        Route::get('/entries/create', [EntryController::class, 'create'])->name('entries.create');
        Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
        Route::get('/entries/{lot}', [EntryController::class, 'show'])->name('entries.show');

        // Cargamentos (despachos)
        Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
        Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
        Route::post('/transfers/{transfer}/dispatch', [TransferController::class, 'dispatch'])->name('transfers.dispatch');
        Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');

        // AJAX: bultos dentro del cargamento
        Route::post('/transfers/{transfer}/packages', [TransferController::class, 'addPackage'])->name('transfers.add-package');
        Route::delete('/transfers/{transfer}/packages/{package}', [TransferController::class, 'removePackage'])->name('transfers.remove-package');
        Route::patch('/transfer-lines/{line}/merma', [TransferController::class, 'updateMerma'])->name('transfers.update-merma');

        // Bultos / Etiquetas (consulta)
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('/packages/transfer/{transfer}', [PackageController::class, 'show'])->name('packages.show');
        Route::get('/packages/{package}/label', [PackageController::class, 'label'])->name('packages.label');

        // Pedidos recibidos de caja
        Route::get('/orders', [AlmacenOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/archive', [AlmacenOrderController::class, 'archive'])->name('orders.archive');
        Route::get('/orders/{order}', [AlmacenOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/confirm', [AlmacenOrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{order}/prepare', [AlmacenOrderController::class, 'startPreparing'])->name('orders.prepare');
        Route::post('/orders/{order}/link-transfer', [AlmacenOrderController::class, 'linkToTransfer'])->name('orders.link-transfer');
        Route::post('/orders/{order}/ready', [AlmacenOrderController::class, 'markReady'])->name('orders.ready');
    });

    // ══════════════════════════════════════════════════════════
    // TIENDA (Recepción)
    // ══════════════════════════════════════════════════════════
    Route::prefix('tienda')->name('tienda.')->middleware('role:admin|tienda')->group(function () {

        Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
        Route::post('/reception/quick-scan', [ReceptionController::class, 'quickScan'])->name('reception.quick-scan');
        Route::get('/reception/{transfer}', [ReceptionController::class, 'show'])->name('reception.show');
        Route::post('/reception/{transfer}/scan', [ReceptionController::class, 'scanPackage'])->name('reception.scan');
        Route::post('/reception/{transfer}/receive/{package}', [ReceptionController::class, 'receivePackage'])->name('reception.receive');
        Route::post('/reception/{transfer}/transit-sale/{package}', [ReceptionController::class, 'transitSale'])->name('reception.transit-sale');
        Route::post('/reception/{transfer}/finish', [ReceptionController::class, 'finish'])->name('reception.finish');
    });

    // ══════════════════════════════════════════════════════════
    // CAJA (POS)
    // ══════════════════════════════════════════════════════════
    Route::prefix('pos')->name('pos.')->middleware('role:admin|caja')->group(function () {

        // Caja registradora
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/open-register', [PosController::class, 'showOpenRegister'])->name('open-register');
        Route::post('/open-register', [PosController::class, 'openRegister'])->name('open-register.store');
        Route::get('/close-register', [PosController::class, 'showCloseRegister'])->name('close-register');
        Route::post('/close-register', [PosController::class, 'closeRegister'])->name('close-register.store');
        Route::get('/register-report/{register}', [PosController::class, 'registerReport'])->name('register-report');

        // AJAX: productos y escaneo
        Route::get('/products', [PosController::class, 'getProducts'])->name('products');
        Route::post('/scan', [PosController::class, 'scanPackage'])->name('scan');
        Route::post('/open-package/{package}', [PosController::class, 'openPackage'])->name('open-package');
        Route::post('/sale', [PosController::class, 'storeSale'])->name('store-sale');
        Route::post('/check-credit', [PosController::class, 'checkCredit'])->name('check-credit');

        // Fiados / Créditos
        Route::get('/credits', [CreditController::class, 'index'])->name('credits.index');
        Route::get('/credits/{customer}', [CreditController::class, 'show'])->name('credits.show');
        Route::post('/credits/{credit}/pay', [CreditController::class, 'pay'])->name('credits.pay');

        // Pedidos
        Route::get('/orders', [PosOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [PosOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [PosOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [PosOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/receipt', [PosOrderController::class, 'receipt'])->name('orders.receipt');
        Route::get('/orders/{order}/final-receipt', [PosOrderController::class, 'finalReceipt'])->name('orders.final-receipt');
        Route::post('/orders/{order}/payment', [PosOrderController::class, 'addPayment'])->name('orders.payment');
        Route::post('/orders/{order}/deliver', [PosOrderController::class, 'deliver'])->name('orders.deliver');
        Route::post('/orders/{order}/cancel', [PosOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/add-store-item', [PosOrderController::class, 'addStoreItem'])->name('orders.add-store-item');
    });

    // ══════════════════════════════════════════════════════════
    // REPORTES
    // ══════════════════════════════════════════════════════════
    Route::prefix('reports')->name('reports.')->middleware('role:admin|gerente|caja')->group(function () {

        Route::get('/cash', [CashReportController::class, 'index'])->name('cash.index');
        Route::get('/cash/day/{date}', [CashReportController::class, 'day'])->name('cash.day');
        Route::get('/cash/register/{register}', [CashReportController::class, 'show'])->name('cash.show');
        Route::get('/cash/sale/{sale}', [CashReportController::class, 'showSale'])->name('cash.sale');

        // Exportaciones
        Route::get('/cash/register/{register}/excel', [CashReportController::class, 'exportExcel'])->name('cash.export-excel');
        Route::get('/cash/register/{register}/pdf', [CashReportController::class, 'exportPdf'])->name('cash.export-pdf');
        Route::get('/cash/day/{date}/excel', [CashReportController::class, 'exportDayExcel'])->name('cash.export-day-excel');
        Route::get('/cash/day/{date}/pdf', [CashReportController::class, 'exportDayPdf'])->name('cash.export-day-pdf');
    });

    // ═══ STOCK TIENDA ═══
    Route::prefix('stock')->name('stock.')->middleware(['auth'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Stock\StockController::class, 'index'])->name('index');
        Route::put('/price/{variant}', [\App\Http\Controllers\Stock\StockController::class, 'updatePrice'])->name('update-price');
        Route::post('/add-stock', [\App\Http\Controllers\Stock\StockController::class, 'addStock'])->name('add-stock');
        Route::get('/variants', [\App\Http\Controllers\Stock\StockController::class, 'variants'])->name('variants');
        Route::get('/packages/{variant}', [\App\Http\Controllers\Stock\StockController::class, 'packages'])->name('packages');
        Route::get('/print', [\App\Http\Controllers\Stock\StockController::class, 'printReport'])->name('print');
    });

    // ══════════════════════════════════════════════════════════
    // ADMINISTRACIÓN
    // ══════════════════════════════════════════════════════════
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {

        // Categorías
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggleActive'])->name('categories.toggle');

        // Productos
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggleActive'])->name('products.toggle');
        Route::put('/products/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.update-variant');
        Route::post('/products/{product}/variants', [ProductController::class, 'addVariant'])->name('products.add-variant');
        Route::delete('/products/{product}/image', [ProductController::class, 'deleteImage'])->name('products.delete-image');

        // Clientes
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/toggle', [CustomerController::class, 'toggleActive'])->name('customers.toggle');
        Route::patch('/customers/{customer}/toggle-credit', [CustomerController::class, 'toggleCreditBlock'])->name('customers.toggle-credit');

        // Usuarios
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::put('/roles/{role}/permissions', [UserController::class, 'updateRolePermissions'])->name('roles.update-permissions');
    });
});

require __DIR__.'/auth.php';
