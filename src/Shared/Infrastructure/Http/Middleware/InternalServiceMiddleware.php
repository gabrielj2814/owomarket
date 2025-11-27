<?php

namespace Src\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class InternalServiceMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $serviceName = $request->header('X-Internal-Service');
        $serviceSecret = $request->header('X-Internal-Secret');

        if (!$this->isValidInternalRequest($serviceName, $serviceSecret)) {
            return ApiResponse::error("Unauthorized internal request",403);
        }

        return $next($request);
    }

    private function isValidInternalRequest($serviceName, $secret): bool
    {
        $validSecret = config("services.internal.secret");
        $allowedServices = config("services.internal.allowed_services");

        return in_array($serviceName, $allowedServices) &&
               hash_equals($validSecret, $secret);
    }
}
