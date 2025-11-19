<?php


namespace Src\Authentication\Infrastructure\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Src\Authentication\Application\Contracts\UserServices;
use Src\Authentication\Domain\Entities\User;

class UserApiClient extends BaseApiClient implements UserServices {

    public function consultUserByEmail(string $email): array {
        $body= [
            "email" => $email
        ];
        try {
            $endpoint="/api/user/consulta-usuario-por-email";
            $data = $this->post($endpoint,$body);
            if(env("APP_ENV")=="local"){
                Log::info(" Ok ");
                Log::info(__METHOD__." Endpoint => ".config("app.url").$endpoint);
                Log::info("body ".json_encode($body));
                Log::info("response ".json_encode($data));
                Log::info(" ");
            }
            return $data;
        } catch (RequestException $error) {
            if(env("APP_ENV")=="local"){
                Log::info(" ERROR ");
                Log::info(__METHOD__." Endpoint => ".config("app.url").$endpoint);
                Log::info("body ".json_encode($body));
                Log::info(" ");
            }
            return $error->response->json();
        }
    }
}


?>
