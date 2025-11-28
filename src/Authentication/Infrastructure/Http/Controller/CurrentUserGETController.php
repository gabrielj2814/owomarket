<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Src\Authentication\Application\UseCase\ConsultarAuthUserByUuidUseCase;
use Src\Authentication\Domain\ValueObjects\Uuid;
use Src\Authentication\Infrastructure\Eloquent\Repositories\AuthUserRepository;
use Src\Shared\Helper\ApiResponse;

class CurrentUserGETController extends Controller {


    public function index(Request $request):JsonResponse {
        $uuid= Uuid::make($request->user_uuid);
        $authUserRepository= new AuthUserRepository();
        $consultarAuthUserByUuidUseCase= new ConsultarAuthUserByUuidUseCase($authUserRepository);
        $authUser=$consultarAuthUserByUuidUseCase->execute($uuid);


        return ApiResponse::success(
            data: [
                'user_id' => $authUser->getUserId()->value(),
                'user_name' => $authUser->getName()->value(),
                'user_email' => $authUser->getEmail()->value(),
                'user_type' => $authUser->getType()->value(),
                'user_avatar' => $authUser->getAvatar()->value(),
            ],
            message: 'Usuario actual obtenido con éxito',
            code: 200
        );
    }


}



?>
