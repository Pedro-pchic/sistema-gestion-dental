<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Models\Appointment;
use App\Models\DentalInvoice;
use App\Models\DentalPayment;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\DentalInvoicePolicy;
use App\Policies\DentalPaymentPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PatientTreatmentPolicy;
use App\Policies\TreatmentPlanPolicy;
use App\Policies\TreatmentPolicy;
use App\Services\CloudinaryProductImageStorage;
use App\Services\DisabledSmsSender;
use App\Services\ProductImageStorage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductImageStorage::class, CloudinaryProductImageStorage::class);
        $this->app->bind(SmsSender::class, DisabledSmsSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/legacy'));
        }

        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(DentalInvoice::class, DentalInvoicePolicy::class);
        Gate::policy(DentalPayment::class, DentalPaymentPolicy::class);
        Gate::policy(Treatment::class, TreatmentPolicy::class);
        Gate::policy(PatientTreatment::class, PatientTreatmentPolicy::class);
        Gate::policy(TreatmentPlan::class, TreatmentPlanPolicy::class);

        Gate::define('manage-patient-assignments', fn (User $user): bool => $user->can(
            'assignDentists',
            Patient::class,
        ));

        Gate::define('view-management-dashboard', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::MANAGER],
            true,
        ));

        Gate::define('view-dental-dashboard', fn (User $user): bool => $user->is_active && in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::DENTIST, Role::RECEPTIONIST],
            true,
        ));
        Gate::define('view-appointment-reminders', fn (User $user): bool => $user->is_active && in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::RECEPTIONIST],
            true,
        ));

        Gate::define('manage-users', fn (User $user): bool => $user->role?->name === Role::ADMINISTRATOR
        );

        Gate::define('manage-employees', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::HUMAN_RESOURCES],
            true,
        ));

        Gate::define('manage-catalog', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::WAREHOUSE],
            true,
        ));

        Gate::define('manage-purchases', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::PURCHASING],
            true,
        ));

        Gate::define('manage-commercial', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::SALES],
            true,
        ));

        Gate::define('manage-logistics', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::WAREHOUSE],
            true,
        ));

        Gate::define('view-purchases', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::PURCHASING, Role::WAREHOUSE],
            true,
        ));

        Gate::define('receive-purchases', fn (User $user): bool => $user->can('view-purchases'));

        Gate::define('manage-finances', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::FINANCE],
            true,
        ));

        Gate::define('view-dental-finances', fn (User $user): bool => $user->is_active && in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::RECEPTIONIST],
            true,
        ));

        Gate::define('view-dental-reports', fn (User $user): bool => $user->is_active
            && $user->hasRole(Role::ADMINISTRATOR));

        Gate::define('view-invoices', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::SALES, Role::FINANCE],
            true,
        ));

        Gate::define('issue-invoices', fn (User $user): bool => in_array(
            $user->role?->name,
            [Role::ADMINISTRATOR, Role::SALES],
            true,
        ));

        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower($email) : '';

            return Limit::perMinute(5)
                ->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('checkout', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('contact', function (Request $request): Limit {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower($email) : '';

            return Limit::perMinute(5)
                ->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('order-tracking', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
    }
}
