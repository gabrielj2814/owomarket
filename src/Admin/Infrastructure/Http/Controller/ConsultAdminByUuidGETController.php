<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Shared\Helper\ApiResponse;

class ConsultAdminByUuidGETController extends Controller {



    public function index(Request $request):JsonResponse {
        $uuid= Uuid::make($request->uuid);

        $repository= new AdminRepository();
        $useCase= new ConsultAdminByUuidUseCase($repository);
        $admin=$useCase->execute($uuid);

        if(!$admin){
            return ApiResponse::error("El administrador no fue encontrador", 404);
        }

        $dataResponse= [
            "id" =>             $admin->getId()->value(),
            "name" =>           $admin->getName()->value(),
            "email" =>          $admin->getEmail()->value(),
            "phone" =>          $admin->getPhone()?->value(),
            "type" =>           $admin->getType()->value(),
            "avatar" =>         $admin->getAvatar()?->value(),
            "is_active" =>      $admin->isActive(),
            "created_at" =>     $admin->getCreatedAt()->value(),
            "updated_at" =>     $admin->getUpdatedAt()->value(),
        ];

        return ApiResponse::success(data: $dataResponse, message: "OK", code: 200);

    }




}



?>
