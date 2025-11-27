<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\LoginWebUserUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;
use Src\Authentication\Infrastructure\Http\Request\LoginFormRequest;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginWebPOSTController extends Controller{


    public function __construct(protected ApiGateway $api){}


    public function index(LoginFormRequest $request):JsonResponse {
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


}


?>
