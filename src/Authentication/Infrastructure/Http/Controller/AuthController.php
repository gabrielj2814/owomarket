<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
// use App\Modules\Core\Auth\Contracts\Auth;
// use App\Modules\Core\Auth\Request\LoginFormRequest;
// use App\Modules\Core\Helpers\ApiResponse;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Http\Request;

class AuthController extends Controller
{
    //

    public function helpCheck(): string
    {
        return "ok";
        // return ApiResponse::success(message: 'El servicio de autenticación está funcionando correctamente', code:200);
    }


    // public function login(LoginFormRequest $request): JsonResponse
    // {
    //     // $credentials = $request->data;

    //     // if(!$this->auth->login($credentials)){
    //     //     return ApiResponse::success(message: 'Credenciales invalidas', code:401);
    //     // }

    //     // return ApiResponse::success(message: 'Inicio de sesión exitoso', code:200);

    // }

    // public function logout(): JsonResponse
    // {
    //     // $this->auth->logout();

    //     // return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);
    // }

    // public function loginApi(LoginFormRequest $request): JsonResponse{

    //     // $token=$this->auth->loginApi($request->data);

    //     // return ApiResponse::success(data: ['token'=>$token], message: 'Inicio de sesión exitoso', code:200);

    // }

    // public function logoutApi(Request $request): JsonResponse{

    //     // $token = request()->bearerToken();

    //     // $this->auth->logoutApi($token);

    //     // return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);

    // }





}



?>
