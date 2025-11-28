<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Admin\Application\UseCase\ConsultAuthUserApiByUuid;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Services\ApiGateway;

class ViewDashboardAdminGETController extends Controller{


    // public function

    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index(Request $request) {

        $user_uuid=$request->user_uuid;
        $uuid=Uuid::make($user_uuid);
        $ConsultAuthUserApiByUuid= new ConsultAuthUserApiByUuid($this->apiGateway->auth());
        $usuario=$ConsultAuthUserApiByUuid->execute($uuid);

        return Inertia::render(
            component: 'admin/dashboard/AdminDashboardPage',
            props: [
                'title' => 'Dashboard Admin - OwOMarket',
                'user_id' => $usuario->getUserId()->value(),
                'user_name' => $usuario->getName()->value(),
                'user_email' => $usuario->getEmail()->value(),
                'user_type' => $usuario->getType()->value(),
                'user_avatar' => $usuario->getAvatar()->value(),
            ]
        );
    }



}


?>
