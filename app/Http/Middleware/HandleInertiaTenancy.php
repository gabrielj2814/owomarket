<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaTenancy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
        // Solo aplicar en contexto de tenant
        if (tenancy()->initialized) {
            $tenant = tenant();

            // Compartir datos del tenant con Inertia
            Inertia::share([
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    // otros datos del tenant que necesites
                ],
                'current_domain' => $request->getHost(),
            ]);

            // Configurar asset URL para Vite
            if (app()->environment('local')) {
                Inertia::setRootView('tenant-app');
            }
        }

        return $next($request);
    }
}
