<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Inertia\Response;

final class ViewCartCentralGETController extends Controller
{
    public function index(): Response
    {
        return inertia()->render('marketplace/cart/CentralCartPage', [
            'domain' => request()->getHost(),
        ]);
    }
}
