<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Src\Authentication\Application\UseCase\LogoutApiUserUseCase;
use Src\Authentication\Infrastructure\Eloquent\Repositories\PersonalAccessTokenRepository;
use Src\Shared\Helper\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LogoutApiGETController extends Controller {

    public function index(Request $request): JsonResponse{

        $token= request()->bearerToken();

        $repository= new PersonalAccessTokenRepository();

        $useCase = new LogoutApiUserUseCase($repository);

        $useCase->execute($token);


        return ApiResponse::success(message: 'Logout exitoso', code:200);
    }


}


?>
