<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListStaffRolesAndPermissionsUseCase;

final class ViewAdminRolesAndStaffPageGETController extends Controller
{
    public function __construct(
        private readonly ListStaffRolesAndPermissionsUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute();

        return Inertia::render('admin/security/AdminRolesAndStaffPage', [
            'title' => 'Roles, Permisos RBAC & Staff - OwOMarket',
            'user_id' => $user_uuid,
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
            'staff_users' => $result['staff_users'],
            'metrics' => $result['metrics'],
        ]);
    }
}
