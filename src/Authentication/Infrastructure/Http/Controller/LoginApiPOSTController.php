<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\ConsultDataUserByEmailCase;
use Src\Authentication\Application\UseCase\LoginApiUserUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\PersonalAccessTokenRepository;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;
use Src\Authentication\Infrastructure\Http\Request\LoginFormRequest;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginApiPOSTController extends Controller {


    public function __construct(protected ApiGateway $api){}


    public function index(LoginFormRequest $request): JsonResponse{

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



}


?>
