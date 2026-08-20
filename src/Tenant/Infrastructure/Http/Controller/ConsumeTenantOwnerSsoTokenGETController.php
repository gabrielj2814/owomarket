<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Tenant\Application\UseCase\ConsumeTenantOwnerSsoTokenUseCase;

final class ConsumeTenantOwnerSsoTokenGETController
{
    public function __construct(
        private readonly ConsumeTenantOwnerSsoTokenUseCase $useCase
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if (empty($token)) {
            return redirect('/auth/login')->with('error', 'Token SSO no proporcionado');
        }

        try {
            $result = $this->useCase->execute($token);
            $user = $result['user'];

            Auth::login($user, true);

            return redirect($result['redirect_to']);
        } catch (Exception $e) {
            return redirect('/auth/login')->with('error', $e->getMessage());
        }
    }
}
