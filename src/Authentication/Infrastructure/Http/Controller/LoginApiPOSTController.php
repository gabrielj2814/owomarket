<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\ConsultDataUserByEmailCase;
use Src\Authentication\Application\UseCase\LoginApiUserUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Http\Request\LoginFormRequest;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginApiPOSTController extends Controller {


    public function __construct(
        protected ApiGateway $api,
        protected LoginApiUserUseCase $login_api_user_use_case,
        protected ConsultDataUserByEmailCase $consult_data_user_by_email_case,
        ){}


    public function index(LoginFormRequest $request): JsonResponse{

        $credentials = $request->data;

        $consultarExistenciaUsuarioApi=$this->api->users()->consultUserByEmail($credentials->email);

        if($consultarExistenciaUsuarioApi['code']!=200){
            return ApiResponse::error(message: 'El usuario no econtrado', code:401);
        }

        $email=UserEmail::make($credentials->email);

        $token=$this->login_api_user_use_case->execute(
            $email,
            $credentials->password
        );

        if($token==null){
            return ApiResponse::error(message: 'Credenciales inválidas', code:401);
        }

        $dataUser=$this->consult_data_user_by_email_case->execute(
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
