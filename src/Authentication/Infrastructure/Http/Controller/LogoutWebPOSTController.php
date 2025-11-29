<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Authentication\Application\UseCase\LogoutWebUseCase;
use Src\Authentication\Domain\ValueObjects\Uuid;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LogoutWebPOSTController extends Controller{


    public function __construct(protected ApiGateway $api){}

    public function index(Request $request):JsonResponse {
        $uuid=Uuid::make($request->uuid);

        $loginWebRepository= new LoginWebRepository();

        $useCase= new LogoutWebUseCase(
            $loginWebRepository
        );

        $respuesta=$useCase->execute($uuid);
        if(!$respuesta){
            return ApiResponse::error(message:"Error al hacer logout", code: 500);
        }

        return ApiResponse::success(data: $respuesta,message: 'Cierre de sesión exitoso', code:200);
    }


}


?>
