<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ConsultAuthUserApiByUuid;
use Src\Admin\Application\UseCase\GetAdminDashboardMetricsUseCase;
use Src\Admin\Infrastructure\Eloquent\Models\User;
use Src\Admin\Infrastructure\Services\ApiGateway;
use Src\Shared\Domain\ValueObjects\Uuid;

final class ViewDashboardAdminGETController extends Controller
{
    public function __construct(
        protected ApiGateway $apiGateway,
        protected GetAdminDashboardMetricsUseCase $metricsUseCase
    ) {}

    public function index(Request $request): Response
    {
        $user_uuid = (string) ($request->route('user_uuid') ?: $request->user_uuid);

        try {
            $uuid = Uuid::make($user_uuid);
            $consultAuthUserApiByUuid = new ConsultAuthUserApiByUuid($this->apiGateway->auth());
            $usuario = $consultAuthUserApiByUuid->execute($uuid);
            $userId = $usuario->getUserId()->value();
            $userName = $usuario->getName()->value();
            $userEmail = $usuario->getEmail()->value();
            $userType = $usuario->getType()->value();
            $userAvatar = $usuario->getAvatar()->value();
        } catch (\Throwable $e) {
            $user = auth()->user() ?: User::find($user_uuid);
            $userId = (string) ($user?->id ?? $user_uuid);
            $userName = (string) ($user?->name ?? 'Super Admin');
            $userEmail = (string) ($user?->email ?? '');
            $userType = (string) ($user?->type ?? 'super_admin');
            $userAvatar = (string) ($user?->avatar ?? 'https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg');
        }

        $dashboardData = $this->metricsUseCase->execute();

        return Inertia::render(
            component: 'admin/dashboard/AdminDashboardPage',
            props: [
                'title' => 'Dashboard Ejecutivo Super Admin - OwOMarket',
                'user_id' => $userId,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'user_type' => $userType,
                'user_avatar' => $userAvatar,
                'metrics' => $dashboardData['metrics'],
                'recent_activity' => $dashboardData['recent_activity'],
            ]
        );
    }
}
