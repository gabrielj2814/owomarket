<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Tenant\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Tenant\Infrastructure\Http\Services\ApiGateway;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ViewModuleTenantSuspendedIndexGETController extends Controller {


    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index(Request $request) {

        $user_uuid=$request->user_uuid;
        $uuid=Uuid::make($user_uuid);
        $ConsultAuthUserApiByUuid= new ConsultAuthUserApiByUuidUseCase($this->apiGateway->auth());
        $usuario=$ConsultAuthUserApiByUuid->execute($uuid);

        $type=null;
        $title=null;
        $message=null;
        if($request->has("type") && $request->has("message") && $request->has("title")){
            $type=$request->query("type");
            $title=$request->query("title");
            $message=$request->query("message");
        }


        return Inertia::render(
            component: 'admin/modules/tenants_suspended/IndexPage',
            props: [
                'title'      => 'Modulo Tenant Suspended - OwOMarket',
                'user_id'    => $usuario->getUserId()->value(),
                'type'       => $type,
                'titleToast' => $title,
                'message'    => $message,
            ]
        );


    }




}







?>
