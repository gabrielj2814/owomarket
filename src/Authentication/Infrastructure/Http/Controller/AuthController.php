<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;
use Src\Authentication\Infrastructure\Http\Request\LoginFormRequest;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Authentication\Infrastructure\Services\AuthServices;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends Controller
{
    //

    public function __construct(protected ApiGateway $api){}

    public function helpCheck(): string
    {
        return ApiResponse::success(message: 'El servicio de autenticación está funcionando correctamente', code:200);
    }


    // public function login(LoginFormRequest $request): JsonResponse
    // {
    //     $credentials = $request->data;


    //     return ApiResponse::success(message: 'Inicio de sesión exitoso', code:200);

    // }

    public function loginApi(LoginFormRequest $request): JsonResponse{

        $credentials = $request->data;

        $respuestaApiUser=$this->api->users()->consultUserByEmail($credentials->email);

        if($respuestaApiUser["code"]==404){
            return ApiResponse::error(message: $respuestaApiUser["message"], code:$respuestaApiUser["code"]);
        }

        $userRepository= new UserRepository();

        $authServices= new AuthServices($userRepository);

        $email=UserEmail::make($respuestaApiUser["data"]["email"]);

        $authServices->consultUserByEmail($email);



        return ApiResponse::success(data: $credentials->toArray(), message: 'Inicio de sesión exitoso', code:200);

    }

    // public function logout(): JsonResponse
    // {
    //     // $this->auth->logout();

    //     // return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);
    // }



    // public function logoutApi(Request $request): JsonResponse{

    //     // $token = request()->bearerToken();

    //     // $this->auth->logoutApi($token);

    //     // return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);

    // }





}



?>
