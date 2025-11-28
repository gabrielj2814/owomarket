<?php


namespace Src\Admin\Infrastructure\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Admin\Application\Contracts\AuthServices;
use Src\Admin\Domain\ValueObjects\Uuid;

class AuthApiClient extends BaseApiClient implements AuthServices {

    public function consultAuthUserByUuid(Uuid $uuid): array {
        try {

            $endpoint="/api/auth/interna/user/".$uuid->value();

            $data = $this->get($endpoint);
            if(env("APP_ENV")=="local"){
                Log::info(" Ok ");
                Log::info(__METHOD__." Endpoint => ".config("app.url").$endpoint);;
                Log::info("response ".json_encode($data));
                Log::info(" ");
            }
            return $data;
        } catch (RequestException $error) {
            if(env("APP_ENV")=="local"){
                Log::info(" ERROR ");
                Log::info(__METHOD__." Endpoint => ".config("app.url").$endpoint);
                Log::info(" ");
            }
            return $error->response->json();
        }
    }

}


?>
