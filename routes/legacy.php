<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerInquiryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManagementReportController;
use App\Http\Controllers\MarketingAnalyticsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderLogisticsController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('inicio');
Route::view('/nosotros', 'portal.about')->name('nosotros');
Route::get('/contacto', [CustomerInquiryController::class, 'create'])->name('contacto.create');
Route::post('/contacto', [CustomerInquiryController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contacto.store');
Route::get('/seguimiento', [OrderTrackingController::class, 'create'])->name('seguimiento.create');
Route::post('/seguimiento', [OrderTrackingController::class, 'show'])
    ->middleware('throttle:order-tracking')
    ->name('seguimiento.show');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalogo.index');
Route::get('/catalogo/{product}', [CatalogController::class, 'show'])->name('catalogo.show');

Route::get('/carrito', [CartController::class, 'index'])->name('carrito.index');
Route::post('/carrito/{product}', [CartController::class, 'store'])->name('carrito.store');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->name('carrito.update');
Route::delete('/carrito/{product}', [CartController::class, 'destroy'])->name('carrito.destroy');
Route::delete('/carrito', [CartController::class, 'clear'])->name('carrito.clear');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:checkout')
    ->name('checkout.store');
Route::get('/pedido/{order}/confirmacion', [CheckoutController::class, 'show'])
    ->middleware('signed')
    ->name('checkout.confirmation');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:view-management-dashboard')
        ->prefix('control-gestion')
        ->name('gestion.')
        ->group(function () {
            Route::get('/reportes', [ManagementReportController::class, 'index'])->name('reportes.index');
        });

    Route::middleware('can:manage-logistics')
        ->prefix('operaciones/logistica')
        ->name('operaciones.logistica.')
        ->group(function () {
            Route::get('/', [OrderLogisticsController::class, 'index'])->name('index');
            Route::get('/pedidos/{order}', [OrderLogisticsController::class, 'show'])->name('show');
            Route::post('/pedidos/{order}/avanzar', [OrderLogisticsController::class, 'advance'])->name('advance');
            Route::patch('/pedidos/{order}/entrega-estimada', [OrderLogisticsController::class, 'estimate'])
                ->name('estimate');
        });

    Route::middleware('can:view-invoices')->group(function () {
        Route::get('/facturas', [InvoiceController::class, 'index'])->name('facturas.index');
        Route::get('/facturas/{invoice}', [InvoiceController::class, 'show'])->name('facturas.show');
        Route::get('/facturas/{invoice}/imprimir', [InvoiceController::class, 'document'])->name('facturas.print');
    });

    Route::post('/ventas/{sale}/factura', [InvoiceController::class, 'store'])
        ->middleware('can:issue-invoices')
        ->name('ventas.factura.store');

    Route::middleware('can:manage-finances')->group(function () {
        Route::get('/finanzas', [FinanceController::class, 'index'])->name('finanzas.index');
        Route::get('/finanzas/ingresos', [IncomeController::class, 'index'])->name('finanzas.ingresos.index');
        Route::get('/finanzas/ingresos/crear', [IncomeController::class, 'create'])->name('finanzas.ingresos.create');
        Route::post('/finanzas/ingresos', [IncomeController::class, 'store'])->name('finanzas.ingresos.store');
        Route::get('/finanzas/gastos', [ExpenseController::class, 'index'])->name('finanzas.gastos.index');
        Route::get('/finanzas/gastos/crear', [ExpenseController::class, 'create'])->name('finanzas.gastos.create');
        Route::post('/finanzas/gastos', [ExpenseController::class, 'store'])->name('finanzas.gastos.store');

        Route::post('/facturas/{invoice}/cancelar', [InvoiceController::class, 'cancel'])->name('facturas.cancel');

        Route::get('/pagos', [PaymentController::class, 'index'])->name('pagos.index');
        Route::get('/pagos/{payment}', [PaymentController::class, 'show'])->name('pagos.show');
        Route::get('/pagos/{payment}/recibo', [PaymentController::class, 'receipt'])->name('pagos.receipt');
        Route::post('/facturas/{invoice}/pagos', [PaymentController::class, 'store'])->name('facturas.pagos.store');

        Route::get('/notas-credito', [CreditNoteController::class, 'index'])->name('notas-credito.index');
        Route::get('/notas-credito/{creditNote}', [CreditNoteController::class, 'show'])->name('notas-credito.show');
        Route::get('/notas-credito/{creditNote}/imprimir', [CreditNoteController::class, 'document'])
            ->name('notas-credito.print');
        Route::post('/notas-credito/{creditNote}/cancelar', [CreditNoteController::class, 'cancel'])
            ->name('notas-credito.cancel');
        Route::post('/facturas/{invoice}/notas-credito', [CreditNoteController::class, 'store'])
            ->name('facturas.notas-credito.store');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.status');
    });

    Route::middleware('can:manage-employees')->group(function () {
        Route::resource('empleados', EmployeeController::class)
            ->parameters(['empleados' => 'employee'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/empleados/{employee}/estado', [EmployeeController::class, 'toggleStatus'])->name('empleados.status');

        Route::resource('departamentos', DepartmentController::class)
            ->parameters(['departamentos' => 'department'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/departamentos/{department}/estado', [DepartmentController::class, 'toggleStatus'])->name('departamentos.status');

        Route::resource('puestos', PositionController::class)
            ->parameters(['puestos' => 'position'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/puestos/{position}/estado', [PositionController::class, 'toggleStatus'])->name('puestos.status');
    });

    Route::middleware('can:manage-purchases')->group(function () {
        Route::resource('proveedores', SupplierController::class)
            ->parameters(['proveedores' => 'supplier'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/proveedores/{supplier}/estado', [SupplierController::class, 'toggleStatus'])
            ->name('proveedores.status');

        Route::get('/compras/ordenes/crear', [PurchaseController::class, 'create'])->name('compras.create');
        Route::post('/compras/ordenes', [PurchaseController::class, 'store'])->name('compras.store');
        Route::get('/compras/ordenes/{purchase}/editar', [PurchaseController::class, 'edit'])->name('compras.edit');
        Route::put('/compras/ordenes/{purchase}', [PurchaseController::class, 'update'])->name('compras.update');
        Route::post('/compras/ordenes/{purchase}/enviar', [PurchaseController::class, 'submit'])->name('compras.submit');
        Route::post('/compras/ordenes/{purchase}/cancelar', [PurchaseController::class, 'cancel'])->name('compras.cancel');
    });

    Route::middleware('can:view-purchases')->group(function () {
        Route::get('/compras/ordenes', [PurchaseController::class, 'index'])->name('compras.index');
        Route::get('/compras/ordenes/{purchase}', [PurchaseController::class, 'show'])->name('compras.show');
    });

    Route::post('/compras/ordenes/{purchase}/recibir', [PurchaseController::class, 'receive'])
        ->middleware('can:receive-purchases')
        ->name('compras.receive');

    Route::middleware('can:manage-commercial')->group(function () {
        Route::get('/marketing/analitica', [MarketingAnalyticsController::class, 'index'])
            ->name('marketing.analytics.index');

        Route::get('/consultas', [CustomerInquiryController::class, 'index'])->name('consultas.index');
        Route::get('/consultas/{inquiry}', [CustomerInquiryController::class, 'show'])->name('consultas.show');
        Route::patch('/consultas/{inquiry}', [CustomerInquiryController::class, 'update'])->name('consultas.update');

        Route::resource('clientes', CustomerController::class)
            ->parameters(['clientes' => 'customer'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/clientes/{customer}/estado', [CustomerController::class, 'toggleStatus'])
            ->name('clientes.status');

        Route::get('/clientes/{customer}/direcciones/crear', [AddressController::class, 'create'])
            ->name('clientes.direcciones.create');
        Route::post('/clientes/{customer}/direcciones', [AddressController::class, 'store'])
            ->name('clientes.direcciones.store');
        Route::get('/clientes/{customer}/direcciones/{address}/editar', [AddressController::class, 'edit'])
            ->scopeBindings()
            ->name('clientes.direcciones.edit');
        Route::put('/clientes/{customer}/direcciones/{address}', [AddressController::class, 'update'])
            ->scopeBindings()
            ->name('clientes.direcciones.update');
        Route::delete('/clientes/{customer}/direcciones/{address}', [AddressController::class, 'destroy'])
            ->scopeBindings()
            ->name('clientes.direcciones.destroy');

        Route::get('/cotizaciones', [QuoteController::class, 'index'])->name('cotizaciones.index');
        Route::get('/cotizaciones/crear', [QuoteController::class, 'create'])->name('cotizaciones.create');
        Route::post('/cotizaciones', [QuoteController::class, 'store'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{quote}/editar', [QuoteController::class, 'edit'])->name('cotizaciones.edit');
        Route::put('/cotizaciones/{quote}', [QuoteController::class, 'update'])->name('cotizaciones.update');
        Route::post('/cotizaciones/{quote}/enviar', [QuoteController::class, 'send'])->name('cotizaciones.send');
        Route::post('/cotizaciones/{quote}/aceptar', [QuoteController::class, 'accept'])->name('cotizaciones.accept');
        Route::post('/cotizaciones/{quote}/rechazar', [QuoteController::class, 'reject'])->name('cotizaciones.reject');
        Route::post('/cotizaciones/{quote}/pedido', [OrderController::class, 'storeFromQuote'])
            ->name('cotizaciones.pedido.store');
        Route::get('/cotizaciones/{quote}', [QuoteController::class, 'show'])->name('cotizaciones.show');

        Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/crear', [OrderController::class, 'create'])->name('pedidos.create');
        Route::post('/pedidos', [OrderController::class, 'store'])->name('pedidos.store');
        Route::get('/pedidos/{order}/editar', [OrderController::class, 'edit'])->name('pedidos.edit');
        Route::put('/pedidos/{order}', [OrderController::class, 'update'])->name('pedidos.update');
        Route::post('/pedidos/{order}/confirmar', [OrderController::class, 'confirm'])->name('pedidos.confirm');
        Route::post('/pedidos/{order}/cancelar', [OrderController::class, 'cancel'])->name('pedidos.cancel');
        Route::post('/pedidos/{order}/venta', [OrderController::class, 'sell'])->name('pedidos.sale.store');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('pedidos.show');

        Route::get('/ventas', [SaleController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/{sale}', [SaleController::class, 'show'])->name('ventas.show');
    });

    Route::middleware('can:manage-catalog')->group(function () {
        Route::get('/inventario/existencias', [InventoryController::class, 'index'])
            ->name('inventario.existencias');
        Route::patch('/inventario/existencias/{inventory}/stock-minimo', [InventoryController::class, 'updateMinimumStock'])
            ->name('inventario.minimum-stock');
        Route::get('/inventario/movimientos', [InventoryController::class, 'movements'])
            ->name('inventario.movimientos.index');
        Route::post('/inventario/movimientos', [InventoryController::class, 'storeMovement'])
            ->name('inventario.movimientos.store');

        Route::resource('categorias', CategoryController::class)
            ->parameters(['categorias' => 'category'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/categorias/{category}/estado', [CategoryController::class, 'toggleStatus'])->name('categorias.status');

        Route::resource('productos', ProductController::class)
            ->parameters(['productos' => 'product'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::patch('/productos/{product}/estado', [ProductController::class, 'toggleStatus'])->name('productos.status');
    });
});
