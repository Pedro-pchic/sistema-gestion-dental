<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;

class PermissionCatalog
{
    public const array GROUPS = [
        'Panel' => ['dashboard.view' => 'Ver panel'],
        'Pacientes' => ['patients.view' => 'Ver pacientes', 'patients.create' => 'Crear pacientes', 'patients.update' => 'Actualizar pacientes', 'patients.deactivate' => 'Desactivar pacientes', 'patients.assign' => 'Asignar odontólogos'],
        'Citas' => ['appointments.view' => 'Ver citas', 'appointments.create' => 'Crear citas', 'appointments.update' => 'Reprogramar citas', 'appointments.confirm' => 'Confirmar citas', 'appointments.cancel' => 'Cancelar citas', 'appointments.progress' => 'Atender citas'],
        'Solicitudes web' => ['appointment_requests.view' => 'Ver solicitudes', 'appointment_requests.confirm' => 'Confirmar solicitudes', 'appointment_requests.reject' => 'Rechazar solicitudes'],
        'Tratamientos' => ['treatments.view' => 'Ver tratamientos', 'treatments.manage' => 'Administrar tratamientos'],
        'Expedientes clínicos' => ['clinical_records.view' => 'Ver expedientes', 'clinical_records.update' => 'Registrar y actualizar expedientes'],
        'Facturación' => ['invoices.view' => 'Ver facturas', 'invoices.create' => 'Crear facturas', 'invoices.issue' => 'Emitir facturas', 'invoices.cancel' => 'Anular facturas'],
        'Pagos' => ['payments.view' => 'Ver pagos', 'payments.create' => 'Registrar pagos', 'payments.cancel' => 'Anular pagos'],
        'Reportes' => ['reports.financial' => 'Ver reportes financieros'],
        'Sitio público' => ['announcements.manage' => 'Administrar anuncios', 'public_site.manage' => 'Administrar sitio público', 'dentists_public.manage' => 'Administrar perfiles públicos'],
        'Administración' => ['users.manage' => 'Administrar usuarios', 'roles.view' => 'Ver roles y permisos', 'roles.manage' => 'Modificar permisos', 'audit.view' => 'Consultar auditoría'],
        'Recordatorios' => ['reminders.view' => 'Ver recordatorios'],
    ];

    public const array REQUIRED_ADMIN = ['users.manage', 'roles.view', 'roles.manage'];

    public const array ADMIN_ONLY = ['users.manage', 'roles.view', 'roles.manage', 'audit.view'];

    /** @return array<int, string> */
    public static function names(): array
    {
        return array_keys(array_merge(...array_values(self::GROUPS)));
    }

    /** @return array<int, string> */
    public static function defaults(string $role): array
    {
        if ($role === Role::ADMINISTRATOR) {
            return array_values(array_diff(self::names(), ['clinical_records.update']));
        }

        if ($role === Role::RECEPTIONIST) {
            return ['dashboard.view', 'patients.view', 'patients.create', 'patients.update', 'patients.deactivate', 'appointments.view', 'appointments.create', 'appointments.update', 'appointments.confirm', 'appointments.cancel', 'appointment_requests.view', 'appointment_requests.confirm', 'appointment_requests.reject', 'treatments.view', 'invoices.view', 'invoices.create', 'invoices.issue', 'payments.view', 'payments.create', 'reminders.view'];
        }

        if ($role === Role::DENTIST) {
            return ['dashboard.view', 'patients.view', 'appointments.view', 'appointments.progress', 'treatments.view', 'clinical_records.view', 'clinical_records.update'];
        }

        return [];
    }

    /** @param array<int, mixed> $arguments */
    public static function requiredFor(string $ability, array $arguments): ?string
    {
        $direct = ['view-dental-dashboard' => 'dashboard.view', 'view-appointment-reminders' => 'reminders.view', 'view-dental-reports' => 'reports.financial', 'manage-users' => 'users.manage'];
        if (isset($direct[$ability])) {
            return $direct[$ability];
        }

        $subject = $arguments[0] ?? null;
        $type = is_object($subject) ? $subject::class : $subject;

        return match ($type) {
            Patient::class => match ($ability) {
                'viewAny', 'view' => 'patients.view',
                'create' => 'patients.create',
                'update' => 'patients.update',
                'delete' => 'patients.deactivate',
                'assignDentists' => 'patients.assign',
                'viewClinicalHistory' => 'clinical_records.view',
                'createClinicalRecord' => 'clinical_records.update',
                default => null,
            },
            Appointment::class => match ($ability) {
                'viewAny', 'view' => 'appointments.view',
                'create' => 'appointments.create',
                'reschedule' => 'appointments.update',
                'confirm' => 'appointments.confirm',
                'cancel', 'markNoShow' => 'appointments.cancel',
                'start', 'complete' => 'appointments.progress',
                default => null,
            },
            Treatment::class => match ($ability) {
                'viewAny', 'view' => 'treatments.view',
                'create', 'update', 'toggleStatus' => 'treatments.manage',
                default => null,
            },
            PatientTreatment::class, TreatmentPlan::class => match ($ability) {
                'viewAny', 'view' => 'clinical_records.view',
                'create', 'update' => 'clinical_records.update',
                default => null,
            },
            DentalInvoice::class => match ($ability) {
                'viewAny', 'view' => 'invoices.view',
                'create' => 'invoices.create',
                'issue' => 'invoices.issue',
                'cancel' => 'invoices.cancel',
                'recordPayment' => 'payments.create',
                default => null,
            },
            DentalPayment::class => match ($ability) {
                'viewAny', 'view' => 'payments.view',
                'cancel' => 'payments.cancel',
                default => null,
            },
            default => null,
        };
    }
}
