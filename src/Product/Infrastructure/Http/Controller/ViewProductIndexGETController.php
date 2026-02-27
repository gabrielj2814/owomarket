<?php


namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Product\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Product\Domain\ValueObjects\Uuid;
use Src\Product\Infrastructure\Http\Services\ApiGateway;

class ViewProductIndexGETController extends Controller {

    public function __construct(
        protected ApiGateway $apiGateway
    ){}

    public function index(Request $request){

        $fullUrl = request()->getSchemeAndHttpHost();
        $user_uuid=$request->user_uuid;
        $uuid=Uuid::make($user_uuid);

        $ConsultAuthUserApiByUuid= new ConsultAuthUserApiByUuidUseCase($this->apiGateway->authTenant());
        $usuario=$ConsultAuthUserApiByUuid->execute($uuid,$fullUrl);

        // ViewProductIndexGetController
        // $type=null;
        // $title=null;
        // $message=null;
        // if($request->has("type") && $request->has("message") && $request->has("title")){
        //     $type=$request->query("type");
        //     $title=$request->query("title");
        //     $message=$request->query("message");
        // }

        $host= $request->getHost();


        return Inertia::render(
            component: 'tenant/modules/product/ProductIndexPage',
            props: [
                'title'      => 'Module Product - OwOMarket',
                'user_id'    => $usuario->getUserId()->value(),
                'host'       => $host,
                'user_name'  => $usuario->getName()->value(),
            ]
        );

    }



}


?>
