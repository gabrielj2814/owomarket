<?php

namespace Src\Admin\Infrastructure\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Admin\Application\Contracts\AuthServices;
use Src\Admin\Domain\ValueObjects\Uuid;

class AuthApiClient extends BaseApiClient implements AuthServices
{
    /**
     * Método consultAuthUserByUuid.
     */
    public function consultAuthUserByUuid(Uuid $uuid): array
    {
        $endpoint = '/api/auth/interna/user/'.$uuid->value();
        try {

            $data = $this->get($endpoint);
            if (app()->environment('local')) {
                Log::info(' Ok ');
                Log::info(__METHOD__.' Endpoint => '.config('app.url').$endpoint);
                Log::info('response '.json_encode($data));
                Log::info(' ');
            }

            return $data;
        } catch (RequestException $error) {
            if (app()->environment('local')) {
                Log::info(' ERROR ');
                Log::info(__METHOD__.' Endpoint => '.config('app.url').$endpoint);
                Log::info(' ');
            }

            return $error->response->json();
        }
    }
}
