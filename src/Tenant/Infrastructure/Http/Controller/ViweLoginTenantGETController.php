<?php


namespace Src\Tenant\Infrastructure\Http\Controllerl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Tenant\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Tenant\Domain\ValuesObjects\Uuid;
use Src\Tenant\Infrastructure\Http\Services\ApiGateway;

class ViweDashboardTenantGETController extends Controller {




    public function index(Request $request) {


        return Inertia::render(
            component: 'auth/LoginTenantPage',
        );


    }




}


?>
