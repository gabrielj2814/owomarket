<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Tenant\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Tenant\Domain\ValuesObjects\Uuid;
use Src\Tenant\Infrastructure\Http\Services\ApiGateway;

class ViewDashboardTenantGETController extends Controller {


    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index(Request $request) {
        $fullUrl = request()->getSchemeAndHttpHost();
        $user_uuid=$request->user_uuid;
        $uuid=Uuid::make($user_uuid);
        $ConsultAuthUserApiByUuid= new ConsultAuthUserApiByUuidUseCase($this->apiGateway->authTenant());
        $usuario=$ConsultAuthUserApiByUuid->execute($uuid,$fullUrl);

        $type=null;
        $title=null;
        $message=null;
        if($request->has("type") && $request->has("message") && $request->has("title")){
            $type=$request->query("type");
            $title=$request->query("title");
            $message=$request->query("message");
        }

        $host= $request->getHost();


        return Inertia::render(
            component: 'tenant/dashboard/TenantDashboardTenantPage',
            props: [
                'title'      => 'Tenant Dashboard - OwOMarket',
                'user_id'    => $usuario->getUserId()->value(),
                'host'       => $host,
                'user_name'  => $usuario->getName()->value(),
            ]
        );


    }




}


?>
