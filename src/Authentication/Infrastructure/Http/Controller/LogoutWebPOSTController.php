<?php

namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\LogoutWebUseCase;
use Src\Authentication\Infrastructure\Eloquent\Repositories\LoginWebRepository;
use Src\Authentication\Infrastructure\Services\ApiGateway;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class LogoutWebPOSTController extends Controller{


    public function __construct(protected ApiGateway $api){}

    public function index():JsonResponse {

        $loginWebRepository= new LoginWebRepository();

        $useCase= new LogoutWebUseCase(
            $loginWebRepository
        );

        $useCase->execute();

        return ApiResponse::success(message: 'Cierre de sesión exitoso', code:200);
    }


}


?>
