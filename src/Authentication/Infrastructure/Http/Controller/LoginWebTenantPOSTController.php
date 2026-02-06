<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\ConsultUserApiByEmailUseCase;
use Src\Authentication\Application\UseCase\CrearAuthUserUseCase;
use Src\Authentication\Application\UseCase\EliminarAuthUserByUuidUseCase;
use Src\Authentication\Application\UseCase\LoginWebUserUseCase;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Http\Request\LoginFormRequest;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginWebTenantPOSTController extends Controller{


    public function __construct(
        protected ApiGateway $api,
        protected LoginWebUserUseCase $login_web_user_use_case,
        protected CrearAuthUserUseCase $crear_auth_user_use_case,
        protected EliminarAuthUserByUuidUseCase $eliminar_auth_user_by_uuid_use_case
        ){}


    public function index(LoginFormRequest $request):JsonResponse {
        $fullUrl = request()->getSchemeAndHttpHost();
        $credentials = $request->data;
        $email=UserEmail::make($credentials->email);

        $consultaUsuarioApiPorEmail=new ConsultUserApiByEmailUseCase($this->api->usersTenants());
        $usuario=$consultaUsuarioApiPorEmail->execute($email, $fullUrl);

        if(!$usuario){
            return ApiResponse::error(message: 'El usuario no encontrado', code:401);
        }

        $success=$this->login_web_user_use_case->execute(
            $email,
            $credentials->password
        );

        if(!$success){
            return ApiResponse::error(message: 'Credenciales inválidas', code:401);
        }

        $this->eliminar_auth_user_by_uuid_use_case->execute($usuario->getId());

        $this->crear_auth_user_use_case->execute($usuario);




        $respuesta=[
            'rol' => $usuario->getType()->value(),
            'uuid' => $usuario->getId()->value(),
            "user_name" => $usuario->getName()->value(),
        ];

        return ApiResponse::success(data:$respuesta, message: 'Inicio de sesión exitoso', code:200);
    }


}


?>
