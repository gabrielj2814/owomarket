<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Response;

final class ViewCheckoutCentralGETController extends Controller
{
    public function index(): Response
    {
        return inertia()->render('marketplace/checkout/CentralCheckoutPage', [
            'domain' => request()->getHost(),
        ]);
    }
}
