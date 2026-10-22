<?php

namespace App\Http\Middleware;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'logo' => asset('logo.png'),
            ],
            'routes' => [
                'dashboard' => route('dashboard'),
                'logout' => route('logout'),
            ],
            'auth' => fn (): ?array => $request->user() ? [
                'name' => $request->user()->name,
                'role' => $request->user()->role?->name,
            ] : null,
            'navigation' => fn (): array => $this->navigation($request),
            'csrfToken' => fn (): string => $request->session()->token(),
            'flash' => fn (): array => [
                'status' => $request->session()->get('status'),
                'warning' => $request->session()->get('warning'),
            ],
        ];
    }

    /** @return array<int, array{label: string, href: string, section: string, inertia: bool}> */
    private function navigation(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $links = [];
        $add = static function (string $label, string $route, string $section, bool $inertia = false) use (&$links): void {
            $links[] = ['label' => $label, 'href' => route($route), 'section' => $section, 'inertia' => $inertia];
        };

        if ($user->can('view-dental-dashboard')) {
            $add('Dashboard', 'dashboard', 'Consultorio', true);
        }

        if ($user->can('viewAny', Patient::class)) {
            $add('Pacientes', 'pacientes.index', 'Atención');
        }

        if ($user->can('viewAny', Appointment::class)) {
            $add('Citas', 'citas.index', 'Atención');

            if ($user->can('appointment_requests.view')) {
                $add('Solicitudes web', 'solicitudes-cita.index', 'Atención');
            }

            if ($user->can('view-appointment-reminders')) {
                $add('Recordatorios', 'recordatorios.index', 'Atención');
            }
        }

        if ($user->can('viewAny', Treatment::class)) {
            $add('Tratamientos', 'tratamientos.index', 'Atención');
        }

        if ($user->can('view-dental-finances')) {
            $add('Facturas', 'facturacion.facturas.index', 'Facturación');
            $add('Pagos', 'facturacion.pagos.index', 'Facturación');

            if ($user->can('view-dental-reports')) {
                $add('Reportes', 'facturacion.reportes.index', 'Facturación');
            }
        }

        if ($user->can('manage-users')) {
            $add('Usuarios', 'usuarios.index', 'Administración');
        }

        if ($user->can('manage-announcements')) {
            $add('Anuncios', 'anuncios.index', 'Administración');
        }

        if ($user->can('manage-public-site')) {
            $add('Sitio público', 'configuracion-publica.edit', 'Administración', true);
        }

        if ($user->can('manage-dentists-public')) {
            $add('Equipo público', 'odontologos-publicos.index', 'Administración');
        }

        if ($user->can('roles.view')) {
            $add('Roles y permisos', 'roles.index', 'Seguridad');
        }

        if ($user->can('audit.view')) {
            $add('Auditoría', 'audit.index', 'Seguridad');
        }

        return $links;
    }
}
