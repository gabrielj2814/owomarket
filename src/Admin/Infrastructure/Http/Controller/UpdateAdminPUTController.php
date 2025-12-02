<?php



namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Application\UseCase\UpdateAdminUseCase;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Shared\Helper\ApiResponse;

class UpdateAdminPUTController extends Controller {




    public function index(Request $request): JsonResponse{
        $uuid= Uuid::make($request->id);

        $repository= new AdminRepository();
        $consultAdminByUuidUseCase= new ConsultAdminByUuidUseCase($repository);
        $admin=$consultAdminByUuidUseCase->execute($uuid);

        if(!$admin){
            return ApiResponse::error("El administrador no fue encontrador", 404);
        }

        $updateUseCase= new UpdateAdminUseCase($repository);

        $adminUpdated= $updateUseCase->execute($request->id, $request->name,$request->email, $request->phone);

        $dataResponse= [
            "id" =>            $adminUpdated->getId()->value(),
            "name" =>          $adminUpdated->getName()->value(),
            "email" =>         $adminUpdated->getEmail()->value(),
            "phone" =>         $adminUpdated->getPhone()->value(),
            "type" =>          $adminUpdated->getType()->value(),
            "updated_at" =>    $adminUpdated->getUpdatedAt()->value(),
        ];

        return ApiResponse::success(data: $dataResponse, message: "OK", code: 200);

    }



}


?>
