<?php

namespace Src\Authentication\Infrastructure\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Authentication\Application\Contracts\TenantServices;
use Src\Authentication\Application\Contracts\UserServices;

class TenantApiCentralClient extends BaseApiClient implements TenantServices {

    public function consultTenantLoginIsActive(string $slug): array {
        try {
            $endpoint="/api/tenant/interna/consulta-tenant-login-activo/".$slug;
            $headers = [];
            $data = $this->get($endpoint,$headers, env("TENANT_API_HOST"));
            if(env("APP_ENV")=="local"){
                Log::info(" Ok ");
                Log::info(__METHOD__." Endpoint => ".env("TENANT_API_HOST").$endpoint);
                Log::info("slug ".json_encode($slug));
                Log::info("response ".json_encode($data));
                Log::info(" ");
            }
            return $data;
        } catch (RequestException $error) {
            if(env("APP_ENV")=="local"){
                Log::info(" ERROR ");
                Log::info(__METHOD__." Endpoint => ".env("TENANT_API_HOST").$endpoint);
                Log::info("slug ".json_encode($slug));
                Log::info(" ");
            }
            return $error->response->json();
        }
    }
}




?>
