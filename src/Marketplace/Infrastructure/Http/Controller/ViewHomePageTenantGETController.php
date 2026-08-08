<?php


namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;

class ViewHomePageTenantGETController extends Controller {


    public function index() {
        $host=request()->getHost();
        $uri=request()->getRequestUri();

        return inertia()->render('marketplace/home/tenantHomePage',[
            'domain' => $host,
            'uri' => $uri,
        ]);
    }




}


?>
