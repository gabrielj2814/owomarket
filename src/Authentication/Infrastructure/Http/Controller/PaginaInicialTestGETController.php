<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaginaInicialTestGETController extends Controller {



    public function index() {
          return Inertia::render('InicialPage',[
            'user' => Auth::user(),
        ]);
    }


}



?>
