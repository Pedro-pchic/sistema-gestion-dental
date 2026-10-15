<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AuditLog;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\PublicSiteSetting;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        AuditLog::record($this->module($model), 'created', $model, metadata: $this->safeMetadata($model));
    }

    public function updated(Model $model): void
    {
        $changedFields = array_values(array_intersect(array_keys($model->getChanges()), $this->allowedFields($model)));
        if ($changedFields === []) {
            return;
        }

        $action = match (true) {
            $model instanceof User && in_array('role_id', $changedFields, true) => 'role_changed',
            $model instanceof User && in_array('is_active', $changedFields, true) => 'status_changed',
            $model instanceof Appointment && in_array('status', $changedFields, true) => strtolower((string) $model->status->value),
            $model instanceof Appointment && in_array('starts_at', $changedFields, true) => 'rescheduled',
            $model instanceof AppointmentRequest && in_array('status', $changedFields, true) => (string) $model->status,
            $model instanceof DentalInvoice && in_array('status', $changedFields, true) => $model->status->value,
            $model instanceof DentalPayment && in_array('status', $changedFields, true) => $model->status->value,
            $model instanceof Announcement && in_array('is_published', $changedFields, true) => $model->is_published ? 'published' : 'unpublished',
            in_array('is_active', $changedFields, true) => 'status_changed',
            default => 'updated',
        };

        $metadata = ['changed_fields' => $changedFields] + $this->safeMetadata($model);
        if ($model instanceof User && in_array('role_id', $changedFields, true)) {
            $metadata['role_id_before'] = $model->getOriginal('role_id');
            $metadata['role_id_after'] = $model->role_id;
        }
        if (in_array('is_active', $changedFields, true)) {
            $metadata['is_active_before'] = (bool) $model->getOriginal('is_active');
            $metadata['is_active_after'] = (bool) $model->is_active;
        }
        if (in_array('status', $changedFields, true)) {
            $metadata['status_before'] = $model->getRawOriginal('status');
            $metadata['status_after'] = $model->getAttributes()['status'];
        }

        AuditLog::record($this->module($model), $action, $model, metadata: $metadata);
    }

    public function deleted(Model $model): void
    {
        AuditLog::record($this->module($model), 'deleted', $model);
    }

    private function module(Model $model): string
    {
        return match ($model::class) {
            User::class => 'security',
            Patient::class => 'patients',
            Appointment::class => 'appointments',
            AppointmentRequest::class => 'appointment_requests',
            PatientTreatment::class, TreatmentPlan::class, TreatmentPlanItem::class => 'clinical_records',
            DentalInvoice::class, DentalPayment::class => 'finances',
            Announcement::class, PublicSiteSetting::class, Dentist::class, Treatment::class => 'public_site',
        };
    }

    /** @return array<int, string> */
    private function allowedFields(Model $model): array
    {
        return match ($model::class) {
            User::class => ['role_id', 'is_active', 'can_view_clinical_records', 'password'],
            Patient::class => ['first_name', 'last_name', 'date_of_birth', 'phone', 'address', 'email', 'is_active', 'email_reminders_enabled', 'sms_reminders_enabled'],
            Appointment::class => ['patient_id', 'dentist_id', 'starts_at', 'ends_at', 'status'],
            AppointmentRequest::class => ['status', 'patient_id', 'appointment_id', 'reviewed_by'],
            PatientTreatment::class => ['patient_id', 'dentist_id', 'treatment_id', 'performed_at', 'procedure_description', 'clinical_notes', 'price_applied'],
            TreatmentPlan::class => ['patient_id', 'dentist_id', 'title', 'description', 'status'],
            TreatmentPlanItem::class => ['treatment_id', 'quantity', 'estimated_unit_price', 'status', 'sort_order'],
            DentalInvoice::class => ['status', 'total', 'balance_due', 'issued_by', 'cancelled_by'],
            DentalPayment::class => ['status', 'amount', 'cancelled_by'],
            Announcement::class => ['title', 'description', 'is_published', 'published_on', 'expires_on'],
            PublicSiteSetting::class => ['clinic_name', 'description', 'phone', 'email', 'address', 'opening_hours', 'booking_hours', 'booking_weekdays', 'booking_starts_at', 'booking_ends_at', 'hero_image_path', 'logo_path'],
            Dentist::class => ['is_active', 'is_public', 'public_bio', 'public_photo_path', 'specialty'],
            Treatment::class => ['name', 'is_active', 'is_public'],
        };
    }

    /** @return array<string, scalar|null> */
    private function safeMetadata(Model $model): array
    {
        return match ($model::class) {
            AppointmentRequest::class => ['appointment_id' => $model->appointment_id, 'patient_id' => $model->patient_id],
            DentalInvoice::class => ['status' => $model->status?->value, 'total' => $model->total],
            DentalPayment::class => ['status' => $model->status?->value, 'amount' => $model->amount, 'invoice_id' => $model->dental_invoice_id],
            default => [],
        };
    }
}
