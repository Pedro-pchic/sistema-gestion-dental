<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentReminderController;
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\AppointmentTransitionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DentalFinancialReportController;
use App\Http\Controllers\DentalInvoiceController;
use App\Http\Controllers\DentalPaymentController;
use App\Http\Controllers\DentistPublicProfileController;
use App\Http\Controllers\PatientAppointmentHistoryController;
use App\Http\Controllers\PatientClinicalHistoryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientTreatmentController;
use App\Http\Controllers\PublicAppointmentRequestController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PublicSiteSettingController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

$isTesting = app()->environment('testing');

Route::get('/clinica', PublicSiteController::class)->name('public.home');
Route::get('/clinica/horarios', [PublicAppointmentRequestController::class, 'slots'])
    ->middleware('throttle:60,1')->name('public.slots');
Route::post('/clinica/citas', [PublicAppointmentRequestController::class, 'store'])
    ->middleware('throttle:5,1')->name('public.appointments.store');

if ($isTesting) {
    require __DIR__.'/legacy.php';
} else {
    Route::get('/', PublicSiteController::class)->name('inicio');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
    });
}

Route::middleware(['auth', 'active'])->group(function () use ($isTesting): void {
    Route::get('/solicitudes-cita', [AppointmentRequestController::class, 'index'])->name('solicitudes-cita.index');
    Route::get('/solicitudes-cita/{appointmentRequest}', [AppointmentRequestController::class, 'show'])->name('solicitudes-cita.show');
    Route::post('/solicitudes-cita/{appointmentRequest}/confirmar', [AppointmentRequestController::class, 'confirm'])->name('solicitudes-cita.confirm');
    Route::post('/solicitudes-cita/{appointmentRequest}/rechazar', [AppointmentRequestController::class, 'reject'])->name('solicitudes-cita.reject');

    Route::resource('anuncios', AnnouncementController::class)->parameters(['anuncios' => 'announcement'])->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('/anuncios/{announcement}/publicacion', [AnnouncementController::class, 'toggle'])->name('anuncios.toggle');
    Route::get('/configuracion-publica', [PublicSiteSettingController::class, 'edit'])->name('configuracion-publica.edit');
    Route::put('/configuracion-publica', [PublicSiteSettingController::class, 'update'])->name('configuracion-publica.update');
    Route::get('/odontologos-publicos', [DentistPublicProfileController::class, 'index'])->name('odontologos-publicos.index');
    Route::put('/odontologos-publicos/{dentist}', [DentistPublicProfileController::class, 'update'])->name('odontologos-publicos.update');
    Route::get('/recordatorios', AppointmentReminderController::class)
        ->middleware('can:view-appointment-reminders')
        ->name('recordatorios.index');

    if (! $isTesting) {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)
            ->middleware('can:view-dental-dashboard')
            ->name('dashboard');

        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.status');
    }

    Route::middleware('can:view-dental-finances')
        ->prefix('facturacion')
        ->name('facturacion.')
        ->group(function (): void {
            Route::get('/facturas', [DentalInvoiceController::class, 'index'])->name('facturas.index');
            Route::get('/facturas/crear', [DentalInvoiceController::class, 'create'])->name('facturas.create');
            Route::post('/facturas', [DentalInvoiceController::class, 'store'])->name('facturas.store');
            Route::get('/facturas/{dentalInvoice}', [DentalInvoiceController::class, 'show'])->name('facturas.show');
            Route::post('/facturas/{dentalInvoice}/emitir', [DentalInvoiceController::class, 'issue'])->name('facturas.issue');
            Route::post('/facturas/{dentalInvoice}/anular', [DentalInvoiceController::class, 'cancel'])->name('facturas.cancel');
            Route::get('/facturas/{dentalInvoice}/comprobante', [DentalInvoiceController::class, 'document'])->name('facturas.document');

            Route::get('/pagos', [DentalPaymentController::class, 'index'])->name('pagos.index');
            Route::get('/pagos/{dentalPayment}', [DentalPaymentController::class, 'show'])->name('pagos.show');
            Route::get('/pagos/{dentalPayment}/recibo', [DentalPaymentController::class, 'receipt'])->name('pagos.receipt');
            Route::post('/facturas/{dentalInvoice}/pagos', [DentalPaymentController::class, 'store'])->name('pagos.store');
            Route::post('/pagos/{dentalPayment}/anular', [DentalPaymentController::class, 'cancel'])->name('pagos.cancel');

            Route::get('/reportes', DentalFinancialReportController::class)
                ->middleware('can:view-dental-reports')
                ->name('reportes.index');
        });

    Route::get('/pacientes/{patient}/resumen-financiero', [DentalInvoiceController::class, 'patientSummary'])
        ->can('viewFinancialSummary', 'patient')
        ->name('pacientes.resumen-financiero');

    Route::resource('pacientes', PatientController::class)
        ->parameters(['pacientes' => 'patient'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('/pacientes/{patient}/estado', [PatientController::class, 'toggleStatus'])
        ->name('pacientes.status');

    Route::get('/pacientes/{patient}/citas', [PatientAppointmentHistoryController::class, 'index'])
        ->name('pacientes.citas.index');
    Route::get('/pacientes/{patient}/historial-clinico', [PatientClinicalHistoryController::class, 'show'])
        ->name('pacientes.historial-clinico');

    Route::get('/pacientes/{patient}/tratamientos/crear', [PatientTreatmentController::class, 'create'])
        ->name('pacientes.tratamientos.create');
    Route::post('/pacientes/{patient}/tratamientos', [PatientTreatmentController::class, 'store'])
        ->name('pacientes.tratamientos.store');
    Route::get('/pacientes/{patient}/tratamientos/{patientTreatment}/editar', [PatientTreatmentController::class, 'edit'])
        ->scopeBindings()
        ->name('pacientes.tratamientos.edit');
    Route::put('/pacientes/{patient}/tratamientos/{patientTreatment}', [PatientTreatmentController::class, 'update'])
        ->scopeBindings()
        ->name('pacientes.tratamientos.update');

    Route::get('/pacientes/{patient}/planes', [TreatmentPlanController::class, 'index'])
        ->name('pacientes.planes.index');
    Route::get('/pacientes/{patient}/planes/crear', [TreatmentPlanController::class, 'create'])
        ->name('pacientes.planes.create');
    Route::post('/pacientes/{patient}/planes', [TreatmentPlanController::class, 'store'])
        ->name('pacientes.planes.store');
    Route::get('/pacientes/{patient}/planes/{treatmentPlan}', [TreatmentPlanController::class, 'show'])
        ->scopeBindings()
        ->name('pacientes.planes.show');
    Route::patch('/pacientes/{patient}/planes/{treatmentPlan}/estado', [TreatmentPlanController::class, 'update'])
        ->scopeBindings()
        ->name('pacientes.planes.update');
    Route::patch('/pacientes/{patient}/planes/{treatmentPlan}/partidas/{treatmentPlanItem}', [TreatmentPlanController::class, 'updateItem'])
        ->scopeBindings()
        ->name('pacientes.planes.items.update');

    Route::resource('tratamientos', TreatmentController::class)
        ->parameters(['tratamientos' => 'treatment'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('/tratamientos/{treatment}/estado', [TreatmentController::class, 'toggleStatus'])
        ->name('tratamientos.status');

    Route::resource('citas', AppointmentController::class)
        ->parameters(['citas' => 'appointment'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::post('/citas/{appointment}/confirmar', [AppointmentTransitionController::class, 'confirm'])
        ->name('citas.confirm');
    Route::post('/citas/{appointment}/iniciar', [AppointmentTransitionController::class, 'start'])
        ->name('citas.start');
    Route::post('/citas/{appointment}/completar', [AppointmentTransitionController::class, 'complete'])
        ->name('citas.complete');
    Route::post('/citas/{appointment}/cancelar', [AppointmentTransitionController::class, 'cancel'])
        ->name('citas.cancel');
    Route::post('/citas/{appointment}/no-asistio', [AppointmentTransitionController::class, 'markNoShow'])
        ->name('citas.no-show');
});
