<?php



namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Admin\Application\UseCase\ConsultAdminByEmailUseCase;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Application\UseCase\UpdateAdminUseCase;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Admin\Infrastructure\Http\Request\UpdateAdminFormRequest;
use Src\Shared\Helper\ApiResponse;

class UpdateAdminPUTController extends Controller {




    public function index(UpdateAdminFormRequest $request): JsonResponse{
        $data=$request->data;

        $uuid= Uuid::make($data->id);
        $email=UserEmail::make($data->email);

        $repository= new AdminRepository();

        $consultAdminByUuidUseCase= new ConsultAdminByUuidUseCase($repository);
        $admin=$consultAdminByUuidUseCase->execute($uuid);

        if(!$admin){
            return ApiResponse::error("El administrador no fue encontrador", 404);
        }

        $consultAdminByEmailUseCase=new ConsultAdminByEmailUseCase($repository);
        $adminValidate=$consultAdminByEmailUseCase->execute($email);

        if($adminValidate){
            if($admin->getId()->value() != $adminValidate->getId()->value()){
                return ApiResponse::error("El administrador no fue encontrador", 422,[
                    "email" => ["El correo ingresado ya esta en uso por otro usuario"]
               ]);
            }
        }

        $updateUseCase= new UpdateAdminUseCase($repository);

        $adminUpdated= $updateUseCase->execute($data->id, $data->name, $data->email, $data->phone);;

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
