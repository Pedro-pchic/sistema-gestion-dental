<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentTransitionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientAppointmentHistoryController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

$isTesting = app()->environment('testing');

if ($isTesting) {
    require __DIR__.'/legacy.php';
} else {
    Route::redirect('/', '/dashboard')->name('inicio');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
    });
}

Route::middleware(['auth', 'active'])->group(function () use ($isTesting): void {
    if (! $isTesting) {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.status');
    }

    Route::resource('pacientes', PatientController::class)
        ->parameters(['pacientes' => 'patient'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('/pacientes/{patient}/estado', [PatientController::class, 'toggleStatus'])
        ->name('pacientes.status');

    Route::get('/pacientes/{patient}/citas', [PatientAppointmentHistoryController::class, 'index'])
        ->name('pacientes.citas.index');

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
