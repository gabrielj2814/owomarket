<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\LaravelData\Attributes\Validation\In;
use Src\Authentication\Application\UseCase\ConsultDataUserByEmailCase;
use Src\Authentication\Application\UseCase\LoginApiUserUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\PersonalAccessTokenRepository;
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

    public function LoginStaffScreen() {
        $host=request()->getHost();

        // dd($host, tenancy()->initialized);

        return Inertia::render('auth/LoginStaff',[
            'domain' => $host,
        ]);
    }

    // public function login(LoginFormRequest $request): JsonResponse
    // {
    //     $credentials = $request->data;


    //     return ApiResponse::success(message: 'Inicio de sesión exitoso', code:200);

    // }

    public function loginApi(LoginFormRequest $request): JsonResponse{

        $credentials = $request->data;

        $userRepository= new UserRepository();
        $personalTokenRepository= new PersonalAccessTokenRepository();

        $LoginApiUserUseCase= new LoginApiUserUseCase(
            $userRepository,
            $personalTokenRepository
        );

        $email=UserEmail::make($credentials->email);

        $token=$LoginApiUserUseCase->execute(
            $email,
            $credentials->password
        );

        if($token==null){
            return ApiResponse::error(message: 'Credenciales inválidas', code:401);
        }

        $ConsultarDataUserByEmailCase= new ConsultDataUserByEmailCase(
            $userRepository
        );

        $dataUser=$ConsultarDataUserByEmailCase->execute(
            $email
        );

        $data=[
            'token'=>$token,
            "user_type" => $dataUser->getType()->value()
        ];

        return ApiResponse::success(data: $data, message: 'Inicio de sesión exitoso', code:200);

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
