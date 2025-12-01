<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Admin\Application\UseCase\ConsultAuthUserApiByUuid;
use Src\Admin\Domain\ValueObjects\Uuid;
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
            $record_id=$request->record_id;
            $titulo='Modulo Admins en modo edit  - OwOMarket';
        }


        return Inertia::render(
            component: 'admin/modules/admins/FormPage',
            props: [
                'title' => $titulo,
                'user_id' => $usuario->getUserId()->value(),
                'record_id' => $record_id
            ]
        );

    }





}



?>
