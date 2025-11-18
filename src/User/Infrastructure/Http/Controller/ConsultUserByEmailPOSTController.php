<?php

namespace Src\User\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Shared\Helper\ApiResponse;
use Src\User\Application\Data\EmailUserData;
use Src\User\Application\UseCase\ConsultUserByEmailUseCase;
use Src\User\Infrastructure\Eloquent\Repositories\UserRepository;

class ConsultUserByEmailPOSTController extends Controller{



    public function index(Request $request):JsonResponse{

        $userRepository=new UserRepository();

        $consultUseCase=new ConsultUserByEmailUseCase($userRepository);

        $dto=new EmailUserData($request->email);

        $respuesta = $consultUseCase->execute($dto);

        if(!$respuesta){
            return ApiResponse::error("El usuario no fue encontrado",404);
        }

        $data=[
            "id" => $respuesta?->getId()->value(),
            "name" => $respuesta?->getName()->value(),
            "email" => $respuesta?->getEmail()->value(),
            // "password" => $respuesta?->getPassword()->getHash(),
            "type" => $respuesta?->getType()->value(),
            "has_phone" => $respuesta?->hasPhone(),
            "has_avatar" => $respuesta?->hasAvatar(),
            "is_active" => $respuesta?->isActive(),
            "createdAt" => $respuesta?->getCreatedAt()->value(),
            "updatedAt" => $respuesta?->getUpdatedAt()->value(),
        ];

        return ApiResponse::success($data,"usuario consultado",200);

    }




}

?>
