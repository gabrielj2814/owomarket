<?php


namespace Src\Authentication\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Src\Authentication\Application\Contracts\UserServices;

class UserApiClient extends BaseApiClient implements UserServices {
    // TODO: hacer que responda con una entity de usuario con los datos que se usaran
    public function consultUserByEmail(string $email):void {
        $data= [
            "email" => $email
        ];
        $data = $this->post("/api/user/consulta-usuario-por-email",$data);
        Log::info("Respuesta");
        Log::info($data);
        // return new OrderUserDTO($data['id'], $data['email'], $data['name']);
    }
}


?>
