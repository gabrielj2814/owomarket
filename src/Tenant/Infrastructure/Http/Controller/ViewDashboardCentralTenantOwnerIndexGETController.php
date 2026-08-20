<?php

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Tenant\Infrastructure\Http\Services\ApiGateway;

class ViewDashboardCentralTenantOwnerIndexGETController extends Controller
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected ApiGateway $apiGateway
    ) {}

    /**
     * Método index.
     */
    public function index(Request $request)
    {
        $user_uuid = (string) ($request->user_uuid ?: auth()->id());

        $type = null;
        $title = null;
        $message = null;
        if ($request->has('type') && $request->has('message') && $request->has('title')) {
            $type = $request->query('type');
            $title = $request->query('title');
            $message = $request->query('message');
        }

        return Inertia::render(
            component: 'tenant/dashboard/TenantOwnerDashboardCentralPage',
            props: [
                'title' => 'Dashboard Central Tenant Owner - OwOMarket',
                'user_id' => $user_uuid,
                'type' => $type,
                'titleToast' => $title,
                'message' => $message,
            ]
        );
    }
}
