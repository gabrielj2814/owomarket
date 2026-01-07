<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ViewCreateAccountTenantGETController extends Controller {



    public function index(){

        return Inertia::render(
            component: 'signup/CreateAccountTenantPage',
        );
    }




}


?>
