<?php

namespace Src\User\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\User\Application\Data\EmailUserData;
use Src\User\Application\UseCase\ConsultUserByEmailUseCase;
use Src\User\Infrastructure\Eloquent\Repositories\UserRepository;

class ConsultUserByEmailPOSTController extends Controller{



    public function index(Request $request):JsonResponse{

        $userRepository=new UserRepository();

        $consultUseCase=new ConsultUserByEmailUseCase($userRepository);

        $dto=new EmailUserData($request->email);

        $respuesta = $consultUseCase->execute($dto);

        return new JsonResponse([
            "id" => $respuesta?->getId()->value(),
            "email" => $respuesta?->getEmail()->value(),
        ]);

    }




}

?>
