<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Admin\Infrastructure\Services\ApiGateway;

class ViewDashboardAdminGETController extends Controller{


    // public function

    public function __construct(
        protected ApiGateway $apiGateway
    ){}


    public function index(Request $request) {
        //
        // $user=$this->apiGateway->auth()->getCurrentUser();
        // dd($user);

        return Inertia::render(
            component: 'admin/dashboard/AdminDashboardPage',
            props: [
                'title' => 'Dashboard Admin - OwOMarket',
                // 'user' => $user,
            ]
        );
    }



}


?>
