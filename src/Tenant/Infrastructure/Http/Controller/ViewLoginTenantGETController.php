<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ViewLoginTenantGETController extends Controller
{
    /**
     * Método index.
     */
    public function index(Request $request)
    {

        return Inertia::render(
            component: 'auth/LoginTenantPage',
        );

    }
}
