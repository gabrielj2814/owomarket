<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Src\Authentication\Infrastructure\Services\ApiGateway;

class LoginTenantScreenGETController extends Controller{

    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index() {
        $centralDomain=config("app.url");
        $host=request()->getHost();
        $slug=explode(".",$host)[0];
        $data=$this->apiGateway->tenants()->consultTenantLoginIsActive($slug, $centralDomain);
        if($data["data"]==false){
             return Inertia::render('auth/TenantInactivePage',[
                'domain' => $host,
            ]);
        }
        return Inertia::render('auth/LoginTenantPage',[
            'domain' => $host,
        ]);

    }


}


?>
