<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\ValueObjects\Uuid;

class ViewAdminProfileGETController extends Controller
{
    private AdminRepositoryInterface $repository;

    public function __construct(AdminRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request, string $user_uuid): Response
    {
        $adminUuid = Uuid::make($user_uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (! $admin) {
            abort(404, 'Administrador no encontrado');
        }

        $profileData = [
            'id' => $admin->getId()->value(),
            'name' => $admin->getName()->value(),
            'email' => $admin->getEmail()->value(),
            'phone' => $admin->getPhone()?->value() ?? '',
            'avatar' => $admin->getAvatar()?->value() ?? '',
            'type' => $admin->getType()->value(),
            'is_active' => $admin->isActive(),
            'has_pin' => $admin->hasPin(),
        ];

        return Inertia::render('admin/profile/Index', [
            'user_uuid' => $user_uuid,
            'profile' => $profileData,
        ]);
    }
}
