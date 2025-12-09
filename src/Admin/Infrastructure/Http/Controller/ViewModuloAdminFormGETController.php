<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Admin\Application\UseCase\ConsultAdminByUuidUseCase;
use Src\Admin\Application\UseCase\ConsultAuthUserApiByUuid;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Eloquent\Repositories\AdminRepository;
use Src\Admin\Infrastructure\Services\ApiGateway;

class ViewModuloAdminFormGETController extends Controller {



    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index(Request $request) {

        $user_uuid=$request->user_uuid;
        $uuid=Uuid::make($user_uuid);
        $ConsultAuthUserApiByUuid= new ConsultAuthUserApiByUuid($this->apiGateway->auth());
        $usuario=$ConsultAuthUserApiByUuid->execute($uuid);

        $titulo='Modulo Admins  - OwOMarket';
        $record_id=null;

        if($request->record_id){
            $record_id=Uuid::make($request->record_id);
            $repository= new AdminRepository();
            $consultAdminByUuidUseCase=new ConsultAdminByUuidUseCase($repository);
            $admin=$consultAdminByUuidUseCase->execute($record_id);
            if(!$admin){
                return redirect(route("central.backoffice.web.admin.module.admin",
                [
                    "user_uuid" => $user_uuid,
                    "type"    => "failure",
                    "title"   => "Error",
                    "message" => "Error: No se puedo cargar el formulario de edición por que que el admintrador seleccionado no existe en la base de datos",
                ]));
            }
        }


        return Inertia::render(
            component: 'admin/modules/admins/FormPage',
            props: [
                'title' => $titulo,
                'user_id' => $usuario->getUserId()->value(),
                'record_id' => ($record_id!=null)?$record_id->value():null
            ]
        );

    }





}



?>
