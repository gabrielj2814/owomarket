<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Src\Admin\Application\UseCase\CreateAdminUseCase;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Admin\Infrastructure\Http\Request\CreateAdminFormRequest;
use Src\Shared\Helper\ApiResponse;

class CreateAdminPOSTController extends Controller {


    /**
     * Reglas de validación para la contraseña
     */
    private $rules = [
        'mayúscula' => '/[A-Z]/',                      // Al menos una letra mayúscula
        'minúscula' => '/[a-z]/',                      // Al menos una letra minúscula
        'número' => '/[0-9]/',                         // Al menos un número
        'carácter especial' => '/[!@#$%^&*()\-_=+{};:,<.>]/' // Al menos un carácter especial
    ];



    public function index(CreateAdminFormRequest $request): JsonResponse {
        $data=$request->data;

        $name=$data->name;
        $email=$data->email;
        $phone=$data->phone;
        $password=null;

        if(env("APP_ENV")=="local"){
            $password=env("USER_PASSWORD_DEV");
        }
        else{
            $password=$this->generarContrasena(12);
        }

        $repository= new AdminRepository();
        $createAdminUseCase= new CreateAdminUseCase($repository);


        $admin=$createAdminUseCase->execute($name,$email,$phone,$password);

        $dataRespose=[
            "id"          => $admin->getId()->value(),
            "name"        => $admin->getName()->value(),
            "email"       => $admin->getEmail()->value(),
            "type"        => $admin->getType()->value(),
            "phone"       => $admin->getPhone()->value(),
            "created_at"  => $admin->getCreatedAt()->value()->format("Y-m-d"),
        ];

        return ApiResponse::success(data: $dataRespose, message: "ok", code: 200);

    }

    /**
     * Genera una contraseña segura con las reglas especificadas
     *
     * @param int $length Longitud de la contraseña (8-72)
     * @return string Contraseña generada
     * @throws \InvalidArgumentException Si la longitud no está en el rango válido
     */
    private function generarContrasena(int $length = 12): string
    {
        // Validar que la longitud esté en el rango permitido
        if ($length < 8 || $length > 72) {
            throw new \InvalidArgumentException('La longitud debe estar entre 8 y 72 caracteres.');
        }

        // Conjuntos de caracteres
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()-_=+{};:,<.>';

        // Combinar todos los caracteres
        $allChars = $uppercase . $lowercase . $numbers . $specialChars;
        $allCharsLength = strlen($allChars);

        // Inicializar la contraseña
        $password = '';

        // Asegurar al menos un carácter de cada tipo
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];

        // Completar la longitud restante con caracteres aleatorios
        $remainingLength = $length - 4;
        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $allChars[random_int(0, $allCharsLength - 1)];
        }

        // Mezclar la contraseña para que los caracteres obligatorios no estén siempre al inicio
        $password = str_shuffle($password);

        // Validar que la contraseña generada cumple todas las reglas
        if (!$this->validarContrasena($password)) {
            // En caso raro de que no cumpla, regenerar recursivamente
            return $this->generarContrasena($length);
        }

        return $password;
    }

    /**
     * Valida si una contraseña cumple con todas las reglas
     *
     * @param string $password Contraseña a validar
     * @return bool True si cumple todas las reglas
     */
    public function validarContrasena(string $password): bool
    {
        foreach ($this->rules as $rule => $pattern) {
            if (!preg_match($pattern, $password)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Genera múltiples contraseñas
     *
     * @param int $count Cantidad de contraseñas a generar
     * @param int $length Longitud de cada contraseña
     * @return array Array de contraseñas generadas
     */
    public function generarMultiplesContrasenas(int $count = 5, int $length = 12): array
    {
        $passwords = [];

        for ($i = 0; $i < $count; $i++) {
            $passwords[] = $this->generarContrasena($length);
        }

        return $passwords;
    }



}



?>
