<?php


namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ViewDashboardAdminGETController extends Controller{


    public function index(Request $request) {
        //
        return Inertia::render(
            component: 'admin/dashboard/AdminDashboardPage',
            props: [
                'title' => 'Dashboard Admin - OwOMarket',
            ]
        );
    }



}


?>
