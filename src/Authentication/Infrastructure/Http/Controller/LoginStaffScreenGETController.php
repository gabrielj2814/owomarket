<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class LoginStaffScreenGETController extends Controller{


    public function index() {
        $host=request()->getHost();

        return Inertia::render('auth/LoginStaff',[
            'domain' => $host,
        ]);
    }


}


?>
