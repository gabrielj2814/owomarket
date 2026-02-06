<?php


namespace Src\Tenant\Infrastructure\Http\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Tenant\Application\Contracts\AuthServices;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class AuthCentralApiClient extends BaseApiClient implements AuthServices {


    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl=""): array {
        try {

            $endpoint="/api/auth/interna/user/".$uuid->value();

            $data = $this->get($endpoint,[],$baseUrl);
            if(env("APP_ENV")=="local"){
                Log::info(" Ok ");
                Log::info(__METHOD__." Endpoint => ".$baseUrl.$endpoint);;
                Log::info("response ".json_encode($data));
                Log::info(" ");
            }
            return $data;
        } catch (RequestException $error) {
            if(env("APP_ENV")=="local"){
                Log::info(" ERROR ");
                Log::info(__METHOD__." Endpoint => ".$baseUrl.$endpoint);
                Log::info(" ");
            }
            return $error->response->json();
        }
    }

}


?>
