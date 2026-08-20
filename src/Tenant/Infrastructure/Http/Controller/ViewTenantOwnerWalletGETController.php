<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Tenant\Application\UseCase\GetTenantOwnerWalletSummaryUseCase;

final class ViewTenantOwnerWalletGETController extends Controller
{
    public function __construct(
        private readonly GetTenantOwnerWalletSummaryUseCase $walletSummaryUseCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $summary = $this->walletSummaryUseCase->execute($user_uuid);

        return Inertia::render('tenant/wallet/TenantOwnerWalletPage', [
            'title' => 'Billetera Central & Liquidaciones - OwOMarket',
            'user_id' => $user_uuid,
            'wallet' => $summary,
        ]);
    }
}
