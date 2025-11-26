<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\LaravelData\Attributes\Validation\In;
use Src\Authentication\Application\UseCase\ConsultDataUserByEmailCase;
use Src\Authentication\Application\UseCase\LoginApiUserUseCase;
use Src\Authentication\Application\UseCase\LoginWebUserUseCase;
use Src\Authentication\Application\UseCase\LogoutWebUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
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

        return Inertia::render('auth/LoginStaff',[
            'domain' => $host,
        ]);
    }

    public function InicialPageScreen() {

        return Inertia::render('InicialPage',[
            'user' => Auth::user(),
        ]);

    }

    public function loginWeb(LoginFormRequest $request):JsonResponse {
        $credentials = $request->data;

        $consultarExistenciaUsuarioApi=$this->api->users()->consultUserByEmail($credentials->email);

        if($consultarExistenciaUsuarioApi['code']!=200){
            return ApiResponse::error(message: 'El usuario no econtrado', code:401);
        }


        $loginWebRepository= new LoginWebRepository();
        $userRepository= new UserRepository();
        $loginWeb= new LoginWebUserUseCase($loginWebRepository,$userRepository);

        $success=$loginWeb->execute(
            UserEmail::make($credentials->email),
            $credentials->password
        );

        if(!$success){
            return ApiResponse::error(message: 'Credenciales inválidas', code:401);
        }

        $respuesta=[
            'rol' => $consultarExistenciaUsuarioApi['data']['type'],
            "user_name" => $consultarExistenciaUsuarioApi['data']['name'],
        ];

        return ApiResponse::success(data:$respuesta, message: 'Inicio de sesión exitoso', code:200);
    }

    public function logoutWeb(): JsonResponse
    {
        $loginWebRepository= new LoginWebRepository();

        $useCase= new LogoutWebUseCase(
            $loginWebRepository
        );

        $useCase->execute();

        return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);
    }

    public function loginApi(LoginFormRequest $request): JsonResponse{

        $credentials = $request->data;

        $consultarExistenciaUsuarioApi=$this->api->users()->consultUserByEmail($credentials->email);

        if($consultarExistenciaUsuarioApi['code']!=200){
            return ApiResponse::error(message: 'El usuario no econtrado', code:401);
        }


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

    // public function logoutApi(Request $request): JsonResponse{

    //     // $token = request()->bearerToken();

    //     // $this->auth->logoutApi($token);

    //     // return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);

    // }

}

?>
