<?php


namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;

class ViewHomePageCentralGETController extends Controller {


    public function index() {
        $host=request()->getHost();

        return inertia()->render('marketplace/home/centralHomePage',[
            'domain' => $host,
        ]);
    }




}


?>
