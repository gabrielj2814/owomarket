<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class LoginTenantScreenGETController extends Controller{


    public function index() {
        $host=request()->getHost();

        return Inertia::render('auth/LoginTenantPage',[
            'domain' => $host,
        ]);
    }


}


?>
