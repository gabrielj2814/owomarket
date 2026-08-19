<?php

namespace Src\Tenant\Infrastructure\Http\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Tenant\Application\Contracts\AuthServices;
use Src\Tenant\Domain\ValueObjects\Uuid;

class AuthTenantApiClient extends BaseApiClient implements AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl = ''): array
    {
        try {

            $endpoint = '/api-tenant/auth/interna/user/'.$uuid->value();

            $data = $this->get($endpoint, [], $baseUrl);
            if (app()->environment('local')) {
                Log::info(' Ok ');
                Log::info(__METHOD__.' Endpoint => '.$baseUrl.$endpoint);
                Log::info('response '.json_encode($data));
                Log::info(' ');
            }

            return $data;
        } catch (RequestException $error) {
            if (app()->environment('local')) {
                Log::info(' ERROR ');
                Log::info(__METHOD__.' Endpoint => '.$baseUrl.$endpoint);
                Log::info(' ');
            }

            return $error->response->json();
        }
    }
}
