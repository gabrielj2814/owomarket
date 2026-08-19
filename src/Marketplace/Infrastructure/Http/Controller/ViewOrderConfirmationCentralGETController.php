<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Response;

final class ViewOrderConfirmationCentralGETController extends Controller
{
    public function index(string $id): Response
    {
        return inertia()->render('marketplace/checkout/CentralOrderConfirmationPage', [
            'domain' => request()->getHost(),
            'order_id' => $id,
        ]);
    }
}
