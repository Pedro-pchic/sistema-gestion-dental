<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogRequestPerformance;
use App\Models\AuditLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [LogRequestPerformance::class], append: [HandleInertiaRequests::class]);

        $middleware->alias([
            'active' => EnsureActiveUser::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request): void {
            if ($request->user() !== null && $request->route()?->getName() !== null) {
                AuditLog::record('security', 'access_denied', result: 'rejected', metadata: ['route_name' => $request->route()->getName()]);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request): void {
            if (in_array($request->route()?->getName(), ['citas.store', 'citas.update', 'solicitudes-cita.confirm'], true)
                && in_array('El odontólogo ya tiene una cita en ese horario.', $exception->errors()['starts_at'] ?? [], true)) {
                AuditLog::record('appointments', 'conflict_rejected', result: 'rejected', metadata: ['route_name' => $request->route()->getName()]);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
